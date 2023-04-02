<?php

namespace App\Console\Commands;
use App\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Console\Command;
use App\Http\Controllers\AdKernalController;
use App\Mail\CampaignDisableEmail;
use Illuminate\Support\Facades\Http;

class DisableCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaign:disable';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disable Campaigns based on Advertiser';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */

    function disableCampaign($token, $start, $advertiser_id, $disabled)
    {
        $response = Http::get('https://login.myadcampaigns.com/admin/api/Campaign', [
            'token' => $token, 
            'range' => ''.$start.'-200',
            'filters' => 'advertiser:'.$advertiser_id.';is_active:true',
            'ord' => '-id'
        ]);
        $body = $response->json(); 
        $array_data = $body['response'];
        $start = $start + count($array_data);
        
        if(count($array_data) === 0)
        {
            if(count($disabled) > 0) {
                $data = ['ids' => $disabled, 'advertiser' => $advertiser_id];
                Mail::to('staycationmedia@gmail.com')->send(new CampaignDisableEmail($data));
            }
            return 0;
        } else {
            foreach($array_data AS $key => $value )
            {
                $date = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
                $date_system= strtotime($value['end_date']); //current timestamp
                $date_yesterday = strtotime($date);
                //$dateTimestamp1 = strtotime($value['end_date']); //in the system 
                //$dateTimestamp2 = strtotime($date); //todays date
                if ($date_system < $date_yesterday || $date_system === $date_yesterday)
                {
                    array_push($disabled, $value['id']);
                    $response = Http::put('https://login.myadcampaigns.com/admin/api/Campaign/'.$value['id'].'?token='.$token.'', array('is_active' => false));
                    $result = $response->json();
                }
            }
        }
        return $this->disableCampaign($token, $start, $advertiser_id, $disabled);
    }

    public function handle()
    {
        $users = \App\User::where('level','=', 3)->get();
        $adKernalController = new AdKernalController;

        foreach($users AS $key => $value) {
            $start = 0;
            $disabled = array();
            $token = $adKernalController->create_token($value->adkernal_l, $value->adkernal_p);
            $body = $this->disableCampaign($token, $start, $value->advertiser_id, $disabled);
        }

        return 0;
    }
}
