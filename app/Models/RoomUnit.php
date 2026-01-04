<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomUnit extends Model
{
    protected $fillable = ['room_id', 'name', 'status', 'note'];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
