<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\ApiController;
use App\Models\Notification;
use App\Models\NotificationUser;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    /**
     * @OA\Post(
     *     security={{"bearerAuth":{}}},
     *     path="/api/v1/notifications",
     *     tags={"Notifications"},
     *     summary="Get Unread Notifications",
     *     description="Returns a list of all unread Notifications.",
     *     @OA\Response(
     *         response=200,
     *         description="Successful Operation",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function all(Request $request)
    {
        if (!$request->has('user')) {
            return $this->result('', 'User is not exist', 400);
        }

        $unreadNotifications = Notification::leftJoin('notification_user', function ($join) use ($request) {
            $join->on('notifications.id', '=', 'notification_user.notification_id')
                ->where('notification_user.user_id', '=', $request->user->id);
        })
            ->whereNull('notification_user.read_at')
            ->orWhereNull('notification_user.user_id')
            ->select('notifications.*')
            ->orderBy('notifications.created_at', 'desc')
            ->get();

        return $this->result($unreadNotifications);
    }

    public function getById(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:notifications,id',
        ]);

        $notification = Notification::find($request->input('id'));

        if (!$notification) {
            return $this->result('', 'Notification not found', 404);
        }

        return $this->result($notification);
    }

    public function markAsRead(Request $request)
    {
        if (!$request->has('user')) {
            return $this->result('', 'User is not exist', 400);
        }

        $request->validate([
            'id' => 'required|integer|exists:notifications,id',
        ]);

        $notificationUser = NotificationUser::where('notification_id', $request->input('id'))
            ->where('user_id', $request->user->id)
            ->first();

        if ($notificationUser) {
            $notificationUser->update(['read_at' => now()]);
        } else {
            NotificationUser::create([
                'notification_id' => $request->input('id'),
                'user_id' => $request->user->id,
                'read_at' => now(),
            ]);
        }

        return $this->result('Notification marked as read.');
    }

    public function markAsReadAll(Request $request)
    {
        if (!$request->has('user')) {
            return $this->result('', 'User is not exist', 400);
        }

        $user = $request->user;

        $unreadNotifications = Notification::leftJoin('notification_user', function ($join) use ($user) {
            $join->on('notifications.id', '=', 'notification_user.notification_id')
                ->where('notification_user.user_id', '=', $user->id);
        })
            ->whereNull('notification_user.read_at')
            ->orWhereNull('notification_user.user_id')
            ->select('notifications.*')
            ->get();

        foreach ($unreadNotifications as $notification) {
            NotificationUser::updateOrCreate(
                [
                    'notification_id' => $notification->id,
                    'user_id' => $user->id,
                ],
                ['read_at' => now()]
            );
        }

        return $this->result('All notifications marked as read.');
    }
}
