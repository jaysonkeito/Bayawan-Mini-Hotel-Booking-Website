<?php
// bayawan-mini-hotel-system/includes/user_email_helper.php

require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Core mailer. All template functions call this.
 */
function sendHotelEmail(string $to, string $subject, string $body): bool {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = wrapEmailTemplate($subject, $body);
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('PHPMailer Error [' . $subject . ']: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Wraps any email body in a consistent hotel-branded HTML shell.
 */
function wrapEmailTemplate(string $title, string $content): string {
    $year = date('Y');
    return <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>{$title}</title>
    </head>
    <body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;font-size:15px;color:#333;">
      <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:30px 0;">
        <tr><td align="center">
          <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
            <tr>
              <td style="background:#1a1a2e;padding:24px 32px;text-align:center;">
                <h1 style="margin:0;color:#2ec1ac;font-size:22px;letter-spacing:1px;">Bayawan Mini Hotel</h1>
                <p style="margin:4px 0 0;color:#aaa;font-size:12px;">Poblacion, Bayawan City, Negros Oriental</p>
              </td>
            </tr>
            <tr>
              <td style="padding:32px;">
                {$content}
              </td>
            </tr>
            <tr>
              <td style="background:#f9f9f9;padding:16px 32px;text-align:center;border-top:1px solid #eee;">
                <p style="margin:0;font-size:12px;color:#999;">
                  Questions? Email us at <a href="mailto:bayawanminihotel@gmail.com" style="color:#2ec1ac;">bayawanminihotel@gmail.com</a>
                </p>
                <p style="margin:6px 0 0;font-size:11px;color:#bbb;">
                  &copy; {$year} Bayawan Mini Hotel. All rights reserved.
                </p>
              </td>
            </tr>
          </table>
        </td></tr>
      </table>
    </body>
    </html>
    HTML;
}

/**
 * Reusable HTML row for booking summary tables inside emails.
 */
function emailTableRow(string $label, string $value): string {
    return <<<HTML
    <tr>
      <td style="padding:8px 12px;background:#f9f9f9;font-weight:bold;width:40%;border-bottom:1px solid #eee;">{$label}</td>
      <td style="padding:8px 12px;border-bottom:1px solid #eee;">{$value}</td>
    </tr>
    HTML;
}

// ─────────────────────────────────────────────
//  1. BOOKING CONFIRMATION
// ─────────────────────────────────────────────
function sendBookingConfirmationEmail(array $d): bool {
    $checkin  = date('F j, Y', strtotime($d['check_in']));
    $checkout = date('F j, Y', strtotime($d['check_out']));
    $booked   = date('F j, Y \a\t h:i A', strtotime($d['datentime']));

    $rows  = emailTableRow('Order ID',    htmlspecialchars($d['order_id']));
    $rows .= emailTableRow('Room',        htmlspecialchars($d['room_name']));
    $rows .= emailTableRow('Check-in',    $checkin);
    $rows .= emailTableRow('Check-out',   $checkout);
    $rows .= emailTableRow('Amount Paid', '&#8369;' . number_format($d['trans_amt'], 2));
    $rows .= emailTableRow('Booked on',   $booked);

    $body = <<<HTML
    <h2 style="color:#2ec1ac;margin:0 0 8px;">Booking Confirmed!</h2>
    <p style="margin:0 0 20px;color:#555;">
      Hi <strong>{$d['user_name']}</strong>, your booking has been successfully confirmed.
      We look forward to welcoming you!
    </p>
    <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eee;border-radius:6px;overflow:hidden;margin-bottom:24px;">
      {$rows}
    </table>
    <div style="background:#e8f8f5;border-left:4px solid #2ec1ac;padding:12px 16px;border-radius:4px;margin-bottom:20px;">
      <p style="margin:0;font-size:13px;color:#0f6e56;">
        <strong>What's next?</strong> Our staff will assign your room number upon your arrival.
        You can download your receipt from the <strong>My Bookings</strong> page.
      </p>
    </div>
    <div style="background:#fff8e1;border-left:4px solid #f9a825;padding:12px 16px;border-radius:4px;margin-bottom:20px;">
      <p style="margin:0;font-size:13px;color:#7a5800;">
        <strong>Cancellation Policy:</strong><br>
        ✅ 72+ hours before check-in — Full refund<br>
        ⚠️ 24–72 hours before check-in — 50% of first night charged<br>
        ❌ Less than 24 hours — First night forfeited
      </p>
    </div>
    <p style="margin:0;font-size:13px;color:#999;">Thank you for choosing Bayawan Mini Hotel!</p>
    HTML;

    return sendHotelEmail($d['email'], 'Booking Confirmed – ' . $d['order_id'], $body);
}

// ─────────────────────────────────────────────
//  2. CANCELLATION NOTICE
//     Now includes refund_amt and policy_msg
// ─────────────────────────────────────────────
function sendGuestCancellationEmail(array $d): bool {
    $checkin  = date('F j, Y', strtotime($d['check_in']));
    $checkout = date('F j, Y', strtotime($d['check_out']));

    // Use refund_amt if available, otherwise fall back to trans_amt
    $refund_amt  = isset($d['refund_amt']) && $d['refund_amt'] !== null
        ? (float) $d['refund_amt']
        : (float) $d['trans_amt'];

    $policy_msg  = $d['policy_msg'] ?? 'Refund will be processed by our team.';
    $paid_amt    = (float) $d['trans_amt'];
    $penalty_amt = round($paid_amt - $refund_amt, 2);

    $rows  = emailTableRow('Order ID',      htmlspecialchars($d['order_id']));
    $rows .= emailTableRow('Room',          htmlspecialchars($d['room_name']));
    $rows .= emailTableRow('Check-in',      $checkin);
    $rows .= emailTableRow('Check-out',     $checkout);
    $rows .= emailTableRow('Amount Paid',   '&#8369;' . number_format($paid_amt, 2));
    $rows .= emailTableRow('Penalty',       $penalty_amt > 0 ? '&#8369;' . number_format($penalty_amt, 2) : 'None');
    $rows .= emailTableRow('Refund Amount', '&#8369;' . number_format($refund_amt, 2));

    // Color the refund box based on amount
    if ($refund_amt >= $paid_amt) {
        $refund_color  = '#e8f8f5';
        $refund_border = '#2ec1ac';
        $refund_text   = '#0f6e56';
    } elseif ($refund_amt > 0) {
        $refund_color  = '#fff8e1';
        $refund_border = '#f9a825';
        $refund_text   = '#7a5800';
    } else {
        $refund_color  = '#fef3f2';
        $refund_border = '#e74c3c';
        $refund_text   = '#a93226';
    }

    $body = <<<HTML
    <h2 style="color:#e74c3c;margin:0 0 8px;">Booking Cancelled</h2>
    <p style="margin:0 0 20px;color:#555;">
      Hi <strong>{$d['user_name']}</strong>, your booking has been cancelled.
    </p>
    <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eee;border-radius:6px;overflow:hidden;margin-bottom:24px;">
      {$rows}
    </table>
    <div style="background:{$refund_color};border-left:4px solid {$refund_border};padding:12px 16px;border-radius:4px;margin-bottom:20px;">
      <p style="margin:0;font-size:13px;color:{$refund_text};">
        <strong>Cancellation Policy Applied:</strong><br>
        {$policy_msg}
      </p>
    </div>
    <p style="margin:0;font-size:13px;color:#999;">We hope to see you again soon!</p>
    HTML;

    return sendHotelEmail($d['email'], 'Booking Cancelled – ' . $d['order_id'], $body);
}

// ─────────────────────────────────────────────
//  3. ARRIVAL CONFIRMATION
// ─────────────────────────────────────────────
function sendArrivalConfirmationEmail(array $d): bool {
    $checkin  = date('F j, Y', strtotime($d['check_in']));
    $checkout = date('F j, Y', strtotime($d['check_out']));

    $rows  = emailTableRow('Order ID',    htmlspecialchars($d['order_id']));
    $rows .= emailTableRow('Room Name',   htmlspecialchars($d['room_name']));
    $rows .= emailTableRow('Room Number', htmlspecialchars($d['room_no']));
    $rows .= emailTableRow('Check-in',    $checkin);
    $rows .= emailTableRow('Check-out',   $checkout);
    $rows .= emailTableRow('Amount Paid', '&#8369;' . number_format($d['trans_amt'], 2));

    $body = <<<HTML
    <h2 style="color:#2ec1ac;margin:0 0 8px;">Welcome! Your Room is Ready.</h2>
    <p style="margin:0 0 20px;color:#555;">
      Hi <strong>{$d['user_name']}</strong>, your arrival has been confirmed and your room
      has been assigned. Enjoy your stay!
    </p>
    <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eee;border-radius:6px;overflow:hidden;margin-bottom:24px;">
      {$rows}
    </table>
    <div style="background:#e8f8f5;border-left:4px solid #2ec1ac;padding:12px 16px;border-radius:4px;margin-bottom:20px;">
      <p style="margin:0;font-size:13px;color:#0f6e56;">
        <strong>Your stay is confirmed!</strong> You can download your PDF receipt and
        leave a rating &amp; review from the <strong>My Bookings</strong> page after checkout.
      </p>
    </div>
    <p style="margin:0;font-size:13px;color:#999;">Thank you for staying at Bayawan Mini Hotel!</p>
    HTML;

    return sendHotelEmail($d['email'], 'Your Room is Ready – ' . $d['room_name'], $body);
}

// ─────────────────────────────────────────────
//  4. REFUND PROCESSED
//     Now shows actual refund_amt
// ─────────────────────────────────────────────
function sendRefundProcessedEmail(array $d): bool {
    $checkin  = date('F j, Y', strtotime($d['check_in']));
    $checkout = date('F j, Y', strtotime($d['check_out']));

    $refund_amt = isset($d['refund_amt']) && $d['refund_amt'] !== null
        ? (float) $d['refund_amt']
        : (float) $d['trans_amt'];

    $rows  = emailTableRow('Order ID',      htmlspecialchars($d['order_id']));
    $rows .= emailTableRow('Room',          htmlspecialchars($d['room_name']));
    $rows .= emailTableRow('Check-in',      $checkin);
    $rows .= emailTableRow('Check-out',     $checkout);
    $rows .= emailTableRow('Refund Amount', '&#8369;' . number_format($refund_amt, 2));

    $body = <<<HTML
    <h2 style="color:#2ec1ac;margin:0 0 8px;">Your Refund Has Been Processed</h2>
    <p style="margin:0 0 20px;color:#555;">
      Hi <strong>{$d['user_name']}</strong>, we have completed the refund for your
      cancelled booking. Please allow a few business days for the amount to reflect
      in your account.
    </p>
    <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eee;border-radius:6px;overflow:hidden;margin-bottom:24px;">
      {$rows}
    </table>
    <div style="background:#e8f8f5;border-left:4px solid #2ec1ac;padding:12px 16px;border-radius:4px;margin-bottom:20px;">
      <p style="margin:0;font-size:13px;color:#0f6e56;">
        <strong>Note:</strong> Refund timelines depend on your payment provider (GCash, card, Maya, GrabPay).
        If you have not received it after 7 business days, please contact us.
      </p>
    </div>
    <p style="margin:0;font-size:13px;color:#999;">We hope to welcome you again at Bayawan Mini Hotel!</p>
    HTML;

    return sendHotelEmail($d['email'], 'Refund Processed – ' . $d['order_id'], $body);
}