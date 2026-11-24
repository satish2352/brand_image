<?php

namespace App\Mail;

use App\Models\Vendor;
use Illuminate\Mail\Mailable;

/** Vendor panel login details, sent by the Super Admin (PRD 2.1). */
class VendorCredentialsMail extends Mailable
{
    public function __construct(public Vendor $vendor, public string $plainPassword)
    {
    }

    public function build()
    {
        return $this->from(config('mail.from.address'), 'Brand Adda')
            ->subject('Your Brand Adda Vendor Panel login details')
            ->view('emails.vendor-credentials')
            ->text('emails.vendor-credentials-text');
    }
}
