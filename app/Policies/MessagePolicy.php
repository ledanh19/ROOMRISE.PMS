<?php

namespace App\Policies;

use App\Models\User;

class MessagePolicy
{
    /**
     * Xem tin nhắn
     */
    public function view(User $user)
    {
        return $user->can('view-tin-nhan');
    }

    /**
     * Tạo tin nhắn
     */
    public function create(User $user)
    {
        return $user->can('create-tin-nhan');
    }

    /**
     * Cập nhật tin nhắn
     */
    public function update(User $user)
    {
        return $user->can('update-tin-nhan');
    }

    /**
     * Xóa tin nhắn
     */
    public function delete(User $user)
    {
        return $user->can('delete-tin-nhan');
    }
}
