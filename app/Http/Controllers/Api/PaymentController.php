<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use function Symfony\Component\Clock\now;

class PaymentController extends Controller
{
    public function getPaymentToken(Request $request, $bookingId)
    {
        $booking = Booking::with(['user', 'vehicle'])->findOrFail($bookingId);

        if ($request->user()->id !== $booking->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($booking->status !== 'pending') {
            return response()->json(['message' => 'Booking ini tidak perlu dibayar lagi.'], 422);
        }

        $midtrans = new MidtransService();
        $snapToken = $midtrans->getSnapToken($booking);

        return response()->json([
            'snap_token' => $snapToken,
            'client_key' => config('services.midtrans.client_key')
        ]);
    }

    public function webhook (Request $request)
    {
        $serverKey = config('services.midtrans.server_key');
        $hashed = hash('sha512', $request->order_id . $request->status_code . $request->gross_amount . $serverKey);

        if ($hashed !== $request->signature_key) {
            return response()->json(['message' => 'Invalid Signature'], 403);
        }

        $transactionStatus = $request->transaction_status;
        $orderIdParts = explode('-', $request->order_id);
        $bookingId = $orderIdParts[0];

        $booking = Booking::find($bookingId);
        if (!$booking) return response()->json(['message' => 'Booking not found'], 404);

        if ($booking->status === 'paid' && ($transactionStatus == 'capture' || $transactionStatus == 'settlement')) {
            return response()->json(['message' => 'Payment already processed']);
        }

        DB::transaction(function () use ($booking, $transactionStatus, $request) {
            if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
                $booking->update(['status' => 'paid']);

                Payment::firstOrCreate([
                    'booking_id' => $booking->id,
                ],
                [
                    'payment_method' => $request->payment_type,
                    'payment_proof' => 'midtrans_automatic',
                    'payment_date' => now(),
                    'status' => 'verified'
                ]);
            } else if (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
                $booking->update(['status' => 'cancelled']);
            }
        });
        

        return response()->json(['message' => 'Webhook received']);
    }
}
