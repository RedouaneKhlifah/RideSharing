@component('mail::message')
<div style="text-align: center;">
    <img src="{{ asset('images/logo.png') }}" alt="Logo" style="max-width: 150px; margin-bottom: 20px;">
</div>

<h1 style="text-align: center; color: #4F46E5; font-size: 24px; margin-bottom: 20px;">Verify Your Email Address</h1>

<p style="font-size: 16px; line-height: 1.6; margin-bottom: 20px;">
    Hello <strong>{{ $user->name }}</strong>,
</p>

<p style="font-size: 16px; line-height: 1.6; margin-bottom: 25px;">
    Thank you for registering with our service. To complete your registration and verify your email address, please use the verification code below:
</p>

@component('mail::panel')
<div style="text-align: center; background-color: #f8fafc; padding: 25px 15px; border-radius: 8px;">
    <p style="margin-bottom: 10px; color: #64748b; font-size: 14px;">YOUR VERIFICATION CODE</p>
    <div style="font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #4F46E5; background-color: #EEF2FF; padding: 15px; border-radius: 6px; display: inline-block; min-width: 180px;">
        {{ $verificationCode }}
    </div>
    <p style="margin-top: 15px; color: #94a3b8; font-size: 13px;">This code will expire in 1 hour</p>
</div>
@endcomponent

@component('mail::button', ['url' => config('app.url'), 'color' => 'primary'])
Return to Website
@endcomponent

<p style="font-size: 14px; color: #64748b; margin-top: 25px; line-height: 1.5;">
    If you did not create an account, no further action is required.
</p>

<div style="margin: 30px 0; border-top: 1px solid #e2e8f0; padding-top: 20px;">
    <p style="font-size: 14px; color: #94a3b8; text-align: center; margin-bottom: 5px;">
        Need help? Contact our support team
    </p>
    <p style="font-size: 14px; color: #4F46E5; text-align: center; margin-bottom: 0;">
        <a href="mailto:support@example.com" style="color: #4F46E5; text-decoration: none;">support@example.com</a>
    </p>
</div>

<p style="text-align: center; font-size: 12px; color: #94a3b8; margin-top: 25px;">
    &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
</p>
@endcomponent