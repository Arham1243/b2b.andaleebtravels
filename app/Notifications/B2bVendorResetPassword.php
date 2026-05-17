<?php

namespace App\Notifications;

use App\Models\Config;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class B2bVendorResetPassword extends Notification
{

    public function __construct(public string $token) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $email = $notifiable->getEmailForPasswordReset();

        $verifyLink = url(route('password.reset', [
            'token' => $this->token,
            'email' => $email,
        ], false));

        $siteLogo = Config::where('config_key', 'SITE_LOGO')->value('config_value');
        $logo = $siteLogo ? asset($siteLogo) : asset('frontend/assets/images/logo.webp');

        $fullName = trim((string) (($notifiable->name ?? '') ?: ($notifiable->username ?? '')));
        if ($fullName === '') {
            $fullName = 'there';
        }

        return (new MailMessage)
            ->subject('Reset your password - ' . config('app.name'))
            ->view('frontend.emails.reset-password', [
                'data' => [
                    'full_name' => $fullName,
                    'verify_link' => $verifyLink,
                    'logo' => $logo,
                ],
            ]);
    }
}
