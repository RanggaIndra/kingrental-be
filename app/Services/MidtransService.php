<?php

namespace App\Services;

use Midtrans\Snap;
use Midtrans\Config;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = config('services.midtrans.is_production');
        Config::$isSanitized = config('services.midtrans.is_sanitized');
        Config::$is3ds = config('services.midtrans.is_3ds');
    }

    public function getSnapToken($booking)
    {
        $params = [
            'transaction_details' => [
                'order_id' => $booking->id . '-' . rand(),
                'gross_amount' => (int) $booking->total_price,
            ],
            'customer_details' => [
                'first_name' => $booking->user->name,
                'email' => $booking->user->email,
                'phone' => $booking->user->phone,
            ],
            'item_details' => [
                [
                    'id' => $booking->vehicle->id,
                    'price' => (int) $booking->total_price,
                    'quantity' => 1,
                    'name' => $booking->vehicle->name . ' (' . $booking->days . ' Hari)',
                ]
            ],
        ];

        return Snap::getSnapToken($params);
    }
}