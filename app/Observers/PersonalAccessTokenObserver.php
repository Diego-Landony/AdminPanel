<?php

namespace App\Observers;

use App\Models\CustomerDevice;
use Laravel\Sanctum\PersonalAccessToken;

class PersonalAccessTokenObserver
{
    /**
     * Handle the PersonalAccessToken "deleted" event.
     */
    public function deleted(PersonalAccessToken $personalAccessToken): void
    {
        CustomerDevice::where('sanctum_token_id', $personalAccessToken->id)
            ->update(['is_active' => false]);
    }

    /**
     * Handle the PersonalAccessToken "force deleted" event.
     */
    public function forceDeleted(PersonalAccessToken $personalAccessToken): void
    {
        CustomerDevice::where('sanctum_token_id', $personalAccessToken->id)
            ->update(['is_active' => false]);
    }
}
