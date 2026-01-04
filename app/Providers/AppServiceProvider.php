<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\BookingRoom;
use App\Observers\BookingObserver;
use App\Observers\BookingRoomObserver;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        JsonResource::withoutWrapping();

        Validator::extend('at_least_one_selected', function ($attribute, $value, $parameters, $validator) {
            foreach ($value as $item) {
                if ($item['selected']) {
                    return true;
                }
            }
            return false;
        });

        // Booking::observe(BookingObserver::class);
        BookingRoom::observe(BookingRoomObserver::class);
    }
}
