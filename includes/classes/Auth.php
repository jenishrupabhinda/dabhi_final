<?php
/**
 * Auth — session-based authentication for all four roles.
 *
 * Role bucket (coarse): superadmin | admin | employee | buyer
 * Fine-grained permissions live in RBAC, not here.
 *
 * Login rate limiting: 5 consecutive failures → 15-minute lockout.
 * Lockout state is stored in the `otp_verifications` table (purpose='login')
 * so it survives PHP restarts and works across multiple processes.
 */

class Auth
{
    // ------------------------------------------------------------------ //
    // Read-only helpers
    // ------------------------------------------------------------------ //

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        static $cached = null;
        if ($cached === null) {
            $cached = Database::fetchOne(
                'SELECT * FROM users WHERE id = ? AND is_active = 1',
                [$_SESSION['user_id']]
            );
        }
        return $cached;
    }

    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    // ------------------------------------------------------------------ //
    // Guards
    // ------------------------------------------------------------------ //

    /**
     * Hard guard: redirect to login if the current user's role is not in $allowedRoles.
     * Usage: Auth::require(['superadmin', 'admin']);
     */
    public static function require(array $allowedRoles): void
    {
        if (!self::check()) {
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            if (str_contains($uri, '/admin/')) {
                redirect('/auth.php?redirect=' . urlencode('admin/index.php'));
            }
            redirect('/auth.php');
        }
        if (!in_array(self::role(), $allowedRoles, true)) {
            http_response_code(403);
            include __DIR__ . '/../../public_html/partials/403.php';
            exit;
        }
    }

    /**
     * Hard guard accepting variadic roles or an array.
     * Usage: Auth::requireRole('admin', 'superadmin');
     */
    public static function requireRole(string ...$roles): void
    {
        self::require($roles);
    }

    // ------------------------------------------------------------------ //
    // Login
    // ------------------------------------------------------------------ //

    /**
     * Validate credentials and open a session.
     *
     * @return array{ok:bool, error:string, user:array|null}
     */
    public static function attempt(string $emailOrPhone, string $password): array
    {
        $emailOrPhone = trim($emailOrPhone);

        // Rate-limit check
        if (self::isLockedOut($emailOrPhone)) {
            return ['ok' => false, 'error' => 'Too many failed attempts. Please wait 15 minutes and try again.', 'user' => null];
        }

        // Allow login by email OR phone
        $user = Database::fetchOne(
            'SELECT id, role, full_name, password_hash, is_active, must_reset_password
             FROM users
             WHERE (email = ? OR phone = ?) AND role != \'buyer\'
                OR (email = ? OR phone = ?) AND role = \'buyer\'',
            [$emailOrPhone, $emailOrPhone, $emailOrPhone, $emailOrPhone]
        );

        // Simpler: just search by email or phone regardless of role
        $user = Database::fetchOne(
            'SELECT id, role, full_name, password_hash, is_active, must_reset_password
             FROM users
             WHERE email = ? OR phone = ?
             LIMIT 1',
            [$emailOrPhone, $emailOrPhone]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            self::recordFailedAttempt($emailOrPhone);
            return ['ok' => false, 'error' => 'Invalid email/phone or password.', 'user' => null];
        }

        if (!(int) $user['is_active']) {
            return ['ok' => false, 'error' => 'Your account has been deactivated. Please contact support.', 'user' => null];
        }

        // Success — open session
        self::clearLockout($emailOrPhone);
        session_regenerate_id(true);

        $_SESSION['user_id']   = (int) $user['id'];
        $_SESSION['user_role'] = $user['role'];

        Database::query(
            'UPDATE users SET last_login_at = NOW() WHERE id = ?',
            [$user['id']]
        );

        return ['ok' => true, 'error' => '', 'user' => $user];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    // ------------------------------------------------------------------ //
    // Registration
    // ------------------------------------------------------------------ //

    /**
     * Create a new buyer account.
     * @return array{ok:bool, error:string, userId:int|null}
     */
    public static function register(string $fullName, string $email, string $phone, string $password): array
    {
        $email = strtolower(trim($email));
        $phone = trim($phone);

        $existingUser = Database::fetchOne('SELECT id, role, full_name, phone FROM users WHERE email = ?', [$email]);
        if ($existingUser) {
            if ($existingUser['role'] === 'buyer') {
                // Existing guest buyer account — set their chosen password and activate
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                Database::query(
                    'UPDATE users SET full_name = ?, phone = COALESCE(NULLIF(?, ""), phone), password_hash = ?, is_active = 1, last_login_at = NOW() WHERE id = ?',
                    [$fullName, $phone, $hash, $existingUser['id']]
                );
                return ['ok' => true, 'error' => '', 'userId' => (int)$existingUser['id']];
            }
            return ['ok' => false, 'error' => 'An account with this email already exists. Please login.', 'userId' => null];
        }

        if (!empty($phone)) {
            $existingPhone = Database::fetchOne('SELECT id, role FROM users WHERE phone = ?', [$phone]);
            if ($existingPhone) {
                if ($existingPhone['role'] === 'buyer') {
                    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    Database::query(
                        'UPDATE users SET full_name = ?, email = COALESCE(NULLIF(?, ""), email), password_hash = ?, is_active = 1, last_login_at = NOW() WHERE id = ?',
                        [$fullName, $email, $hash, $existingPhone['id']]
                    );
                    return ['ok' => true, 'error' => '', 'userId' => (int)$existingPhone['id']];
                }
                return ['ok' => false, 'error' => 'An account with this phone number already exists.', 'userId' => null];
            }
        }

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $uuid = generateUuidV4();

        Database::query(
            'INSERT INTO users (uuid, role, full_name, email, phone, password_hash)
             VALUES (?, \'buyer\', ?, ?, ?, ?)',
            [$uuid, $fullName, $email, $phone, $hash]
        );

        return ['ok' => true, 'error' => '', 'userId' => (int) Database::lastInsertId()];
    }

    // ------------------------------------------------------------------ //
    // Password reset
    // ------------------------------------------------------------------ //

    /**
     * Create a password reset token for a user identified by email or phone.
     * Returns ['ok'=>true, 'token'=>..., 'userId'=>...] or ['ok'=>false, 'error'=>...].
     * Token is URL-safe (hex), stored hashed.
     */
    public static function createPasswordResetToken(string $emailOrPhone): array
    {
        $user = Database::fetchOne(
            'SELECT id, email FROM users WHERE email = ? OR phone = ? LIMIT 1',
            [$emailOrPhone, $emailOrPhone]
        );

        if (!$user) {
            // Return ok=true to not reveal whether the account exists
            return ['ok' => true, 'token' => null, 'userId' => null, 'email' => null];
        }

        // Invalidate old tokens for this user
        Database::query(
            'DELETE FROM password_resets WHERE user_id = ?',
            [$user['id']]
        );

        $token     = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . PASSWORD_RESET_TOKEN_TTL_MINUTES . ' minutes'));

        Database::query(
            'INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)',
            [$user['id'], $tokenHash, $expiresAt]
        );

        return ['ok' => true, 'token' => $token, 'userId' => (int) $user['id'], 'email' => $user['email']];
    }

    /**
     * Consume a password reset token and update the password.
     * Returns ['ok'=>bool, 'error'=>string].
     */
    public static function consumePasswordResetToken(string $token, string $newPassword): array
    {
        $tokenHash = hash('sha256', $token);

        $reset = Database::fetchOne(
            'SELECT id, user_id, expires_at, used_at FROM password_resets WHERE token_hash = ?',
            [$tokenHash]
        );

        if (!$reset) {
            return ['ok' => false, 'error' => 'Invalid or expired password reset link.'];
        }
        if ($reset['used_at'] !== null) {
            return ['ok' => false, 'error' => 'This reset link has already been used.'];
        }
        if (strtotime($reset['expires_at']) < time()) {
            return ['ok' => false, 'error' => 'This reset link has expired. Please request a new one.'];
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        Database::query(
            'UPDATE users SET password_hash = ?, must_reset_password = 0 WHERE id = ?',
            [$hash, $reset['user_id']]
        );
        Database::query(
            'UPDATE password_resets SET used_at = NOW() WHERE id = ?',
            [$reset['id']]
        );

        return ['ok' => true, 'error' => ''];
    }

    // ------------------------------------------------------------------ //
    // Staff account creation (used by admin panel / create_superadmin.php)
    // ------------------------------------------------------------------ //

    /**
     * Create an admin or employee account.
     * @param string $role 'superadmin' | 'admin' | 'employee'
     * @return array{ok:bool, error:string, userId:int|null}
     */
    public static function createStaffAccount(
        string $fullName,
        string $email,
        string $phone,
        string $role,
        int    $createdBy = 0
    ): array {
        $email = strtolower(trim($email));
        $phone = trim($phone);

        if (Database::fetchOne('SELECT id FROM users WHERE email = ?', [$email])) {
            return ['ok' => false, 'error' => 'Email already in use.', 'userId' => null];
        }
        if (Database::fetchOne('SELECT id FROM users WHERE phone = ?', [$phone])) {
            return ['ok' => false, 'error' => 'Phone already in use.', 'userId' => null];
        }

        // Generate a temporary random password — user must reset on first login
        $tempPass = bin2hex(random_bytes(6));
        $hash     = password_hash($tempPass, PASSWORD_BCRYPT, ['cost' => 12]);
        $uuid     = generateUuidV4();
        $cb       = $createdBy > 0 ? $createdBy : null;

        Database::query(
            'INSERT INTO users (uuid, role, full_name, email, phone, password_hash, must_reset_password, created_by)
             VALUES (?, ?, ?, ?, ?, ?, 1, ?)',
            [$uuid, $role, $fullName, $email, $phone, $hash, $cb]
        );

        return [
            'ok'       => true,
            'error'    => '',
            'userId'   => (int) Database::lastInsertId(),
            'tempPass' => $tempPass,  // caller must communicate this to the new user
        ];
    }

    // ------------------------------------------------------------------ //
    // Rate limiting (stored in DB so it survives restarts)
    // ------------------------------------------------------------------ //

    private static function lockoutKey(string $identifier): string
    {
        return 'lockout_' . md5(strtolower(trim($identifier)));
    }

    private static function isLockedOut(string $identifier): bool
    {
        $row = Database::fetchOne(
            "SELECT setting_value FROM settings WHERE setting_key = ?",
            [self::lockoutKey($identifier)]
        );
        if (!$row) return false;

        $data = json_decode($row['setting_value'], true);
        return $data && isset($data['until']) && $data['until'] > time();
    }

    private static function recordFailedAttempt(string $identifier): void
    {
        $key = self::lockoutKey($identifier);
        $row = Database::fetchOne('SELECT setting_value FROM settings WHERE setting_key = ?', [$key]);
        $data = $row ? json_decode($row['setting_value'], true) : ['count' => 0, 'until' => 0];

        $data['count'] = ($data['count'] ?? 0) + 1;
        if ($data['count'] >= MAX_LOGIN_ATTEMPTS) {
            $data['until'] = time() + 900; // 15-min lockout
            $data['count'] = 0;
        }

        Database::query(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
            [$key, json_encode($data)]
        );
    }

    private static function clearLockout(string $identifier): void
    {
        Database::query(
            'DELETE FROM settings WHERE setting_key = ?',
            [self::lockoutKey($identifier)]
        );
    }

    // ------------------------------------------------------------------ //
    // Lockout remaining time helper (for UI feedback)
    // ------------------------------------------------------------------ //

    public static function lockoutRemainingSeconds(string $identifier): int
    {
        $row = Database::fetchOne('SELECT setting_value FROM settings WHERE setting_key = ?', [self::lockoutKey($identifier)]);
        if (!$row) return 0;
        $data = json_decode($row['setting_value'], true);
        $remaining = ($data['until'] ?? 0) - time();
        return max(0, (int) $remaining);
    }
}
