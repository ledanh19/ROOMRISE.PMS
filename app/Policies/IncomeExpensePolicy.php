<?php

namespace App\Policies;

use App\Models\IncomeExpense;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class IncomeExpensePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user)
    {
        return $user->can('view-quan-ly-thu-chi');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, IncomeExpense $incomeExpense)
    {
        return $user->can('view-quan-ly-thu-chi');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user)
    {
        return $user->can('create-quan-ly-thu-chi');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, IncomeExpense $incomeExpense)
    {
        return $user->can('update-quan-ly-thu-chi');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, IncomeExpense $incomeExpense)
    {
        return $user->can('delete-quan-ly-thu-chi');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, IncomeExpense $incomeExpense)
    {
        return $user->can('update-quan-ly-thu-chi');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, IncomeExpense $incomeExpense)
    {
        return $user->can('delete-quan-ly-thu-chi');
    }

    /**
     * Quyền Hotel Collect: Xem danh sách
     */
    public function viewHotelCollect(User $user)
    {
        return $user->can('view-hotel-collect');
    }

    /**
     * Quyền Hotel Collect: Tạo mới
     */
    public function createHotelCollect(User $user)
    {
        return $user->can('create-hotel-collect');
    }

    /**
     * Quyền Hotel Collect: Cập nhật
     */
    public function updateHotelCollect(User $user, IncomeExpense $incomeExpense)
    {
        return $user->can('update-hotel-collect');
    }

    /**
     * Quyền Hotel Collect: Xóa
     */
    public function deleteHotelCollect(User $user, IncomeExpense $incomeExpense)
    {
        return $user->can('delete-hotel-collect');
    }

    /**
     * Quyền OTA Collect: Xem danh sách
     */
    public function viewOtaCollect(User $user)
    {
        return $user->can('view-ota-collect');
    }

    /**
     * Quyền OTA Collect: Tạo mới
     */
    public function createOtaCollect(User $user)
    {
        return $user->can('create-ota-collect');
    }

    /**
     * Quyền OTA Collect: Cập nhật
     */
    public function updateOtaCollect(User $user, IncomeExpense $incomeExpense)
    {
        return $user->can('update-ota-collect');
    }

    /**
     * Quyền OTA Collect: Xóa
     */
    public function deleteOtaCollect(User $user, IncomeExpense $incomeExpense)
    {
        return $user->can('delete-ota-collect');
    }

    /**
     * Quyền Công Nợ: Xem danh sách
     */
    public function viewCongNo(User $user)
    {
        return $user->can('view-cong-no');
    }

    /**
     * Quyền Công Nợ: Tạo mới
     */
    public function createCongNo(User $user)
    {
        return $user->can('create-cong-no');
    }

    /**
     * Quyền Công Nợ: Cập nhật
     */
    public function updateCongNo(User $user, IncomeExpense $incomeExpense)
    {
        return $user->can('update-cong-no');
    }

    /**
     * Quyền Công Nợ: Xóa
     */
    public function deleteCongNo(User $user, IncomeExpense $incomeExpense)
    {
        return $user->can('delete-cong-no');
    }
}
