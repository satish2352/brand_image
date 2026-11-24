<?php

namespace App\Mail;

use App\Models\Vendor;
use Illuminate\Mail\Mailable;

/** OTP that opens a vendor's showcase website (PRD 2.9). */
class VendorSiteOtpMail extends Mailable
{
    public function __construct(public Vendor $vendor, public string $otp, public int $minutes)
    {
    }

    public function build()
    {
        return $this->from(config('mail.from.address'), 'Brand Adda')
            ->subject('Your verification code for ' . $this->vendor->vendor_name . ' on Brand Adda')
            ->view('emails.vendor-site-otp');
    }
}
