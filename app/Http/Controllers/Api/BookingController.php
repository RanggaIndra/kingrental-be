<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Vehicle;
use App\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use App\Services\MidtransService;
use Illuminate\Support\Facades\Log as FacadesLog;

class BookingController extends Controller
{   
    protected $midtransService;

    public function __construct(MidtransService $midtransService)
    {
        $this->midtransService = $midtransService;
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $query = Booking::with(['vehicle.branch', 'user']);

        match ($user->role) {
            Role::CUSTOMER => $query->where('user_id', $user->id),

            Role::BRANCH_ADMIN => $query->whereHas('vehicle', function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            }),

            Role::SUPER_ADMIN => null,
        };

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return BookingResource::collection($query->latest()->paginate(10));
    }

    public function show(Request $request,$id)
    {
        $booking = Booking::with(['vehicle', 'user', 'payment'])->findOrFail($id);
        $user = $request->user();

        $isAuthorized = match($user->role) {
            Role::SUPER_ADMIN => true,
            Role::BRANCH_ADMIN => $booking->vehicle->branch_id == $user->branch_id,
            Role::CUSTOMER => $booking->user_id == $user->id,
        };

        if (!$isAuthorized) {
            return response()->json(['message' => 'Unauthorized'], 403);
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

        $result = DB::transaction(function () use ($request) {
            $vehicle = Vehicle::lockForUpdate()->find($request->vehicle_id);

            if (!$vehicle->is_available) {
                return response()->json([
                    'message' => 'Kendaraan sedang tidak aktif/dalam perbaikan.'
                ], 422);
            }

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

                try {
                    $snapToken = $this->midtransService->getSnapToken($booking);
                    $booking->snap_token = $snapToken;
                    $booking->save();
                } catch (\Exception $e) {
                    FacadesLog::error($e->getMessage());
                }

                return $booking;
        });

        if ($result instanceof \Illuminate\Http\JsonResponse) {
            return $result;
        }

        $booking = $result;

        $booking->load('vehicle');

        return response()->json([
            'message' => 'Booking berhasil dibuat. Menunggu konfirmasi admin.',
            'data' => new BookingResource($booking),
            'snap_token' => $result->snap_token
        ], 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected,on_rent,finished,cancelled'
        ]);

        $booking = Booking::findOrFail($id);
        $user = $request->user();

        if ($user->role === Role::BRANCH_ADMIN && $booking->vehicle->branch_id !== $user->branch_id) {
             return response()->json(['message' => 'Unauthorized action for this branch'], 403);
        }

        $booking->status = $request->status;
        $booking->save();

        return response()->json([
            'message' => 'Status booking berhasil diperbahui menjadi ' . $request->status,
            'data' => new BookingResource($booking)
        ]);
    }
}
