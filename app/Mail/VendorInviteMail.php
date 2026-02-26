<?php

namespace App\Mail;

use App\Models\B2bVendor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VendorInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public B2bVendor $vendor;
    public string $plainPassword;

    public function __construct(B2bVendor $vendor, string $plainPassword)
    {
        $this->vendor = $vendor;
        $this->plainPassword = $plainPassword;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to ' . config('app.name') . ' - Your Account Details',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.vendor-invite',
        );
    }
}
