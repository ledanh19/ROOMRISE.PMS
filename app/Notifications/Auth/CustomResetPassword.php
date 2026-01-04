<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang; // Import Lang facade for translations

class CustomResetPassword extends BaseResetPassword
{
    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        // You can get the password reset URL using the parent's method
        $url = $this->resetUrl($notifiable);

        return (new MailMessage)
            ->subject(Lang::get('auth.password_reset_subject')) // Custom subject using translation
            ->greeting(Lang::get('auth.password_reset_greeting', ['name' => $notifiable->name])) // Custom greeting with user's name
            ->line(Lang::get('auth.password_reset_intro')) // Custom introduction text
            ->action(Lang::get('auth.reset_password_button'), $url) // Custom button text
            ->line(Lang::get('auth.password_reset_expiration', ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')])) // Custom expiration text
            ->line(Lang::get('auth.password_reset_no_action')); // Custom closing text
    }

    /**
     * Get the password reset URL for the given notifiable.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    protected function resetUrl($notifiable)
    {
        return url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false)); // 'false' for relative URL, or 'true' for absolute
    }
}
