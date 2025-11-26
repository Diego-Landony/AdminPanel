<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class SocialiteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->app['events']->listen(SocialiteWasCalled::class, function (SocialiteWasCalled $socialiteWasCalled) {
            $socialiteWasCalled->extendSocialite('apple', \SocialiteProviders\Apple\Provider::class);
        });
    }
}
