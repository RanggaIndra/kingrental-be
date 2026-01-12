<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'phone' => $this->user->phone,
                ];  
            }),
            'vehicle' => new VehicleResource($this->whenLoaded('vehicle')),
            'start_date' => $this->start_date instanceof \DateTime ? $this->start_date->format('Y-m-d H:i') : $this->start_date,
            'end_date' => $this->end_date instanceof \DateTime ? $this->end_date->format('Y-m-d H:i') : $this->end_date,
            'duration_days' => \Carbon\Carbon::parse($this->start_date)->diffInDays(\Carbon\Carbon::parse($this->end_date)) ?: 1,
            'total_price' => (float) $this->total_price,
            'formatted_total_price' => 'Rp ' . number_format($this->total_price, 0, ',', '.'),
            'status' => $this->status,
            'status_label' => ucfirst($this->status),
            'notes' => $this->notes,
            'created_at' => $this->created_at->diffForHumans(),
        ];
    }
}
