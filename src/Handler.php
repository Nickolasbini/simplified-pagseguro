<?php

namespace NickolasBini\SimplifiedPagSeguro;

class Handler 
{
    private $hostURl = [
        'sandbox'    => 'https://sandbox.api.pagseguro.com',
        'production' => 'https://api.pagseguro.com'
    ];
    private $configuration = [];

    public function __construct($configuration)
    {
        $this->configuration = $configuration;
    }

    public function getCredentials()
    {
        return [
            'email' => $this->configuration['email'],
            'token' => $this->configuration['token']
        ];
    }

    public function getToken()
    {
        return $this->configuration['token'];
    }

    public function getEmail()
    {
        return $this->configuration['email'];
    }

    public function getNotificationURL()
    {
        return $this->configuration['notificationURL'];
    }

    public function getConfigurations()
    {
        return $this->configuration;
    }

    public function getEnviroment()
    {
        return $this->configuration['enviroment'];
    }

    public function formatCheckoutId($checkoutId)
    {
        return str_replace(['CHAR_', '-'], '', $checkoutId);
    }

    public function getHostName($action = null)
    {
        $enviroment = $this->getEnviroment();
        return (!$action ? $this->hostURl[$enviroment] : $this->hostURl[$enviroment] . '/' . $action);
    }
}