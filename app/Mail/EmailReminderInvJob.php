<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailReminderInvJob extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $data;
    public function __construct($data)
    {
        //
       $this->data = $data;
   }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
     $subject = (($this->data['type'] ?? '') === 'blocked')
        ? 'Layanan Internet Diisolir - ' . config('app.name')
        : 'Pengingat Tagihan ' . config('app.name');

     return $this->subject($subject)
     ->view('email/reminderinvjob')
     ->with('data', $this->data);
 }
}
