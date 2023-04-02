<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\User;
// use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Validator;
use Auth;

class BulkController extends BaseController
{
    public function create_token()
    {
        $response = Http::get('https://login.myadcampaigns.com/admin/auth', ['login' => Auth::user()->adkernal_l,'password' => Auth::user()->adkernal_p,]);
        if($response->ok())
        {
            $token = $response->body();
            return $response->body();
        }
        else
        {
            $result = array('status' => 'Error', 'message' => 'Auth Token Creation Failed.  Verify username and password are correct');
            return json_encode($result);
        }
    }

    public function getCampaignByName($name, $token)
    {
        $name = strtolower(trim($name));

         $response = Http::get('https://login.myadcampaigns.com/admin/api/Campaign', ['token' => $token, 'filters' => 'search:'.$name.'']);
         $result = $response->json(); 

         foreach($result['response'] AS $key => $value)
         {
            $searchName = strtolower(trim($value['name']));

            if( $searchName === $name)
            {
                return false;
            }
         }

         return true;
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),[ 
            'file*.' => 'required|mimes:csv',
        ]);

        if($validator->fails()) {          
            return $this->sendError('Invalid File', ['error' => $validator->errors()]);                       
        }

        if($request->file('file') == null)
        {
            return $this->sendError('Invalid File', ['error' => 'No file specified']);
        }

        $advertiser_id = Auth::user()->advertiser_id;
        $remotefeed_id = Auth::user()->remotefeed_id;
        
        if ($file = $request->file('file'))
        {
            $fileName = $request->file('file');
            $token = $this->create_token();
            $cities = file_get_contents(storage_path('json/GeoCities.json'));
            $json_cities = json_decode($cities, true);
            $states = file_get_contents(storage_path('json/GeoRegions.json'));
            $json_states = json_decode($states, true);
            $regions = file_get_contents(storage_path('json/GeoRegionByID.json'));
            $json_regions = json_decode($regions,true);
            $cities = file_get_contents(storage_path('json/GeoCities.json'));
            $json_cities = json_decode($cities,true);

            $file = fopen($fileName, "r");
            $row = 0;
            $uploadErrors = array();
            while (($column = fgetcsv($file, 10000, ",")) !== FALSE) 
            {
                if (!isset($column[19])) {
                    $result = array('status' => 'Error', 'message' => 'Column Mismatch, please use the template');
                    return json_encode($result);
                }
                $is_new = true;

                if($row > 0)
                {
                    if($column[0] != '')
                    {
                        $is_new = false;
                    }
                    $duplicate = $this->getCampaignByName($column[1], $token);
                    $is_campaign_active = false;
                    $is_offer_active = false;
                    $country = $column[15];
                    $countryCodes = array('us');
                    $stateCodes = array();
                    $cityCodes = array();
                    $zipCodes = array();

                    if($is_new === true) 
                    {
                        if(strtoupper($column[5]) === 'TRUE' || $column[5] === '1' || strtoupper($column[5]) === 'ON')
                        {
                            $is_campaign_active = true;
                        }

                        if(strtoupper($column[9]) == 'TRUE' || $column[9] === '1' || strtoupper($column[9]) === 'ON')
                        {
                            $is_offer_active = true;
                        }
                        
                        if($column[16] != '')
                        {
                            $states = rtrim($column[16], '|');
                            $states = explode('|', $states);
                            foreach($states AS $key => $value)
                            {
                                $iso = trim(strtolower($value));
                                if(isset($json_states[0]['us'][$iso]))
                                {
                                    $stateCodes[] = $json_states[0]['us'][$iso];
                                }
                                else
                                {
                                    $uploadErrors[$row]['states'][] = $iso;
                                }
                            }
                        }

                        if($column[17] != '')
                        { 
                            $cities = explode('|', $column[17]);
                            foreach($cities AS $key => $value)
                            {   
                                $string = explode(',',$value);
                                $iso = trim(strtolower($string[1]));
    
                                if(isset($json_states[0][$country][$iso]))
                                {
                                    $cityName = strtolower(trim($string[0]));
                                    $stateID = $json_states[0][$country][$iso];

                                    if(isset($json_cities[$stateID][$cityName]))
                                    {
                                        $cityCodes[] = $json_cities[$stateID][$cityName];
                                    }
                                    else
                                    {
                                        $uploadErrors[$row]['cities'][] = $value;
                                    }
                                }
                                else
                                {
                                    $uploadErrors[$row]['cities'][] = $value;
                                }
                            }
                        }

                        if($column[18] != '')
                        {
                            $dma_file = file_get_contents(storage_path('json/dma.json'));
                            $json_dma = json_decode($dma_file, true);
                            $dmaX = explode('|', $column['18']);

                            $dma = array();
                            foreach($dmaX AS $key => $value)
                            {   
                                array_push($dma, $value);
                            }

                            foreach($json_dma AS $key => $value)
                            {
                                foreach($value AS $key2 => $value2)
                                {
                                    if( in_array($key2, $dma) )
                                    {
                                        $string = explode(',', $value2);

                                        if(isset($string[0]) && isset($string[1])) 
                                        {
                                            $iso = trim(strtolower($string[1]));

                                            if(isset($json_states[0][$country][$iso]))
                                            {
                                                $cityName = strtolower(trim($string[0]));
                                                $stateID = $json_states[0][$country][$iso];

                                                if(isset($json_cities[$stateID][$cityName]))
                                                {
                                                    $cityCodes[] = $json_cities[$stateID][$cityName];
                                                }
                                                else
                                                {
                                                    //$uploadErrors[$row]['cities'][] = $value2;
                                                }
                                            }
                                            else
                                            {
                                                //$uploadErrors[$row]['states'][] = $value2;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        
                        if($column[19] != '')
                        {
                            $geoZipCodes = file_get_contents(storage_path('json/GeoZips.json'));
                            $json_zipCodes = json_decode($geoZipCodes, true);
                            $zips = explode('|', $column[19]);
                            foreach($json_zipCodes AS $key => $value )
                            {
                                if(in_array($value['PostalCode'], $zips))
                                {
                                    array_push($zipCodes , $value['Id']);
                                }
                            }
                        }
                    }

                    if($is_new === true && !empty($column[2]))
                    {
                        if($duplicate)
                        { 
                            $response = Http::post('https://login.myadcampaigns.com/admin/api/Campaign?token='.$token.'', array(
                                'advertiser_id' => intval($advertiser_id),
                                'remotefeed_id' => intval($remotefeed_id),
                                'name'  => $column[1],
                                'budget_total'  => floatval($column[2]), //double
                                'budget_daily'  => floatval($column[3]), //double
                                'budget_limiter_type' => $column[4], //ENUM [Evenly,ASAP]
                                'is_active' => $is_campaign_active,
                                'start_date' => date("Y-m-d", strtotime($column[6])), //Date
                                'end_date' => date("Y-m-d", strtotime($column[7])) //Date
                            ));

                            $result = $response->json();
                        }
                    } else {

                        $response = Http::put('https://login.myadcampaigns.com/admin/api/Campaign/'.$column[0].'?token='.$token.'', array(
                        'name'  => $column[1],
                        'budget_total'  => floatval($column[2]), //double
                        'budget_daily'  => floatval($column[3]), //double
                        'is_active' => $is_campaign_active,
                        'start_date' => date("Y-m-d", strtotime($column[6])), //Date
                        'end_date' => date("Y-m-d", strtotime($column[7])) //Date
                        ));

                        $result = $response->json();
                    }

                    if(!$duplicate && $is_new === true)
                    {
                        $uploadErrors[$row]['campaigns'] = 'Campaign name already exists';
                    }
                    else if($result['status'] === 'Error')
                    {
                        $uploadErrors[$row]['campaigns'] = explode(".", $result['message']);
                    }
                    else
                    {
                        if($is_new === true)
                        {
                            $ad_campaign_id = $result['response']['created'];
                            ////make offer call with ad_campaign_ID
                            $response = Http::post('https://login.myadcampaigns.com/admin/api/OfferNew?token='.$token.'', [
                                'ad_campaign_id'  => $ad_campaign_id,
                                'name'  => $column[8],
                                'is_active'  => $is_offer_active,
                                'bid' => floatval($column[10]), //ENUM [Evenly,ASAP]
                                'Ad' => array(
                                    'mode' => 'REPLACE',
                                    'create' => array(
                                            'title' => $column[11], //string
                                            'desc' => $column[12], //string
                                            'display' => $column[13], //string
                                            'dest_url'=> $column[14]
                                        )
                                ),
                                'Location' => array(
                                    'mode' => 'REPLACE',
                                    'edit' => array(
                                        'countries' => $countryCodes,
                                        'states' => $stateCodes,
                                        'cities' => $cityCodes,
                                        'zips' => $zipCodes,
                                        'enabled' => true
                                    )
                                ),
                                'TimePeriod' => array(
                                    'mode' => 'REPLACE',
                                    'edit' => array(
                                        //array( "hour" => 0, "enabled" => true),
                                        //array( "hour" => 1, "enabled" => true),
                                        array( "hour" => 2, "enabled" => true),
                                        array( "hour" => 3, "enabled" => true),
                                        array( "hour" => 4, "enabled" => true),
                                        array( "hour" => 5, "enabled" => true),
                                        array( "hour" => 6, "enabled" => true),
                                        array( "hour" => 7, "enabled" => true),
                                        array( "hour" => 8, "enabled" => true),
                                        array( "hour" => 9, "enabled" => true),
                                        array( "hour" => 10, "enabled" => true),
                                        array( "hour" => 11, "enabled" => true),
                                        array( "hour" => 12, "enabled" => true),
                                        array( "hour" => 13, "enabled" => true),
                                        array( "hour" => 14, "enabled" => true),
                                        array( "hour" => 15, "enabled" => true),
                                        array( "hour" => 16, "enabled" => true),
                                        array( "hour" => 17, "enabled" => true),
                                        array( "hour" => 18, "enabled" => true),
                                        array( "hour" => 19, "enabled" => true),
                                        array( "hour" => 20, "enabled" => true),
                                        array( "hour" => 21, "enabled" => true)
                                        //array( "hour" => 22, "enabled" => true),
                                        //array( "hour" => 23, "enabled" => true),
                                        //array( "hour" => 24, "enabled" => true)
                                    )
                                )

                            ]);

                            $OfferResult = $response->json(); 

                            if($OfferResult['status'] === 'Error')
                            {
                                $uploadErrors[$row]['offers'] = $OfferResult['message'];
                            }
                        }
                    }
                }
                $row++;
            }  
            if(!empty($uploadErrors))
            {
                $result = array('status' => 'Error', 'message' => $uploadErrors);
                return json_encode($result);
            }

            $result = array('status' => 'Success');
            return json_encode($result);
        }
    }
}