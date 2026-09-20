/**
 * auth.js — Login / Register AJAX for Dabhi Chikki
 */

(function () {
  'use strict';

  /* ── Tab switching ───────────────────────────────── */
  const tabs    = document.querySelectorAll('.auth-tab');
  const forms   = document.querySelectorAll('.auth-form-panel');

  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      const target = tab.dataset.tab;
      tabs.forEach(t  => t.classList.remove('active'));
      forms.forEach(f => f.style.display = 'none');
      tab.classList.add('active');
      const panel = document.getElementById('panel-' + target);
      if (panel) panel.style.display = 'block';
    });
  });

  /* ── Login Form ──────────────────────────────────── */
  const loginForm = document.getElementById('login-form');
  if (loginForm) {
    loginForm.addEventListener('submit', async e => {
      e.preventDefault();
      const btn    = loginForm.querySelector('[type=submit]');
      const errEl  = document.getElementById('login-error');
      clearError(errEl);
      setLoading(btn, true);

      try {
        const res  = await fetch('api/auth.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          body: JSON.stringify({
            action:   'login',
            email:    loginForm.querySelector('[name=email]').value.trim(),
            password: loginForm.querySelector('[name=password]').value,
          })
        });
        const data = await res.json();
        if (data.ok) {
          showToast('Logged in! Redirecting…', 'success');
          const redirect = new URLSearchParams(window.location.search).get('redirect') || 'account.php';
          setTimeout(() => window.location.href = redirect, 800);
        } else {
          showError(errEl, data.error || 'Login failed. Check your credentials.');
        }
      } catch (_) {
        showError(errEl, 'Network error. Please try again.');
      } finally {
        setLoading(btn, false);
      }
    });
  }

  /* ── Register Form ───────────────────────────────── */
  const regForm = document.getElementById('register-form');
  if (regForm) {
    regForm.addEventListener('submit', async e => {
      e.preventDefault();
      const btn   = regForm.querySelector('[type=submit]');
      const errEl = document.getElementById('register-error');
      clearError(errEl);

      const pwd  = regForm.querySelector('[name=password]').value;
      const pwd2 = regForm.querySelector('[name=password2]').value;
      if (pwd !== pwd2) { showError(errEl, 'Passwords do not match.'); return; }
      if (pwd.length < 8) { showError(errEl, 'Password must be at least 8 characters.'); return; }

      setLoading(btn, true);
      try {
        const res  = await fetch('api/auth.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          body: JSON.stringify({
            action:    'register',
            full_name: regForm.querySelector('[name=full_name]').value.trim(),
            email:     regForm.querySelector('[name=email]').value.trim(),
            phone:     regForm.querySelector('[name=phone]').value.trim(),
            password:  pwd,
          })
        });
        const data = await res.json();
        if (data.ok) {
          showToast('Account created! Redirecting…', 'success');
          setTimeout(() => window.location.href = 'account.php', 900);
        } else {
          showError(errEl, data.error || 'Registration failed.');
        }
      } catch (_) {
        showError(errEl, 'Network error. Please try again.');
      } finally {
        setLoading(btn, false);
      }
    });
  }

  /* ── Helpers ─────────────────────────────────────── */
  function showError(el, msg) { if (el) { el.textContent = msg; el.style.display = 'flex'; } }
  function clearError(el)     { if (el) { el.textContent = ''; el.style.display = 'none'; } }
  function setLoading(btn, state) {
    if (!btn) return;
    btn.disabled = state;
    btn.classList.toggle('loading', state);
  }

})();
