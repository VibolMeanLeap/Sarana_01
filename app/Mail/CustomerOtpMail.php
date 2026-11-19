<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $customerName,
        public readonly string $otp,
        public readonly string $purpose = 'email_verification',
        public readonly int $expiresInMinutes = 5,
    ) {
    }

    public function build(): self
    {
        $subject = $this->purpose === 'password_reset'
            ? 'Reset your SK Coffee password'
            : 'Verify your SK Coffee email';

        return $this->subject($subject)->view('emails.customer-otp');
    }
}
