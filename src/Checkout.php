<?php

namespace NickolasBini\SimplifiedPagSeguro;

use NickolasBini\SimplifiedPagSeguro\Handler;

class Checkout extends Handler
{
    public function __construct($configuration)
    {
        parent::__construct($configuration);
    }

    public function createBilletTransaction($infoData = null)
    {
        if(!$infoData)
            return false;
        $data = [
            "reference_id"   => $infoData['referenceId'],
            "description"    => $infoData['description'],
            "amount"         => $infoData['amount'],
            "payment_method" =>  [
                "type" =>  $infoData['paymentType'],
                "boleto" =>  [
                    "due_date"          =>  $infoData['dueDate'],
                    "instruction_lines" =>  $infoData['instruction_lines'],
                    "holder" =>  [
                        "name"   =>  $infoData['holder']['name'],
                        "tax_id" =>  $infoData['holder']['tax_id'],
                        "email"  =>  $infoData['holder']['email'],
                        "address" =>  $infoData['address']
                    ]
                ]
            ],
            "notification_urls" => $infoData['notification_urls']
        ];
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->getHostName('charges'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data, JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER => [
                'Authorization: '.$this->getToken(),
                'Content-Type: application/json',
                'x-api-version: 4.0',
                'x-idempotency-key: '
            ],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response, true);
    }

    public function createCreditCardTransaction($infoData)
    {
        if(!$infoData)
            return false;
        $data = [
            "reference_id"   => $infoData['referenceId'],
            "description"    => $infoData['description'],
            "amount"         => $infoData['amount'],
            "payment_method" =>  [
                "type"            => $infoData['paymentType'],
                "installments"    => 1,
                "capture"         => false,
                "soft_descriptor" => (array_key_exists('soft_descriptor', $infoData) ? $infoData['soft_descriptor'] : []), 
                "card" =>  [
                    "number"        =>  $infoData['card']['number'],
                    "exp_month"     =>  $infoData['card']['exp_month'],
                    "exp_year"      =>  $infoData['card']['exp_year'],
                    "security_code" =>  $infoData['card']['security_code'],
                    "holder" =>  [
                        "name"   =>  $infoData['holder']['name'],
                    ]
                ]
            ],
            "notification_urls" => $infoData['notification_urls'],
            "metadata"          => (array_key_exists('metadata', $infoData) ? $infoData['metadata'] : [])
        ];
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->getHostName('charges'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data, JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $this->getToken(),
                'Content-Type: application/json',
                'x-api-version: 4.0',
                'x-idempotency-key: '
            ],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response, true);
    }
}