<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
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
            'address' => $this->address,
            'contact_number' => $this->contact_number,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'coordinates' => [
                (float) $this->latitude,
                (float) $this->longitude
            ],
            'google_maps_link' => "https://www.google.com/maps/search/?api=1&query={$this->latitude},{$this->longitude}",
            'created_at' => $this->created_at->diffForHumans(),
        ];
    }
}
