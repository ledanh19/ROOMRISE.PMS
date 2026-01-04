<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\IncomeExpense;
use App\Models\Inventory;
use App\Models\Menu;
use App\Models\Message;
use App\Models\Partner;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Room;
use App\Models\RoomUnit;
use App\Models\ThemeOption;
use App\Models\User;
use App\Policies\BookingPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\IncomeExpensePolicy;
use App\Policies\InventoryPolicy;
use App\Policies\MenuPolicy;
use App\Policies\MessagePolicy;
use App\Policies\PartnerPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\PropertyPolicy;
use App\Policies\RatePlanPolicy;
use App\Policies\RolePolicy;
use App\Policies\RoomPolicy;
use App\Policies\RoomUnitPolicy;
use App\Policies\ThemeOptionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
        //        ThemeOption::class => ThemeOptionPolicy::class,
        Menu::class => MenuPolicy::class,
        Role::class => RolePolicy::class,
        Permission::class => PermissionPolicy::class,
        User::class => UserPolicy::class,
        Booking::class => BookingPolicy::class,
        Property::class => PropertyPolicy::class,
        Room::class => RoomPolicy::class,
        RoomUnit::class => RoomUnitPolicy::class,
        RatePlan::class => RatePlanPolicy::class,
        Inventory::class => InventoryPolicy::class,
        Customer::class => CustomerPolicy::class,
        Partner::class => PartnerPolicy::class,
        IncomeExpense::class => IncomeExpensePolicy::class,
        Message::class => MessagePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
