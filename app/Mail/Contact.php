<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class Contact extends Mailable
{
    use Queueable, SerializesModels;
	public $name,$sender,$message,$token;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($name,$sender,$message,$token)
    {
        $this->name=$name;
        $this->sender=$sender;
        $this->message=$message;
        $this->token=$token;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject("Message from $this->name")->markdown('emails.contact');
    }
}
