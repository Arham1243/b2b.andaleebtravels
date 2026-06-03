<?php

namespace App\Mail;

use App\Models\B2bWalletLedger;
use App\Support\WalletLedgerDescription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WalletLedgerTransactionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public B2bWalletLedger $entry) {}

    public function envelope(): Envelope
    {
        $amount = number_format((float) $this->entry->amount, 2);
        $prefix = WalletLedgerDescription::emailSubjectPrefix($this->entry);

        return new Envelope(
            subject: "{$prefix}: AED {$amount} - " . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'user.emails.wallet-ledger-transaction',
        );
    }
}
