<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class ContactEnquiryMail extends Mailable
{
    public function __construct(
        public array $enquiry
    ) {}

    public function build()
    {
        $subject = 'New Enquiry from ' . $this->enquiry['full_name'];

        return $this->subject($subject)
            // So a reply from the sales inbox goes straight back to the visitor
            // instead of to the site's own sending address.
            ->replyTo($this->enquiry['email'], $this->enquiry['full_name'])
            ->view('emails.contact-enquiry');
    }
}
