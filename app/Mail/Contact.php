<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class Contact extends Mailable
{
    use Queueable, SerializesModels;
	public $from_user,$to_user,$message,$token;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($from,$to,$message,$token)
    {
        $this->to_user=$to;
        $this->from_user=$from;
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
        return $this->subject("Message from ".$this->from_user['name'])->markdown('emails.contact');
    }
}
