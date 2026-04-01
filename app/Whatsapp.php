<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
Use GuzzleHttp\Clients;
use Xendit\Xendit;


class Whatsapp extends Model
{
    //

    public static function wa_restart()
    {
        $client = new Clients(); 
        $result = $client->post(env('WAPISENDER_RESTART'), [
            'form_params' => [
                'api_key' => env('WAPISENDER_KEY'),
                'device_key' => env('WAPISENDER_PAYMENT'),
            ]
        ]);
        $result= $result->getBody();
        $array = json_decode($result, true);
        
        
    // $message_payment = ($array['data']['connection']);
    }
    public static function wa_payment()
    {

        if (env('WAPISENDER_STATUS')!="disable")
        {
         $message_payment="";
         
         try{

             $client = new Clients(); 
             $result = $client->post(env('WAPISENDER_INFO'), [
                'form_params' => [
                    'api_key' => env('WAPISENDER_KEY'),
                    'device_key' => env('WAPISENDER_PAYMENT'),
                ],
                ['connect_timeout' => 5, 'timeout' => 5]

            ]);

             $result= $result->getBody();
             $array = json_decode($result, true);
             $message_payment = ($array['status']);


             if ($array['message'] == "Device disconnect")
             {
                $result = $client->post(env('WAPISENDER_RESTART'), [
                    'form_params' => [
                        'api_key' => env('WAPISENDER_KEY'),
                        'device_key' => env('WAPISENDER_PAYMENT'),
                    ]
                ]);
                $result= $result->getBody();
                $array = json_decode($result, true);
                
                
                $message_payment = ($array['data']['connection']);
            }

            
        }
        
        catch (Exception $e)
        {
            $message_noc ="error";
         // $message_noc =$array['message'];
        }

        return ($message_payment);
    }
    else
    {
     return "disabled";
 }

}


public static function wa_noc()
{

    if (env('WAPISENDER_STATUS')!="disable")
    {

        $message_noc="";
        try{

         $client = new Clients(); 
         $result = $client->post(env('WAPISENDER_INFO'), [
            'form_params' => [
                'api_key' => env('WAPISENDER_KEY'),
                'device_key' => env('WAPISENDER_TICKET'),
            ],
            ['connect_timeout' => 5, 'timeout' => 5]
        ]);

         $result= $result->getBody();
         $array = json_decode($result, true);
         $message_noc = ($array['status']);


         

         
     }
     
     catch (Exception $e)
     {
         $message_noc ="error";
          //$message_noc =$array['message'];
     }
     return $message_noc ;

     
 }
 else
 {
     return "disabled";
 }


}
}
