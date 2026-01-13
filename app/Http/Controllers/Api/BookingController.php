<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class BookingController extends Controller
{
    
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Booking::with(['vehicle.branch', 'user']);

        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        } else {
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
        }

        $query->latest();

        return BookingResource::collection($query->paginate(10));
    }

    public function show(Request $request,$id)
    {
        $booking = Booking::with(['vehicle', 'user', 'payment'])->findOrFail($id);

        if ($request->user()->role !== 'admin' && $booking->user_id !== $request->user()->id) {
            return response()->json(['mesasage' => ' Unauthorized'], 403);
        }

        return new BookingResource($booking);
    }

    public function store(Request $request)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'notes' => 'nullable|string'
        ]);

        $vehicle = Vehicle::findOrFail($request->vehicle_id);

        return DB::transaction(function () use ($request, $vehicle) {
            $isBooked = Booking::where('vehicle_id', $vehicle->id)
                ->whereIn('status', ['pending', 'approved', 'paid', 'on_rent'])
                ->where(function ($query) use ($request) {
                    $query->whereBetween('start_date', [$request->start_date, $request->end_date])
                          ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                          ->orWhere(function ($q) use ($request) {
                              $q->where('start_date', '<', $request->start_date)
                                ->where('end_date', '>', $request->end_date);
                          });
                })->exists();
            
                if ($isBooked) {
                    return response()->json([
                        'message' => 'Kendaraan tidak tersedia pada tanggal tersebut.',
                        'errors' => ['dates' => 'Tanggal bentrok dengan pemesanan lain']
                        ], 422);
                }
                
                $start = \Carbon\Carbon::parse($request->start_date);
                $end = \Carbon\Carbon::parse($request->end_date);

                $days = $start->diffInDays($end) ?: 1;

                $totalPrice = $days * $vehicle->price_per_day;

                $booking = Booking::create([
                    'user_id' => Auth::id(),
                    'vehicle_id' => $vehicle->id,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'total_price' => $totalPrice,
                    'notes' => $request->notes ?? null,
                    'status' => 'pending'
                ]);
        });

        $booking->load('vehicle');

        return response()->json([
            'message' => 'Booking berhasil dibuat. Menunggu konfirmasi admin.',
            'data' => new BookingResource($booking)
        ], 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected,on_rent,finished,cancelled'
        ]);

        $booking = Booking::findOrFail($id);

        $booking->status = $request->status;
        $booking->save();

        return response()->json([
            'message' => 'Status booking berhasil diperbahui menjadi ' . $request->status,
            'data' => new BookingResource($booking)
        ]);
    }
}
