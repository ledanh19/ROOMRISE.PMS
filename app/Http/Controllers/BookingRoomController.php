<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\BookingRoom;
use Illuminate\Http\Request;

class BookingRoomController extends Controller
{
    public function assignRoomUnit(Request $request, BookingRoom $bookingRoom)
    {
        $validated = $request->validate([
            'room_unit_id' => 'nullable|exists:room_units,id',
        ]);

        if (!$validated['room_unit_id']) {
            $bookingRoom->room_unit_id = null;
            $bookingRoom->save();
            return redirect()->back();
        }

        $available = Helper::checkRoomUnitAvailable(
            $validated['room_unit_id'],
            $bookingRoom->check_in_date,
            $bookingRoom->check_in_time,
            $bookingRoom->check_out_date,
            $bookingRoom->check_out_time,
            $bookingRoom->booking_id
        );
        if (!$available) {
            return back()->withErrors([
                'msg' => 'Phòng này đã có người đặt trong khoảng thời gian đó.',
            ]);
        }

        $bookingRoom->room_unit_id = $validated['room_unit_id'];
        $bookingRoom->save();

        return redirect()->back();
    }
}
