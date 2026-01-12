<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class VehicleResource extends JsonResource
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
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
