<?php

namespace App\Mail;

use App\Models\Vendor;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Storage;

/** Installation / monitoring / end-date photos sent to the booking user (PRD 2.8). */
class OperationPhotosMail extends Mailable
{
    public const STAGE_TITLES = [
        'installation' => 'Installation photos',
        'monitoring'   => 'Monitoring photos',
        'end_date'     => 'End-date photos',
    ];

    public function __construct(
        public Vendor $vendor,
        public object $booking,
        public string $stage,
        public ?string $date,
        public array $photos
    ) {
    }

    public function build()
    {
        $title = self::STAGE_TITLES[$this->stage] ?? 'Media photos';
        $code  = $this->booking->hoarding_code ?: ($this->booking->media_code ?: $this->booking->media_title);

        $mail = $this->from(config('mail.from.address'), $this->vendor->display_name . ' via Brand Adda')
            ->subject($title . ' – ' . $code . ' (Booking ' . $this->booking->order_no . ')')
            ->view('emails.operation-photos', ['title' => $title]);

        foreach ($this->photos as $photo) {
            $path = Storage::disk('public')->path($photo->file);
            if (is_file($path)) {
                $ext = pathinfo($path, PATHINFO_EXTENSION);
                $mail->attach($path, ['as' => $code . '-' . $photo->shot_type . '.' . $ext]);
            }
        }

        return $mail;
    }
}
