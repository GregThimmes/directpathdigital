<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CampaignDisableEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $address = 'noreply@staycationmedia.com';
        $subject = 'AdLoader Campaign Disabled Report For '.$this->data['advertiser'];
        $name = 'AdLoader Admin';

        return $this->view('emails.campaignDisable')
            ->from($address, $name)
            //->replyTo($address, $name)
            ->subject($subject)
            ->with([ 'ids' => $this->data['ids'] ]);
    }
}
