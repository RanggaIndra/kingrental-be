<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use App\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;


class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $query = Vehicle::with('branch');

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('branch)id')) {
            $query->where('branch_id', $request->branch_id);
        }
        
        if ($request->has('transmission')) {
            $query->where('transmission', $request->transmission);
        }

        if ($request->has(['start_date', 'end_date'])) {
            $start = $request->start_date;
            $end = $request->end_date;

            $query->whereDoesntHave('bookings', function (Builder $q) use ($start, $end) {
                $q->whereIn('status', ['pending', 'approved', 'paid', 'on_rent'])
                ->where(function ($subQ) use ($start, $end) {
                    $subQ->whereBetween('start_date', [$start, $end])
                        ->orWhereBetween('end_date', [$start, $end])
                        ->orWhere(function ($overlap) use ($start, $end) {
                            $overlap->where('start_date', '<', $start)
                                    ->where('end_date', '>', $end);
                        });
                });
            });
        };

        if ($request->has('sort_by_price')) {
            $query->orderBy('price_per_day', $request->sort_by_price === 'desc' ? 'desc' : 'asc');
        } else {
            $query->latest();
        }

        return VehicleResource::collection($query->paginate(10));
    }

    public function show($id) {
        $vehicle = Vehicle::with(['branch', 'bookings' => function($q) {
            $q->select('vehicle_id', 'start_date', 'end_date')
            ->whereIn('status', ['approved', 'paid', 'on_rent'])
            ->where('end_date', '>=', now());
        }])->findOrFail($id);

        return new VehicleResource($vehicle);
    }

    public function store(Request $request)
    {
        $user = $request->user();
    
        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required|in:car,bike',
            'license_plate' => 'required|unique:vehicles,license_plate',
            'transmission' => 'required|in:manual,automatic',
            'capacity' => 'required|integer|min:1',
            'price_per_day' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'description' => 'nullable|string',
        ];

        if ($user->role === Role::BRANCH_ADMIN) {
            $request->merge(['branch_id' => $user->branch_id]);
        } else {
            $rules['branch_id'] = 'required|exists:branches,id';
        }

        $validated = $request->validate($rules);

        if ($user->role === Role::BRANCH_ADMIN) {
            $validated['branch_id'] = $user->branch_id;
        }

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('vehicles', 'public');
            $validated['image_url'] = $path;
        }

        unset($validated['image']);
        
        $vehicle = Vehicle::create($validated);

        return response()->json([
            'message' => 'Kendaraan berhasil ditambahkan',
            'data' => new VehicleResource($vehicle)
        ], 201);
    }
    
    public function update(Request $request, $id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $user = $request->user();

        if ($user->role === Role::BRANCH_ADMIN && $vehicle->branch_id !== $user->branch_id) {
            return response()->json(['message' => 'Unauthorized access to this vehicle'], 403);
        }

        $validated = $request->validate([
            'branch_id' => 'sometimes|exists:branches,id',
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:car,bike',
            'license_plate' => 'sometimes|unique:vehicles,license_plate' . $vehicle->id,
            'transmission' => 'sometimes|in:manual,automatic',
            'capacity' => 'sometimes|integer',
            'price_per_day' => 'sometimes|numeric',
            'is_available' => 'boolean',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'description' => 'nullable|string',
        ]);

        if ($user->role === Role::BRANCH_ADMIN) {
            unset($validated['branch_id']);
        }

        if ($request->hasFile('image')) {
            if ($vehicle->image_url && Storage::disk('public')->exists($vehicle->image_url)) {
                Storage::disk('public')->delete($vehicle->image_url);
            }

            $path = $request->file('image')->store('vehicles', 'public');
            $validated['image_url'] = $path;
        }
            
        unset($validated['image']);

        $vehicle->update($validated);

        return response()->json([
            'message' => 'Kendaraan berhasil diperbarui',
            'data' => new VehicleResource($vehicle)
        ], 200);
    }
        
    public function destroy (Request $request, $id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $user = $request->user();

        if ($user->role === Role::BRANCH_ADMIN && $vehicle->branch_id !== $user->branch_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($vehicle->image_url && Storage::disk('public')->exists($vehicle->image_url)) {
            Storage::disk('public')->delete($vehicle->image_url);
        }

        $vehicle->delete();

        return response()->json([
            'message' => 'Kendaraan berhasil dihapus'
        ], 200);
    }
}
