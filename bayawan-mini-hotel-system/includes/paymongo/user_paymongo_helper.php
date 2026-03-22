<?php
/**
 * bayawan-mini-hotel-system/includes/paymongo/user_paymongo_helper.php
 * 
 * PayMongo API helper functions.
 * Added: createPaymongoCartCheckout() for multi-room cart payments.
 * All existing functions are unchanged.
 */


/**
 * Returns the Authorization header value for PayMongo API calls.
 */
function getPaymongoAuthHeader() {
    return 'Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':');
}


/**
 * Creates a PayMongo Checkout Session for a SINGLE room booking.
 * Existing function — unchanged.
 *
 * @param string $orderId     Internal order ID
 * @param float  $amountPHP   Amount in PHP (e.g. 1500.00)
 * @param string $description Description shown to customer
 * @return array ['checkout_url' => '...', 'session_id' => '...'] or ['error' => '...']
 */
function createPaymongoCheckout($orderId, $amountPHP, $description = 'Hotel Booking') {
    $amountCentavos = (int) round($amountPHP * 100);

    $payload = [
        'data' => [
            'attributes' => [
                'send_email_receipt'   => false,
                'show_description'     => true,
                'show_line_items'      => true,
                'cancel_url'           => PAYMONGO_FAILED_URL,
                'success_url'          => PAYMONGO_SUCCESS_URL . '&order_id=' . urlencode($orderId),
                'description'          => $description,
                'reference_number'     => $orderId,
                'payment_method_types' => ['gcash', 'card', 'paymaya', 'grab_pay'],
                'line_items'           => [
                    [
                        'currency' => 'PHP',
                        'amount'   => $amountCentavos,
                        'name'     => $description,
                        'quantity' => 1,
                    ],
                ],
            ],
        ],
    ];

    $response = callPaymongoAPI('/checkout_sessions', $payload);

    if (isset($response['data']['attributes']['checkout_url'])) {
        return [
            'checkout_url' => $response['data']['attributes']['checkout_url'],
            'session_id'   => $response['data']['id'],
        ];
    }

    return ['error' => $response['errors'][0]['detail'] ?? 'Unknown PayMongo error'];
}


/**
 * Creates a PayMongo Checkout Session for MULTIPLE rooms (cart checkout).
 * Each room becomes a separate line item on the PayMongo-hosted page.
 *
 * @param string $cartRef   Cart reference ID used as the PayMongo reference_number
 * @param array  $items     Array of line items:
 *                          [
 *                            ['name' => string, 'amount' => float (PHP), 'currency' => 'PHP', 'quantity' => int],
 *                            ...
 *                          ]
 * @return array ['checkout_url' => '...', 'session_id' => '...'] or ['error' => '...']
 */
function createPaymongoCartCheckout(string $cartRef, array $items): array {

    if (empty($items)) {
        return ['error' => 'Cart is empty — no line items to charge.'];
    }

    // Build PayMongo line_items array (amounts in centavos)
    $line_items  = [];
    $total_php   = 0;

    foreach ($items as $item) {
        $amount_centavos = (int) round((float) $item['amount'] * 100);
        $line_items[]    = [
            'currency' => 'PHP',
            'amount'   => $amount_centavos,
            'name'     => $item['name'],
            'quantity' => (int) ($item['quantity'] ?? 1),
        ];
        $total_php += (float) $item['amount'];
    }

    $room_count  = count($items);
    $description = $room_count . ' Room' . ($room_count > 1 ? 's' : '') . ' — Bayawan Mini Hotel';

    $payload = [
        'data' => [
            'attributes' => [
                'send_email_receipt'   => false,
                'show_description'     => true,
                'show_line_items'      => true,
                'cancel_url'           => PAYMONGO_FAILED_URL,
                // Cart checkouts do NOT pass order_id in success_url —
                // user_pay_response.php detects the cart via $_SESSION['cart_booking_ids']
                'success_url'          => PAYMONGO_SUCCESS_URL,
                'description'          => $description,
                'reference_number'     => $cartRef,
                'payment_method_types' => ['gcash', 'card', 'paymaya', 'grab_pay'],
                'line_items'           => $line_items,
            ],
        ],
    ];

    $response = callPaymongoAPI('/checkout_sessions', $payload);

    if (isset($response['data']['attributes']['checkout_url'])) {
        return [
            'checkout_url' => $response['data']['attributes']['checkout_url'],
            'session_id'   => $response['data']['id'],
        ];
    }

    return ['error' => $response['errors'][0]['detail'] ?? 'Unknown PayMongo error'];
}


/**
 * Retrieves a Checkout Session by ID to verify payment status.
 */
function getPaymongoCheckoutSession($sessionId) {
    return callPaymongoAPI('/checkout_sessions/' . $sessionId, null, 'GET');
}


/**
 * Issues a refund for a given PayMongo payment ID.
 *
 * @param string $paymentId  PayMongo payment ID (pay_xxxx)
 * @param float  $amountPHP  Amount to refund in PHP
 * @param string $reason     'duplicate', 'fraudulent', or 'others'
 */
function createPaymongoRefund($paymentId, $amountPHP, $reason = 'others') {
    $amountCentavos = (int) round($amountPHP * 100);

    $payload = [
        'data' => [
            'attributes' => [
                'amount'     => $amountCentavos,
                'payment_id' => $paymentId,
                'reason'     => $reason,
            ],
        ],
    ];

    return callPaymongoAPI('/refunds', $payload);
}


/**
 * Core API caller for PayMongo.
 *
 * @param string     $endpoint  e.g. '/checkout_sessions'
 * @param array|null $payload   POST body (null for GET)
 * @param string     $method    'POST' or 'GET'
 */
function callPaymongoAPI($endpoint, $payload = null, $method = 'POST') {
    $url = PAYMONGO_API_URL . $endpoint;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: ' . getPaymongoAuthHeader(),
    ]);

    if ($method === 'POST' && $payload !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    } elseif ($method === 'GET') {
        curl_setopt($ch, CURLOPT_HTTPGET, true);
    }

    $jsonResponse = curl_exec($ch);
    curl_close($ch);

    return json_decode($jsonResponse, true) ?? [];
}