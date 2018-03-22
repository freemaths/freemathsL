<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Log;

class Contact extends Mailable
{
    use Queueable, SerializesModels;
	public $message,$token,$question;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($message,$token,$question)
    {
        $this->message=json_decode($message);
        $this->token=$token;
        $this->question=$question;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
    	//Log::debug('Contact',['message'=>$this->message]);
        return $this->subject("Message from ".$this->message->from->name)->markdown('emails.contact');
    }
}
