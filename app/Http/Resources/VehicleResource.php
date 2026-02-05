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
        $activeBooking = $this->bookings->whereIn('status', ['pending', 'approved', 'paid', 'on_rent'])->where('end_date', '>', now())->sortbyDesc('end_date')->first();

        $availabilityStatus = 'Available';
        $availableIn = null;

        if ($activeBooking) {
            $endDate = Carbon::parse($activeBooking->end_date);
            $availabilityStatus = 'Booked';
            $availableIn = $endDate->diffForHumans(now(), [
                'parts' => 2,
                'join' => ' ',
                'syntax' => Carbon::DIFF_ABSOLUTE
            ]);
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
            'branch' => $this->whenLoaded('branch', function () {
                return [
                    'id' =>$this->branch->id,
                    'name' => $this->branch->name,
                    'address' => $this->branch->address,
                ];
            }),
            'status' => $availabilityStatus,
            'available_estimation' => $availableIn ? "Tersedia dalam {$availableIn}" : "Tersedia Sekarang",
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
