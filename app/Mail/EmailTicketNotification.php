<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailTicketNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
    {
        return $this->subject('Notifikasi Tiket #' . $this->data['ticket_id'] . ' - ' . config('app.name'))
            ->view('email.ticket_notification')
            ->with('data', $this->data);
    }
}
