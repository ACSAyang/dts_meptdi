<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $username,
        public string $loginUrl,
    ) {
    }

    public function build()
    {
        return $this
            ->subject('Official Account Creation Notification')
            ->view('emails.test');
    }
}
