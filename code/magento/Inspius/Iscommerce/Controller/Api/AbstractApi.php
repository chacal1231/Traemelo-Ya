<?php
/**
 * Copyright © 2016 Inspius. All rights reserved.
 * Author: Phong Nguyen
 * Author URI: http://inspius.com
 */
namespace Inspius\Iscommerce\Controller\Api;

use Inspius\Iscommerce\Model\Status;
use Magento\Framework\App\Action\Action;

abstract class AbstractApi extends Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var \Inspius\Iscommerce\Model\Client
     */
    protected $_client;

    /**
     * @var \Inspius\Iscommerce\Helper\Data
     */
    protected $_helper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Event\Manager
     */
    protected $_eventManager;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Event\Manager $manager,
        \Inspius\Iscommerce\Helper\Data $helper,
        \Inspius\Iscommerce\Model\Client $client
    )
    {
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->_eventManager = $manager;
        $this->_client = $client;
        $this->_helper = $helper;
        parent::__construct($context);
    }

    public function execute()
    {
        // Allow from any origin
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');    // cache for 1 day
        }

        // Access-Control headers are received during OPTIONS requests
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
                header("Access-Control-Allow-Methods: GET, POST, OPTIONS");         

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
                header("Access-Control-Allow-Headers:        {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

            exit(0);
        }

        try {
            return $this->_formatResponse(Status::API_SUCCESS, null, $this->_getResponse());
        } catch (\Exception $ex) {
            return $this->_formatResponse(Status::API_FAILED, $ex->getMessage());
        }
    }

    protected abstract function _getResponse();

    protected function _formatResponse($status = Status::API_SUCCESS, $message = '', $data = [])
    {
        /* @var $result \Magento\Framework\Controller\Result\Json */
        $result = $this->_resultJsonFactory->create();
        return $result->setData([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ]);
    }

    protected function _getParam($name, $required = false, $errorMessage = '')
    {
        $param = $this->_request->getParam($name, null);
        if ($required && !$param) {
            throw new \Exception($errorMessage);
        }
        return $param;
    }

    protected function _getSetting($configName)
    {
        if (!$configName) return null;
        return $this->_scopeConfig->getValue($configName, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}