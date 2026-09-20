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

Requirements:
1. A verified **WhatsApp Business Account (WABA)**, linked to a
   Facebook Business Manager account.
2. Phone number verification (a number not already active on regular
   WhatsApp/WhatsApp Business app).
3. **Message templates** (order confirmation, shipping update, etc.)
   submitted to Meta for approval before they can be sent outside a
   customer-initiated 24-hour conversation window.

## What this means for the build
- `WhatsAppSender` and `SMSSender` are written against generic
  `sendTemplate()` methods so swapping providers later is a config
  change, not a rewrite.
- Both toggles default to **off** in `settings` until the client has
  completed the registrations above — so the site is fully usable with
  just email notifications during development and soft-launch.
