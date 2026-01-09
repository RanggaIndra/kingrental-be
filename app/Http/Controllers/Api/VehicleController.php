<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('vehicles')->join('branches', 'vehicles.branch_id', '=', 'branches.id')
            ->select(
                'vehicles.id',
                'vehicles.name as vehicle_name',
                'vehicles.type',
                'vehicles.price_per_day',
                'vehicles.image_url',
                'vehicles.transmission',
                'branches.name as branch_name',
                'branches.address as branch_address'
        )->where('vehicles.is_available', true);

        if ($request->has('type')) {
            $query->where('vehicles.type', $request->type);
        }

        if ($request->has('search')) {
            $query->where('vehicles.name', 'like', '%' . $request->search . '%');
        }

        $vehicles = $query->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $vehicles
        ]);
    }

    public function show($id) {
        $vehicle = DB::table('vehicles')->join('branches', 'vehicles.branch_id', '=', 'branches.id')->where('vehicles.id', $id)->firdst();

        if (!$vehicle) {
            return response()->json(['message' => 'Vehicle not found'], 404);
        }
        
        return response()->json(['data' => $vehicle]);
    }

}
