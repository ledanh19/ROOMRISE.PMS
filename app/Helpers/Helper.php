<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\Menu;
use Spatie\Permission\Models\Role;


class Helper
{
    public static function getMenuAdminPanel(): array
    {
        $user = Auth::user();
        $roleId = $user->roles()->value('id');

        $menus = Menu::with(['roles', 'children.roles'])
            ->whereNull('parent_id')
            ->whereHas('roles', fn($q) => $q->where('role_id', $roleId))
            ->orderBy('order')
            ->get();

        $menus->each(function ($menu) use ($roleId) {
            $menu->setRelation(
                'children',
                $menu->children->filter(fn($child) => $child->roles->pluck('id')->contains($roleId))
            );
        });

        return $menus
            ->map(fn($menu) => self::formatMenu($menu))
            ->all();
    }

    /**
     * Format một menu thành mảng
     *
     * @param Menu $menu
     * @return array
     */
    private static function formatMenu(Menu $menu): array
    {
        $children = $menu->children
            ->map(fn($child) => self::formatMenu($child))
            ->values()
            ->all();

        $data = [
            'name' => $menu->name,
        ];

        if ($menu->link) $data['link'] = $menu->link;
        if ($menu->is_heading) $data['heading'] = $menu->name;
        if ($menu->image) $data['icon'] = $menu->image;
        if ($menu->image_active) $data['icon_active'] = $menu->image_active;
        if ($menu->menu_key) $data['menu_key'] = $menu->menu_key;
        if (!empty($children)) {
            $data['children'] = $children;
        }
        return $data;
    }

    /**
     * Kiểm tra phòng đơn vị có bị trùng lịch không trong khoảng thời gian cho trước
     *
     * @param int $roomUnitId
     * @param string $checkInDate (YYYY-MM-DD)
     * @param string $checkInTime (HH:ii)
     * @param string $checkOutDate (YYYY-MM-DD)
     * @param string $checkOutTime (HH:ii)
     * @param int|null $ignoreBookingId (nếu là update, bỏ qua booking này)
     * @return bool true nếu phòng còn trống, false nếu bị trùng
     */
    public static function checkRoomUnitAvailable($roomUnitId, $checkInDate, $checkInTime, $checkOutDate, $checkOutTime, $ignoreBookingId = null)
    {
        $start = \Carbon\Carbon::parse($checkInDate . ' ' . $checkInTime);
        $end = \Carbon\Carbon::parse($checkOutDate . ' ' . $checkOutTime);

        $conflict = \App\Models\BookingRoom::where('room_unit_id', $roomUnitId)
            ->whereHas('booking', function ($q) use ($ignoreBookingId) {
                if ($ignoreBookingId) {
                    $q->where('id', '!=', $ignoreBookingId);
                }
                $q->where('status', '!=', 'Hủy');
            })
            ->where(function ($q) use ($start, $end) {
                $q->where(function ($q) use ($start, $end) {
                    $q->whereDate('check_in_date', '<', $end->toDateString())
                        ->whereDate('check_out_date', '>', $start->toDateString());
                });
            })
            ->exists();

        return !$conflict;
    }

    /**
     * Gộp các update có cùng date, property_id, rate_plan_id thành 1 object
     * @param array $updates
     * @return array
     */
    public static function mergeRateRestrictionOtaUpdates(array $updates): array
    {
        $merged = [];

        foreach ($updates as $update) {
            // Tạo key duy nhất cho mỗi nhóm
            $key = $update['date'] . '|' . $update['property_id'] . '|' . $update['rate_plan_id'];

            if (!isset($merged[$key])) {
                $merged[$key] = $update;
            } else {
                // Merge các trường lại (ưu tiên trường mới nếu trùng)
                $merged[$key] = array_merge($merged[$key], $update);
            }
        }

        // Trả về dạng array values
        return array_values($merged);
    }


    public static function optimizeOtaUpdates(array $updates): array
    {
        // Bước 1: Merge theo key (date, property_id, rate_plan_id)
        $merged = [];
        foreach ($updates as $update) {
            $key = $update['date'] . '|' . $update['property_id'] . '|' . $update['rate_plan_id'];
            if (!isset($merged[$key])) {
                $merged[$key] = $update;
            } else {
                $merged[$key] = array_merge($merged[$key], $update);
            }
        }

        // Bước 2: Nhóm theo property_id, rate_plan_id, các trường update giống nhau (trừ date)
        $grouped = [];
        foreach ($merged as $item) {
            $groupKeyFields = $item;
            unset($groupKeyFields['date']);
            $groupKey = md5(json_encode($groupKeyFields));
            $grouped[$groupKey]['fields'] = $groupKeyFields;
            $grouped[$groupKey]['dates'][] = $item['date'];
        }

        // Bước 3: Với mỗi nhóm, sort và gộp các ngày liên tiếp
        $result = [];
        foreach ($grouped as $group) {
            $dates = $group['dates'];
            sort($dates);
            $start = $end = null;
            foreach ($dates as $i => $date) {
                if ($start === null) {
                    $start = $end = $date;
                } elseif (date('Y-m-d', strtotime($end . ' +1 day')) === $date) {
                    $end = $date;
                } else {
                    // Gộp thành 1 object
                    $fields = $group['fields'];
                    if ($start === $end) {
                        $fields['date'] = $start;
                    } else {
                        $fields['date_from'] = $start;
                        $fields['date_to'] = $end;
                    }
                    $result[] = $fields;
                    $start = $end = $date;
                }
            }
            // Gộp lần cuối
            if ($start !== null) {
                $fields = $group['fields'];
                if ($start === $end) {
                    $fields['date'] = $start;
                } else {
                    $fields['date_from'] = $start;
                    $fields['date_to'] = $end;
                }
                $result[] = $fields;
            }
        }
        return $result;
    }

    /**
     * Tối ưu payload updateAvailability cho Channex
     * Gộp các ngày liên tiếp, cùng property_id, room_type_id, availability thành 1 object với date_from, date_to
     * @param array $updates (mỗi phần tử: ['property_id', 'room_type_id', 'date', 'availability'])
     * @return array
     */
    public static function optimizeAvailabilityPayload(array $updates): array
    {
        // Nhóm theo property_id, room_type_id, availability
        $grouped = [];
        foreach ($updates as $item) {
            $key = $item['property_id'] . '|' . $item['room_type_id'] . '|' . $item['availability'];
            $grouped[$key]['fields'] = [
                'property_id' => $item['property_id'],
                'room_type_id' => $item['room_type_id'],
                'availability' => $item['availability'],
            ];
            $grouped[$key]['dates'][] = $item['date'];
        }

        // Gộp các ngày liên tiếp
        $result = [];
        foreach ($grouped as $group) {
            $dates = $group['dates'];
            sort($dates);
            $start = $end = null;
            foreach ($dates as $date) {
                if ($start === null) {
                    $start = $end = $date;
                } elseif (date('Y-m-d', strtotime($end . ' +1 day')) === $date) {
                    $end = $date;
                } else {
                    // Gộp thành 1 object
                    $fields = $group['fields'];
                    if ($start === $end) {
                        $fields['date'] = $start;
                    } else {
                        $fields['date_from'] = $start;
                        $fields['date_to'] = $end;
                    }
                    $result[] = $fields;
                    $start = $end = $date;
                }
            }
            // Gộp lần cuối
            if ($start !== null) {
                $fields = $group['fields'];
                if ($start === $end) {
                    $fields['date'] = $start;
                } else {
                    $fields['date_from'] = $start;
                    $fields['date_to'] = $end;
                }
                $result[] = $fields;
            }
        }
        return $result;
    }
}
