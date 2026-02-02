<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $guarded = ['id'];

    protected $fillable = [
        'user_id',
        'vehicle_id',
        'start_date',
        'end_date',
        'days',
        'total_price',
        'status',
        'snap_token',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function vehicle() {
        return $this->belongsTo(Vehicle::class);
    }

    public function payment() {
        return $this->hasOne(Payment::class);
    }
}
