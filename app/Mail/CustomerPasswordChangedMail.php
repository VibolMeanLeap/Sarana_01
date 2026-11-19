<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerPasswordChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly string $customerName)
    {
    }

    public function build(): self
    {
        return $this->subject('Your SK Coffee password was changed')
            ->view('emails.customer-password-changed');
    }
}
