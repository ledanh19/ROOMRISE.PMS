<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomAvailabilitySummary extends Model
{
    use HasFactory;

    protected $table = 'room_availability_summaries';

    protected $fillable = [
        'room_id',
        'total_rooms',
        'available_rooms',
    ];

    protected $casts = [
        'room_id'         => 'integer',
        'total_rooms'     => 'integer',
        'available_rooms' => 'integer',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];


    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function getOccupiedRoomsAttribute(): int
    {
        $total = (int) ($this->total_rooms ?? 0);
        $avail = (int) ($this->available_rooms ?? 0);
        return max($total - $avail, 0);
    }

    public function setAvailableRoomsAttribute($value): void
    {
        $total = (int) ($this->attributes['total_rooms'] ?? 0);
        $val   = (int) $value;

        $this->attributes['available_rooms'] = max(min($val, $total), 0);
    }

    public function scopeForRoom($query, int $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    public function scopeLatestForRoom($query, int $roomId)
    {
        return $query->forRoom($roomId)->latest('created_at');
    }
}
