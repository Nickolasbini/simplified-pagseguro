<?php

namespace NickolasBini\SimplifiedPagSeguro;

use NickolasBini\SimplifiedPagSeguro\Checkout;
use NickolasBini\SimplifiedPagSeguro\Handler;

class SimplifiedPagSeguro extends Handler
{
    private $requestInfo;
    private $checkout;

    private $paymentTypes = [
        1 => 'BOLETO',
        2 => 'CREDIT_CARD'
    ];

    private $enviroments = [
        1 => 'sandbox',
        2 => 'production'
    ];

    private $paymentStatus = [
        1 => 'WAITING',             // no payment received yet
        2 => 'analysis',            // the buyer chose to pay by credict card and PagSeguro is analysing the confibility of the card
        3 => 'payed',               // means the payment was received and you can send the product, note: the value may not be in the account yet
        4 => 'avaliable',           // means the value of the product is already in the account
        5 => 'in dispute',          // the buyer oppened an inquiry informing his/her didn't receive the product or it is malfunctioning so, PagSeguro will take the proper measures
        6 => 'returned',            // the buyer received back the value payed
        7 => 'cancelled',           // the payment was cancelled
        8 => 'credited',            // the value payed returned to the buyer
        9 => 'credit value return', // the buyer entered with a Cashback with his/her credict card operator, wnating the money back in case of a credit payment,
        10 => 'AUTHORIZED'          // payment was a success (happens with a credit card)
    ];

    public function __construct($email = null, $token = null, $enviroment = 'sandbox', $notificationURL = null)
    {
        if(!in_array($enviroment, $this->enviroments))
            $enviroment = 'sandbox';
        $this->enviroment = $enviroment;
        if($notificationURL && !is_array($notificationURL))
            $notificationURL = [$notificationURL];
        $configuration = [
            'email'           => $email,
            'token'           => $token,
            'enviroment'      => $enviroment,
            'notificationURL' => $notificationURL
        ];
        $this->setNotificationURLS($configuration['notificationURL']);
        parent::__construct($configuration);
    }

    public function setReferenceId($referenceId)
    {
        $this->requestInfo['referenceId'] = $referenceId;
    }

    public function setDescription($description)
    {
        $this->requestInfo['description'] = $description;
    }

    public function setSoft_descriptor($softDescription)
    {
        $this->requestInfo['soft_descriptor'] = $softDescription;
    }

    public function setDueDate($dueDate)
    {
        $this->requestInfo['dueDate'] = $dueDate;
    }

    public function setHolder($holder)
    {
        $name   = (array_key_exists('name', $holder) ? $holder['name'] : null);
        $tax_id = (array_key_exists('tax_id', $holder) ? $holder['tax_id'] : null);
        $email  = (array_key_exists('email', $holder) ? $holder['email'] : null);
        $this->requestInfo['holder'] = [
            'name'   => $name,
            'tax_id' => $tax_id,
            'email'  => $email
        ];
    }

    public function setAddress($address)
    {
        $this->requestInfo['address'] = $address;
    }

    public function setAmount($amount)
    {
        $this->requestInfo['amount'] = $amount;
    }

    public function setPaymentType($paymentType)
    {
        if(is_numeric($paymentType)){
            if(!array_key_exists($paymentType, $this->paymentTypes)){
                $paymentType = $this->paymentTypes[1];           
            }else{
                $paymentType = $this->paymentTypes[$paymentType];
            }
        }else{
            if(!in_array($paymentType, $this->paymentTypes)){
                $paymentType = $this->paymentTypes[1];
            }else{
                foreach($this->paymentTypes as $key => $paymentTypeName){
                    if($paymentTypeName == $paymentType){
                        $paymentType = $this->paymentTypes[$key];
                        break;
                    }
                }
            }
        }
        $this->requestInfo['paymentType'] = $paymentType;
    }

    public function setMetaData($metaData)
    {
        $this->requestInfo['metadata'] = $metaData;
    }

