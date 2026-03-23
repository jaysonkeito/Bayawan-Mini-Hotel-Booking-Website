<?php
// bayawan-mini-hotel-system/includes/paymongo/user_webhook.php
//
// ================================================================
//  PAYMONGO WEBHOOK ENDPOINT
//  Receives server-to-server payment event notifications from
//  PayMongo. Handles cases where the user closes the browser
//  before the redirect fires — ensuring bookings are always
//  confirmed or failed correctly.
//
//  HOW IT WORKS:
//  1. PayMongo sends a POST request to this URL with a signed payload
//  2. We verify the signature using PAYMONGO_WEBHOOK_SECRET
//  3. For 'checkout_session.payment.paid' events we mark the
//     booking as 'booked' and send confirmation email
//  4. For 'checkout_session.payment.failed' events we mark
//     the booking as 'payment failed'
//
//  SETUP:
//  1. Start ngrok: ngrok http 3000
//  2. Register webhook in PayMongo dashboard:
//     URL: https://YOUR_NGROK_URL/bayawan-mini-hotel-system/includes/paymongo/user_webhook.php
//     Events: checkout_session.payment.paid, checkout_session.payment.failed
//  3. Copy the webhook secret from PayMongo dashboard to .env:
//     PAYMONGO_WEBHOOK_SECRET=whsk_xxxxxxxxxxxx
// ================================================================

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../admin/includes/admin_configuration.php';
require_once __DIR__ . '/../../admin/includes/admin_essentials.php';
require_once __DIR__ . '/../user_email_helper.php';

date_default_timezone_set('Asia/Manila');

// ── Only accept POST requests ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// ── Read raw payload ──────────────────────────────────────────
$raw_payload = file_get_contents('php://input');

if (empty($raw_payload)) {
    http_response_code(400);
    exit('Empty payload');
}

// ── Verify PayMongo signature ─────────────────────────────────
// PayMongo signs each webhook with HMAC-SHA256 using your webhook secret.
// The signature is in the Paymongo-Signature header as:
// t=TIMESTAMP,te=TEST_SIG,li=LIVE_SIG
$signature_header = $_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? '';
$webhook_secret   = PAYMONGO_WEBHOOK_SECRET;

if (empty($webhook_secret)) {
    // Secret not configured — log and reject
    error_log('[Webhook] PAYMONGO_WEBHOOK_SECRET is not set in .env');
    http_response_code(500);
    exit('Webhook secret not configured');
}

if (!verifyPaymongoSignature($raw_payload, $signature_header, $webhook_secret)) {
    error_log('[Webhook] Signature verification failed');
    http_response_code(401);
    exit('Invalid signature');
}

// ── Parse event ───────────────────────────────────────────────
$event = json_decode($raw_payload, true);

if (!$event || !isset($event['data']['attributes']['type'])) {
    http_response_code(400);
    exit('Invalid event structure');
}

$event_type = $event['data']['attributes']['type'];
$event_data = $event['data']['attributes']['data'] ?? [];
$session_id = $event_data['id'] ?? '';

error_log("[Webhook] Received event: {$event_type} | session: {$session_id}");

// ── Handle events ─────────────────────────────────────────────
switch ($event_type) {

    case 'checkout_session.payment.paid':
        handlePaymentSuccess($event_data, $session_id);
        break;

    case 'checkout_session.payment.failed':
        handlePaymentFailed($event_data, $session_id);
        break;

    default:
        // Unhandled event type — acknowledge and ignore
        error_log("[Webhook] Unhandled event type: {$event_type}");
        break;
}

// ── Always respond 200 to acknowledge receipt ─────────────────
http_response_code(200);
echo json_encode(['received' => true]);
exit;


// ================================================================
//  FUNCTIONS
// ================================================================

/**
 * Verify the PayMongo webhook signature.
 * Format: t=TIMESTAMP,te=TEST_SIGNATURE,li=LIVE_SIGNATURE
 */
