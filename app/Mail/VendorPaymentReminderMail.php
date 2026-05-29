<?php

namespace App\Mail;

use App\Models\B2bVendor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VendorPaymentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public B2bVendor $vendor) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Wallet payment reminder - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'user.emails.payment-reminder',
        );
    }
}
