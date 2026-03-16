<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $fullname;

    public function __construct($otp, $fullname)
    {
        $this->otp = $otp;
        $this->fullname = $fullname;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your OTP is ' . $this->otp . ' - Valid for 10 minutes',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp', // view file ဆောက်ပေးရမယ်
        );
    }
}