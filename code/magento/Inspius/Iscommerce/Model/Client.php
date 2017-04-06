<?php
/**
 * Copyright © 2016 Inspius. All rights reserved.
 * Author: Phong Nguyen
 * Author URI: http://inspius.com
 */

namespace Inspius\Iscommerce\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Integration\Model\IntegrationService;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Framework\HTTP\ZendClient;

class Client extends \Magento\Framework\DataObject
{
    /**
     * @var IntegrationService
     */
    protected $integrationService;

    /**
     * @var TokenFactory
     */
    protected $tokenFactory;

    /**
     * @var ZendClient
     */
    protected $_client;

    protected $_registry;
    
    const INTEGRATION_NAME = 'iscommerceIntegration';
    
    public function __construct(
        \Magento\Framework\Registry $registry,
        IntegrationService $integrationService,
        TokenFactory $tokenFactory,
        ZendClient $client,
        array $data = []
    )
    {
        $this->_registry = $registry;
        $this->integrationService = $integrationService;
        $this->tokenFactory = $tokenFactory;
        $this->_client = $client;
        parent::__construct($data);
    }
    
    protected function _getToken()
    {
        $integration = $this->integrationService->findByName(self::INTEGRATION_NAME);
        if ($integration->getIntegrationId()) {
            $token = $this->tokenFactory->create()->loadByConsumerIdAndUserType($integration->getConsumerId(), \Magento\Authorization\Model\UserContextInterface::USER_TYPE_INTEGRATION);
            if ($token->getId()){
                return $token->getToken();
            }
            throw new \Exception(Message::INTEGRATION_TOKEN_NOT_FOUND);
        }
        throw new \Exception(Message::INTEGRATION_NOT_FOUND);
    }

    public function request($uri, $method = \Zend_Http_Client::POST, $params = [], $noResponse = false, $contentType = \Zend_Http_Client::ENC_FORMDATA)
    {
        if (!\function_exists('curl_version')) {
            throw new \Exception('cURL is NOT installed on this server');
        }

        $ch = curl_init($uri);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER 		=> [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->_getToken()
            ],
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => \Zend_Json_Encoder::encode($params)
        ]);

        $response = curl_exec($ch);
        curl_close($ch);
        return $noResponse ? $response : json_decode($response, true);
    }
    
    public function customRequest($uri, $method, $params = [], $noResponse = false)
    {
        if (!\function_exists('curl_version')) {
            throw new \Exception('cURL is NOT installed on this server');
        }

        $ch = curl_init($uri);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER 		=> [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->_getToken()
            ],
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => \Zend_Json_Encoder::encode($params)
        ]);

        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
    
    protected function _checkRequest($response)
    {
        /* @var $response \Zend_Http_Response */
        $code = $response->getStatus();
        if ($code !== 200) {
            $body = json_decode($response->getBody(), true);
            throw new \Exception($body['message']);
        }
        return true;
    }
}