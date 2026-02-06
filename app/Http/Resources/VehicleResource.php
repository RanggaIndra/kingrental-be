<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class VehicleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {   
        $now = Carbon::now('Asia/Jakarta');

        $rawBookings = $this->relationLoaded('bookings') ? $this->bookings->whereIn('status', ['pending', 'approved', 'paid', 'on_rent']) : collect([]);

        $availabilityStatus = 'Available';
        $availableIn = 'Tersedia Sekarang';

        if ($rawBookings->isNotEmpty()) {
            $currentBooking = $rawBookings->first(function($booking) use ($now) {
                $start = Carbon::parse($booking->start_date)->startOfDay();
                $end = Carbon::parse($booking->start_date)->endOfDay();
                return $now->between($start, $end);
            });

            if ($currentBooking) {
                $availabilityStatus = 'Booked';

                $endDate = Carbon::parse($currentBooking->end_date);
                $diff = $now->diffInDays($endDate, false);
                $diff < 0 ? 0 : round($diff);

                $availableIn = $diff <= 0 ? "Tersedia Besok" : "Tersedia dalam {$diff} Hari";
            } else {
                $nextBooking = $rawBookings->filter(function($booking) use ($now) {
                    return Carbon::parse($booking->start_date->startOfDay()->gt($now));
                })->sortBy('start_date')->first();

                if ($nextBooking) {
                    $nextStart = Carbon::parse($nextBooking->start_date);
                    $daysFree = $now->diffInDays($nextStart);

                    if ($daysFree > 0) {
                        $availableIn = "Tersedia selama {$daysFree} Hari";
                    } else {
                        $availableIn = "Tersedia < 1 Hari";
                    }
                }
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'license_plate' => $this->when($request->user()?->role === 'admin', $this->license_plate),
            'transmission' => $this->capacity,
            'price_per_day' => 'Rp ' . number_format($this->price_per_day, 0, ',', '.'),
            'is_available' => (bool) $this->is_available,
            'image_url' =>$this->image_url ? Storage::url($this->image_url) : null,
            'description' => $this->description,
            'bookings' => $this->whenLoaded('bookings'),
            'branch' => $this->whenLoaded('branch', function () {
                return [
                    'id' =>$this->branch->id,
                    'name' => $this->branch->name,
                    'address' => $this->branch->address,
                ];
            }),
            'status' => $availabilityStatus,
            'available_estimation' => $availableIn,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