function verifyPaymongoSignature(string $payload, string $header, string $secret): bool {
    if (empty($header)) return false;

    // Parse header parts
    $parts = [];
    foreach (explode(',', $header) as $part) {
        [$key, $value] = explode('=', $part, 2) + ['', ''];
        $parts[trim($key)] = trim($value);
    }

    $timestamp = $parts['t'] ?? '';
    // Use 'te' for test mode, 'li' for live mode
    $env_key   = (PAYMONGO_ENVIRONMENT === 'LIVE') ? 'li' : 'te';
    $signature = $parts[$env_key] ?? '';

    if (empty($timestamp) || empty($signature)) return false;

    // Reconstruct the signed message
    $signed_payload = $timestamp . '.' . $payload;
    $expected       = hash_hmac('sha256', $signed_payload, $secret);

    return hash_equals($expected, $signature);
}

/**
 * Handle successful payment — mark booking as 'booked'.
 * Skips if already processed (idempotent).
 */
function handlePaymentSuccess(array $session_data, string $session_id): void {
    if (empty($session_id)) return;

    $attrs    = $session_data['attributes'] ?? [];
    $ref      = $attrs['reference_number'] ?? '';
    $payments = $attrs['payment_intent']['attributes']['payments'] ?? [];
    $txn_id   = $payments[0]['id'] ?? '';
    $txn_amt  = isset($payments[0]['attributes']['amount'])
        ? $payments[0]['attributes']['amount'] / 100
        : 0;

    // Find bookings by order_id (single) or cart ref
    $res = select(
        "SELECT `booking_id`, `booking_status`, `user_id`
         FROM `booking_order`
         WHERE `order_id` = ? OR `order_id` LIKE ?
         LIMIT 20",
        [$ref, $ref . '_%'], 'ss'
    );

    if (mysqli_num_rows($res) === 0) {
        error_log("[Webhook] No booking found for ref: {$ref}");
        return;
    }

    while ($row = mysqli_fetch_assoc($res)) {
        // Skip if already booked (idempotent — webhook may fire multiple times)
        if ($row['booking_status'] === 'booked') {
            error_log("[Webhook] Booking {$row['booking_id']} already booked — skipping");
            continue;
        }

        // Only update if currently pending or payment failed
        if (!in_array($row['booking_status'], ['pending', 'payment failed'])) {
            continue;
        }

        update(
            "UPDATE `booking_order` SET
             `booking_status` = 'booked',
             `trans_id`       = ?,
             `trans_amt`      = ?,
             `trans_status`   = 'TXN_SUCCESS',
             `trans_resp_msg` = 'Payment confirmed via webhook.'
             WHERE `booking_id` = ?",
            [$txn_id, $txn_amt, $row['booking_id']], 'ssi'
        );

        // Send confirmation email
        $email_q    = "SELECT bo.*, bd.*, uc.email, uc.name AS user_name
                       FROM `booking_order` bo
                       INNER JOIN `booking_details` bd ON bo.booking_id = bd.booking_id
                       INNER JOIN `user_cred` uc ON bo.user_id = uc.id
                       WHERE bo.booking_id = ? LIMIT 1";
        $email_data = mysqli_fetch_assoc(select($email_q, [$row['booking_id']], 'i'));

        if ($email_data) {
            sendBookingConfirmationEmail($email_data);
            error_log("[Webhook] Confirmed booking {$row['booking_id']} and sent email.");
        }
    }
}

/**
 * Handle failed payment — mark booking as 'payment failed'.
 * Skips if already processed (idempotent).
 */
function handlePaymentFailed(array $session_data, string $session_id): void {
    if (empty($session_id)) return;

    $attrs = $session_data['attributes'] ?? [];
    $ref   = $attrs['reference_number'] ?? '';

    $res = select(
        "SELECT `booking_id`, `booking_status`
         FROM `booking_order`
         WHERE `order_id` = ? OR `order_id` LIKE ?
         LIMIT 20",
        [$ref, $ref . '_%'], 'ss'
    );

    while ($row = mysqli_fetch_assoc($res)) {
        if ($row['booking_status'] !== 'pending') continue;

        update(
            "UPDATE `booking_order` SET
             `booking_status` = 'payment failed',
             `trans_status`   = 'TXN_FAILURE',
             `trans_resp_msg` = 'Payment failed — confirmed via webhook.'
             WHERE `booking_id` = ?",
            [$row['booking_id']], 'i'
        );

        error_log("[Webhook] Marked booking {$row['booking_id']} as payment failed via webhook.");
    }
}