    public function setCreditCard($creditCard)
    {
        $this->requestInfo['card'] = $creditCard;
    }

    public function setInstructionLine($instructionLine)
    {
        $instructionArray = [];
        for($i = 0; $i < count($instructionLine); $i++){
            $index = $i + 1;
            $lineIndex = 'line_' . $index;
            $instructionArray[$lineIndex] = $instructionLine[$i];
        }
        $this->requestInfo['instruction_lines'] = $instructionArray;
    }

    public function setNotificationURLS($notificationURL)
    {
        $this->requestInfo['notification_urls'] = $notificationURL;
    }

    public function checkout()
    {
        $checkoutObj = new Checkout($this->getConfigurations());
        switch($this->requestInfo['paymentType']){
            case 'BOLETO':
                $response = $checkoutObj->createBilletTransaction($this->requestInfo);
            break;
            case 'CREDIT_CARD':
                $response = $checkoutObj->createCreditCardTransaction($this->requestInfo);
            break;
        }

        if(!$response || !is_array($response))
            return false;
        if(!array_key_exists('id', $response)){
            return [
                'success'     => false,
                'content'     => $response,
                'checkoutId'  => null,
                'referenceId' => null
            ];
        }
        $this->checkout = $response;
        return [
            'success'     => true,
            'content'     => $response,
            'checkoutId'  => $response['id'],
            'referenceId' => $response['reference_id']
        ];
    }

    public function setCheckout($checkoutResponse)
    {
        $this->checkout = $checkoutResponse;
    }

    public function isCheckoutAuthorized($checkoutResponse = null)
    {
        $checkoutResult = (!$checkoutResponse ? $this->checkout : $checkoutResponse);
        $status = $checkoutResult['status'];
        $paymentMethod = $checkoutResult['payment_method']['type'];
        switch($paymentMethod){
            case 'BOLETO':
                if($status == $this->paymentStatus[1])
                    return false;
                return true;
            break;
            case 'CREDIT_CARD':
                if($status == $this->paymentStatus[10])
                    return true;
                return false;
            break;
            default:
                return false;
            break;
        }
    }

    public function searchCheckoutByCheckoutId($checkoutId = null)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->getHostName('charges/' . $checkoutId),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $this->getToken(),
                'Content-Type: application/json',
                'x-api-version: 4.0'
            ],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        exit($response);
        return json_decode($response, true);
    }

    public function searchCheckoutByReferenceId($referenceId = null)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->getHostName('charges?reference_id=' . $referenceId),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $this->getToken(),
                'Content-Type: application/json',
                'x-api-version: 4.0'
            ],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        $responseArray = json_decode($response, true);
        if(!$responseArray || !is_array($responseArray))
            return false;
        if(!array_key_exists('id', $responseArray)){
            return [
                'success'     => false,
                'content'     => $responseArray,
                'checkoutId'  => null,
                'referenceId' => null
            ];
        }
        return [
            'success'     => true,
            'content'     => $responseArray,
            'checkoutId'  => $responseArray['id'],
            'referenceId' => $response['reference_id']
        ];
    }

    public function checkoutReimbursementByCheckoutId($checkoutId, $valueToRefund)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->getHostName('charges/' . $checkoutId . '/cancel'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                "amount" => [
                    "value" => $valueToRefund
                ]
            ]),
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $this->getToken(),
                'Content-Type: application/json',
                'x-api-version: 4.0'
            ],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        $responseArray = json_decode($response, true);
        if(!$responseArray || !is_array($responseArray))
            return false;
        if(!array_key_exists('id', $responseArray)){
            return [
                'success'     => false,
                'content'     => $responseArray,
                'checkoutId'  => null,
                'referenceId' => null
            ];
        }
        return [
            'success'     => true,
            'content'     => $responseArray,
            'checkoutId'  => $responseArray['id'],
            'referenceId' => $response['reference_id']
        ];
    }
}