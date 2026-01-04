<?php

namespace App\Policies;

use App\Models\RoomUnit;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RoomUnitPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('view-phong');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\RoomUnit  $roomUnit
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, RoomUnit $roomUnit)
    {
        return $user->can('view-phong');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('create-phong');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\RoomUnit  $roomUnit
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, RoomUnit $roomUnit)
    {
        return $user->can('update-phong');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\RoomUnit  $roomUnit
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, RoomUnit $roomUnit)
    {
        return $user->can('delete-phong');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\RoomUnit  $roomUnit
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, RoomUnit $roomUnit)
    {
        return $user->can('update-phong');
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\RoomUnit  $roomUnit
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, RoomUnit $roomUnit)
    {
        return $user->can('delete-phong');
    }
}
