# SMS & WhatsApp Notifications — Compliance Notes (India)

These aren't optional technical details — without them, the SMS/WhatsApp
toggles in the admin panel will look like they work but messages simply
won't deliver.

## SMS — DLT Registration (mandatory)
Indian telecom regulation (TRAI) requires every transactional SMS sender
to be registered on a **DLT (Distributed Ledger Technology) platform**
before any message will actually deliver:

1. Register as an entity on a DLT platform (each SMS provider — MSG91,
   Twilio's Indian routes, etc. — links to one, commonly Jio/Airtel/Vi DLT portals).
2. Register a **Sender ID** (6 characters, e.g. `DBHCHK`).
3. Register the **exact wording** of every message template you'll send
   (order confirmed, shipped, delivered, OTP, etc.) — the gateway rejects
   any message that doesn't match an approved template.
4. This approval can take a few days, so it should start well before
   go-live, not the week of launch.

## WhatsApp — Business API (mandatory for automated messages)
A personal WhatsApp number cannot send automated order notifications.
You need the **WhatsApp Business Platform (Cloud API)**, either directly
from Meta or via a Business Solution Provider (BSP) like Gupshup,
Interakt, or AiSensy (BSPs are usually faster to onboard and include a
simple dashboard, at a monthly cost).

Requirements for Production:
1. A verified **WhatsApp Business Account (WABA)**, linked to a
   Facebook Business Manager account.
2. Phone number verification (a number not already active on regular
   WhatsApp/WhatsApp Business app).
3. **Message templates** (order confirmation, shipping update, etc.)
   submitted to Meta for approval before they can be sent outside a
   customer-initiated 24-hour conversation window.

## WhatsApp Testing Mode (Sandbox / Temporary Number)
During development and before permanent Business verification is complete, you can use Meta's **Test Phone Number** provided in the Meta Developer Portal:

### 1. How to obtain temporary credentials:
1. Go to [Meta for Developers](https://developers.facebook.com/apps) and open your App.
2. Navigate to **WhatsApp > API Setup**.
3. Under **Step 1: Send and receive messages**:
   - Copy the **Phone number ID** (e.g. `1029384756...`).
   - Copy the **Temporary access token** (valid for 24 hours).
4. Under **Step 2: Select phone numbers to send messages to**:
   - Click "Manage phone number list" and add your own mobile number.
   - Meta will send a 6-digit verification code to your WhatsApp. Enter the code to verify.

### 2. Configure in Admin Panel:
1. Go to **Admin > Notification Settings** (`/admin/notification-settings.php`).
2. Enable **WhatsApp Cloud API**.
3. Select **🧪 Sandbox / Test Mode (Temporary Test Number)**.
4. Paste your **Test Phone Number ID** and **Temporary Access Token**.
5. Set your **Verified Test Recipient WhatsApp Number** (e.g. `+91 98765 43210`).
6. Ensure **"Route all automated store notifications to Test Recipient"** is checked.
   - *Why?* This ensures that whenever test orders are placed or order statuses are changed, the notification safely routes to your verified test number with customer simulation tags, preventing Meta API `(#131030) Sandbox recipient not verified` errors!
7. Click **Save All Notification Settings**.

### 3. Immediate Testing:
Use the **WhatsApp Interactive Testing Console** on the same page:
- Select **🌐 Built-in Meta Template ("hello_world")** and click **🚀 Send Test WhatsApp**.
- Meta will instantly deliver the test message to your WhatsApp!
- You can also test custom messages and simulated order notifications.
- All attempts are logged in the **Recent Dispatch Log** with Meta Message IDs or actionable troubleshooting tips if tokens expire.

### 4. Transitioning to Live Production:
1. Once your permanent WABA and Phone Number are verified by Meta, switch the environment to **🚀 Live Production Mode**.
2. Enter your **Live Phone Number ID** and permanent **System User Access Token**.
3. Save settings. Automated order notifications will now deliver to actual customers upon checkout and order status changes.
