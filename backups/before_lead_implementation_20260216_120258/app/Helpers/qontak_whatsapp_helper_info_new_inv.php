<?php

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

if (!function_exists('qontak_whatsapp_helper_info_new_inv')) {
    function qontak_whatsapp_helper_info_new_inv($phone, $name, $customer_id, $amount, $due_date, $url)
    {
        if (env('WAPISENDER_STATUS') !== "disable") {
            try {
                $client = new Client();

                // Format nomor HP ke internasional
                $nohp = trim($phone);
                if (substr($nohp, 0, 2) == "62") {
                    $hp = $nohp;
                } elseif (substr($nohp, 0, 1) == "0") {
                    $hp = "62" . substr($nohp, 1);
                } elseif (substr($nohp, 0, 3) == "+62") {
                    $hp = "62" . substr($nohp, 1);
                } else {
                    $hp = $nohp;
                }

                // Data yang dikirim ke API WhatsApp Resmi
                $payload = [
                    "to_number" => $hp,
                    "to_name" => $name,
                    "message_template_id" => env('WA_TAMPLATE_ID_2'),
                    "channel_integration_id" => env('WA_CHANNEL_INTEGRATION_ID'),
                    "language" => ["code" => "id"],
                    "parameters" => [
                        "body" => [
                            ["key" => "1", "value" => "name", "value_text" => $name],
                            ["key" => "2", "value" => "customer_id", "value_text" => $customer_id],
                            ["key" => "3", "value" => "amount", "value_text" => number_format($amount, 0, ',', '.')],
                            ["key" => "4", "value" => "due_date", "value_text" => $due_date],

                        ],
                        "buttons" => [
                            [
                                "index" => "0",
                                "type" => "url",
                                "value" => $url

                            ]
                        ]
                    ]
                ];


                // Kirim request ke API WhatsApp Resmi
                $response = $client->post(env('WHATSAPP_API_URL'), [
                    'headers' => [
                        'Authorization' => 'Bearer ' . env('ACCESS_TOKEN'), // Tambahkan spasi setelah "Bearer"
                        'Content-Type' => 'application/json'
                    ],
                    'json' => $payload
                ]);

                // Decode hasil respons
                $result = json_decode($response->getBody(), true) ?? [];

                // Logging hasil untuk debug (opsional)
                Log::info("WA API Response: ", $result);

                return $result['status'] ?? 'Unknown Status';
            } catch (\Exception $e) {
                Log::error("WA API Error: " . $e->getMessage());
                return 'Error: ' . $e->getMessage();
            }
        }

        return "WA Disabled";
    }
}
