<?php

namespace App\Mail;

use App\Models\ContactMessage as ContactMessageModel;
use App\Support\Phone;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Лист магазину про нове повідомлення з форми «Напишіть нам» */
class ContactMessage extends Mailable
{
    public function __construct(public ContactMessageModel $contactMessage) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Повідомлення з сайту: '.$this->contactMessage->name.', '.Phone::format($this->contactMessage->phone),
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.contact-message',
        );
    }
}
