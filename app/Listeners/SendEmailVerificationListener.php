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
                
        Mail::to($event->user->email)->send(new EmailVerification($event->user, $event->verificationCode , $event->title));

    }
}