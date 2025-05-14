<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailVerification extends Mailable
{
    use Queueable, SerializesModels;

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
     * The title of the email.
     *
     * @var string
     */
    public $title = 'Email Verification Code';

    /**
     * Create a new message instance.
     *
     * @param User $user
     * @param string $verificationCode
     * @return void
     */
    public function __construct(User $user, string $verificationCode , $title = 'Email Verification Code')
    {
        $this->user = $user;
        $this->verificationCode = $verificationCode;
        $this->title = $title;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.verify-email')
                    ->subject($this->title);
    }
}