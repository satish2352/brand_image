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
        public $excelPath,
        public $pptPath
    ) {}

    public function build()
    {
        return $this->subject('New Campaign Created')
            ->view('emails.admin-campaign-created')
            ->attach($this->excelPath, [
                'as' => 'Campaign.xlsx'
            ])
            ->attach($this->pptPath, [
                'as' => 'Campaign.pptx'
            ]);
    }
}
