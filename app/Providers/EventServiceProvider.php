<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\UserRegistered;
use App\Listeners\SendEmailVerificationListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Disable Laravel’s automatic event discovery
     * so only the $listen mappings fire.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = false;

    /**
     * Your manual event-to-listener map.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationListener::class,
        ]
    ];
}
