<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($otp)
    {
        $this->otp = $otp;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Your One-Time Password (OTP)')
                    ->html("
                        <div style='font-family: sans-serif; padding: 20px;'>
                            <h1>Reset Your Password</h1>
                            <p>Your OTP code is: <strong style='font-size: 24px; color: #4F46E5;'>{$this->otp}</strong></p>
                            <p>This code will expire in 10 minutes.</p>
                            <p>If you did not request this, please ignore this email.</p>
                            <br>
                            <small>Sent from SavvySave</small>
                        </div>
                    ");
    }
}
