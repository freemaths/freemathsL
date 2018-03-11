<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class Contact extends Mailable
{
    use Queueable, SerializesModels;
	public $name,$sender,$message;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($name,$sender,$message)
    {
        $this->name=$name;
        $this->sender=$sender;
        $this->message=$message;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject("Message from $this->name via FreeMaths.uk")->markdown('emails.contact');
    }
}
