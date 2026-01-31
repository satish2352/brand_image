<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminCampaignCreatedMail extends Mailable
{
    public function __construct(
        public $campaign,
        public string $excelBinary,
        public string $pptBinary
    ) {}

    public function build()
    {
        return $this->subject('New Campaign Created')
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
