<?php
/**
 * NotificationDispatcher — single entry point the rest of the app calls
 * ("order confirmed", "order shipped", etc). It fans out to
 * EmailSender / WhatsAppSender / SMSSender, respecting the site-wide
 * on/off toggles in `settings`, and writes every attempt to
 * notifications_log regardless of outcome (so support can see what did
 * or didn't get delivered).
 *
 * NEXT PHASE:
 *   public static function orderConfirmed(int $orderId): void
 *   public static function orderStatusChanged(int $orderId, string $newStatus): void
 *   private static function send(string $channel, string $eventType, int $orderId, string $recipient): void
 *       // checks settingEnabled('email_notifications_enabled') / 'whatsapp_...' / 'sms_...'
 *       // before calling the relevant *Sender class; logs skipped_disabled if off
 */
class NotificationDispatcher {}
