<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class AdminCampaignCreatedMail extends Mailable
{
    public function __construct(
        public $campaign,
        public string $excelBinary,
        public string $pptBinary
    ) {}

    public function build()
    {
        return $this->subject('Campaign Created Successfully')
            ->view('emails.admin-campaign-created')
            ->attachData(
                $this->excelBinary,
                'Campaign.xlsx',
                ['mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            )
            ->attachData(
                $this->pptBinary,
                'Campaign.pptx',
                ['mime' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation']
            );
    }
}
