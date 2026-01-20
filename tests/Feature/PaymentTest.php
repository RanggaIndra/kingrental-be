<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $mock = Mockery::mock('alias:Midtrans\Snap');
        $mock->shouldReceive('getSnapToken')->andReturn('dummy_snap_token_123');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function user_cannot_pay_others_booking()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $vehicle = Vehicle::factory()->create();
        $booking = Booking::create([
            'user_id' => $userB->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => now(),
            'end_date' => now()->addDays(2),
            'total_price' => 500000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($userA)->getJson('/api/payments/{$booking->id}/token');
        
        $response->assertStatus(403)->assertJson(['message' => 'Unauthorized']);
    }

    /** @test */
    public function user_can_get_token_for_own_booking()
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $booking = Booking::create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => now(),
            'end_date' => now()->addDays(1),
            'total_price' => 200000,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($user)->getJson('/api/payments/{$booking->id}/token');

        $response->assertStatus(200)->assertJsonStructure(['snap_token', 'client_key'])->assertJsonFragment(['snap_token' => 'dummy_snap_token_123']);
    }

    /** @test */
    public function cannot_pay_paid_booking()
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $booking = Booking::create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => now(),
            'end_date' => now()->addDays(1),
            'total_price' => 200000,
            'status' => 'paid'
        ]);

        $response = $this->actingAs($user)->getJson('/api/payments/{$booking->id}/token');

        $response->assertStatus(422)->assertJson(['message' => 'Booking ini tidak perlu dibaar lagi.']);
    }

    /** @test */
    public function webhook_updates_booking_status_to_paid()
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $booking = Booking::create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'total_price' => 100000,
            'status' => 'pending'
        ]);

        $serverKey = config('services.midtrans.server_key');
        $orderId = $booking->id . '-12345';
        $statusCode = '200';
        $grossAmount = '100000.00';

        $signatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        $payload = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signatureKey,
            'transaction_status' => 'settlement',
            'payment_type' => 'bank_transfer',
            'transaction_id' => 'trans-123'
        ];

        // Hit Endpoint Webhook
        $response = $this->postJson("/api/payments/webhook", $payload);

        // Assertions
        $response->assertStatus(200);
        
        // Cek database apakah status booking berubah
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'paid'
        ]);

        // Cek apakah data payment masuk
        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'payment_method' => 'bank_transfer',
            'status' => 'verified'
        ]);
    }
}
