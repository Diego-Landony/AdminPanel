<?php

namespace App\Exceptions;

use App\Models\Customer;
use Exception;

class AccountDeletedException extends Exception
{
    public function __construct(
        protected Customer $customer,
        protected int $daysLeft
    ) {
        $points = $customer->points ?? 0;

        parent::__construct(
            __('auth.oauth_account_deleted_recoverable', [
                'points' => $points,
                'days_left' => $daysLeft,
            ])
        );
    }

    /**
     * Get the deleted customer
     */
    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    /**
     * Get the number of days left to recover
     */
    public function getDaysLeft(): int
    {
        return $this->daysLeft;
    }

    /**
     * Get the customer points
     */
    public function getPoints(): int
    {
        return $this->customer->points ?? 0;
    }

    /**
     * Get the customer email
     */
    public function getEmail(): string
    {
        return $this->customer->email;
    }

    /**
     * Get the customer's OAuth provider (local, google, apple)
     */
    public function getOAuthProvider(): string
    {
        return $this->customer->oauth_provider ?? 'local';
    }
}
