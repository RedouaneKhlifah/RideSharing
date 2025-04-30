<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UserRegistered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The user instance.
     *
     * @var User
     */
    public $user;

    /**
     * The verification code.
     *
     * @var string
     */
    public $verificationCode;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param string $verificationCode
     * @return void
     */
    public function __construct(User $user, string $verificationCode)
    {
        $this->user = $user;
        $this->verificationCode = $verificationCode;

    }
}