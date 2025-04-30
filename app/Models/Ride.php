<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ride extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'driver_id',
        'departure_location',
        'destination',
        'departure_time',
        'available_seats',
        'is_archived',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'departure_time' => 'datetime',
        'available_seats' => 'integer',
        'is_archived' => 'boolean',
    ];

    /**
     * Get the driver (user) that owns the ride.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}