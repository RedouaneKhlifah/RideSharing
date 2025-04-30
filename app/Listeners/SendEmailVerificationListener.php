<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmailVerification;

class SendEmailVerificationListener implements ShouldQueue
{
    use InteractsWithQueue;
    

    /**
     * Handle the event.
     *
     * @param  UserRegistered  $event
     * @return void
     */
    public function handle(UserRegistered $event)
    {
                
        // Add debug tracing to see when and where this event is created
        Log::info('UserRegistered event created', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
        ]);
        Mail::to($event->user->email)->send(new EmailVerification($event->user, $event->verificationCode));

    }
}