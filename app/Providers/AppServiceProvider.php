<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use App\Observers\CustomerObserver;
use App\Observers\CustomerTypeObserver;
use App\Observers\RestaurantObserver;
use App\Observers\RoleObserver;
use App\Observers\UserObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);
        Role::observe(RoleObserver::class);
        Customer::observe(CustomerObserver::class);
        CustomerType::observe(CustomerTypeObserver::class);
        Restaurant::observe(RestaurantObserver::class);
    }
}
