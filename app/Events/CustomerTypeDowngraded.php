<?php

namespace App\Events;

use App\Models\Customer;
use App\Models\CustomerType;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerTypeDowngraded
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Customer $customer,
        public CustomerType $previousType,
        public CustomerType $newType
    ) {}
}
