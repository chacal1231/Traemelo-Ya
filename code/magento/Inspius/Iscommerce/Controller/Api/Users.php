<?php
/**
 * Copyright © 2016 Inspius. All rights reserved.
 * Author: Phong Nguyen
 * Author URI: http://inspius.com
 */
namespace Inspius\Iscommerce\Controller\Api;

use Inspius\Iscommerce\Model\Message;
use Magento\Customer\Model\AccountManagement;

class Users extends AbstractApi
{
    const USER_ACTION_LOGIN = 'login';
    const USER_ACTION_REGISTER = 'register';
    const USER_ACTION_FORGOT = 'forgot';
    const USER_ACTION_UPDATE = 'update';
    const USER_ACTION_UPDATE_SHIPPING = 'update_shipping';
    const USER_ACTION_UPDATE_BILLING = 'update_billing';

    /**
     * @var \Magento\Customer\Model\AccountManagement
     */
    protected $_accountManagement;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var \Magento\Customer\Model\AddressFactory
     */
    protected $_addressFactory;

    /**
     * Users constructor.
     * 
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param AccountManagement $accountManagement
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\AddressFactory $addressFactory
     * @param \Inspius\Iscommerce\Helper\Data $helper
     * @param \Inspius\Iscommerce\Model\Client $client
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Customer\Model\AccountManagement $accountManagement,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\AddressFactory $addressFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Event\Manager $manager,
        \Inspius\Iscommerce\Helper\Data $helper,
        \Inspius\Iscommerce\Model\Client $client
    )
    {
        $this->_accountManagement = $accountManagement;
        $this->_customerFactory = $customerFactory;
        $this->_addressFactory = $addressFactory;
        parent::__construct($context, $resultJsonFactory, $scopeConfig, $manager, $helper, $client);
    }

    /**
     * consider tasks to do.
     * 
     * @return array
     * @throws \Exception
     */
    protected function _getResponse()
    {
        $data = [];
        if ($task = $this->_request->getParam('task')) {
            switch ($task) {
                case self::USER_ACTION_LOGIN:
                    $data = $this->_login();
                    break;
                case self::USER_ACTION_REGISTER:
                    $data = $this->_register();
                    break;
                case self::USER_ACTION_FORGOT:
                    $this->_forgotPassword();
                    break;
                case self::USER_ACTION_UPDATE:
                    $data = $this->_updateCustomer();
                    break;
                case self::USER_ACTION_UPDATE_BILLING:
                    $data = $this->_updateAddress();
                    break;
                case self::USER_ACTION_UPDATE_SHIPPING:
                    $data = $this->_updateAddress(false);
                    break;
                default:
                    break;
            }
            return $data;
        }
        throw new \Exception(Message::USER_NO_ROUTE);
    }

    /**
     * check customer email and password.
     * 
     * @return array
     * @throws \Exception
     * @throws \Magento\Framework\Exception\EmailNotConfirmedException
     * @throws \Magento\Framework\Exception\InvalidEmailOrPasswordException
     */
    protected function _login()
    {
        // check username - password
        $customerDataObject = $this->_accountManagement->authenticate(
            $this->_getParam('user_login', true, Message::USER_LOGIN_EMPTY_EMAIL),
            $this->_getParam('user_pass', true, Message::USER_LOGIN_EMPTY_PASSWORD)
        );

        return $this->_getCustomerById($customerDataObject->getId());
    }

    /**
     * create new customer
     * 
     * @return array
     * @throws \Exception
     */
    protected function _register()
    {
        /* @var $response \Zend_Http_Response */
        $response = $this->_client->request($this->_url->getBaseUrl() . 'rest/V1/customers', \Zend_Http_Client::POST, [
            'customer' => [
                'email' => $this->_getParam('user_email'),
                'firstname' => $this->_getParam('first_name'),
                'lastname' => $this->_getParam('last_name'),
            ],
            'password' => $this->_getParam('user_pass'),
        ]);

        if (isset($response['id']) && $response['id']) {
            return $this->_getCustomerById($response['id']);
        }
        throw new \Exception(Message::USER_REGISTER_INVALID_DATA);
    }

    /**
     * reset customer password
     * 
     * @return bool
     * @throws \Exception
     */
    protected function _forgotPassword()
    {
        /* @var $response \Zend_Http_Response */
        $response = $this->_client->request($this->_url->getBaseUrl() . 'rest/V1/customers/password', \Zend_Http_Client::PUT, [
            'email' => $this->_getParam('user_login'),
            'websiteId' => 1,
            'template' => AccountManagement::EMAIL_RESET
        ], true);
        if ($response) {
            return true;
        }
        throw new \Exception(Message::USER_REGISTER_INVALID_DATA);
    }

    /**
     * update customer info
     * 
     * @return array
     * @throws \Exception
     */
    protected function _updateCustomer()
    {
        $customerId = $this->_getParam('user_id', true, Message::USER_UPDATE_CUSTOMER_NOT_FOUND);
        /* @var $customer \Magento\Customer\Model\Customer */
        $customer = $this->_customerFactory->create()->load($customerId);
        if ($customer->getId()) {
            $data = $this->_checkCustomerUpdateInfo($customer);

            $customer->setFirstname($data['firstname']);
            $customer->setLastname($data['lastname']);
            if (isset($data['password'])) $customer->changePassword($data['password']);

            return $this->_helper->formatCustomer($customer->save());
        }
        throw new \Exception(Message::USER_UPDATE_FAILED);
    }

    /**
     * validate customer info when update
     * 
     * @param $customer
     * @return array
     * @throws \Exception
     */
    private function _checkCustomerUpdateInfo($customer)
    {
        /* @var $customer \Magento\Customer\Model\Customer */
        $email = $this->_getParam('user_email', true, Message::USER_UPDATE_CUSTOMER_NOT_FOUND);
        $confirmation = $this->_getParam('user_confirmation');
        $password = $this->_getParam('user_pass');
        $newPassword = $this->_getParam('user_new_password');

        if ($confirmation && $newPassword && $password) {
            if ($confirmation == $newPassword) {
                try {
                    // check username - password
                    $customerDataObject = $this->_accountManagement->authenticate($email, $password);
                    if ($customer->getId() == $customerDataObject->getId()) {
                        return [
                            'firstname' => $this->_getParam('user_firstname'),
                            'lastname' => $this->_getParam('user_lastname'),
                            'password' => $newPassword,
                            'email' => $email
                        ];
                    }
                } catch (\Exception $ex) {
                    throw new \Exception(Message::USER_UPDATE_WRONG_PASSWORD);
                }
                throw new \Exception(Message::USER_UPDATE_CUSTOMER_NOT_FOUND);
            }
            throw new \Exception(Message::USER_UPDATE_MISMATCH_CONFIRMATION);
        }
        return [
            'firstname' => $this->_getParam('user_firstname'),
            'lastname' => $this->_getParam('user_lastname'),
            'email' => $email
        ];
    }

    /**
     * create new address and assign as default address
     * 
     * @param bool $isBilling
     * @return array
     * @throws \Exception
     */
    protected function _updateAddress($isBilling = true)
    {
        $data = $isBilling ? json_decode(stripslashes($this->_getParam('billing')), true) : json_decode(stripslashes($this->_getParam('shipping')), true);

        $customerId = $this->_getParam('user_id', true, Message::USER_UPDATE_CUSTOMER_NOT_FOUND);
        /* @var $customer \Magento\Customer\Model\Customer */
        $customer = $this->_customerFactory->create()->load($customerId);

        if ($customer->getId()) {
            /* @var $address \Magento\Customer\Model\Address */
            $address = $this->_createAddress($data, $customer);
            if ($isBilling) {
                $customer = $customer->setDefaultBilling($address->getId())->save();
            } else {
                $customer = $customer->setDefaultShipping($address->getId())->save();
            }
            return $this->_helper->formatCustomer($customer);
        }
        throw new \Exception(Message::USER_UPDATE_CUSTOMER_NOT_FOUND);
    }

    /**
     * create new address
     * 
     * @param $data
     * @param $customer
     * @return $this
     */
    private function _createAddress($data, $customer)
    {
        /* @var $address \Magento\Customer\Model\Address */
        $address = $this->_addressFactory->create();
        $address = $this->_helper->setAddressData($address, $data);
        return $address->setCustomer($customer)->save();
    }

    /**
     * get customer by Id
     * 
     * @param $customerId
     * @return array
     */
    private function _getCustomerById($customerId)
    {
        /* @var $customer \Magento\Customer\Model\Customer */
        $customer = $this->_customerFactory->create()->load($customerId);
        return $this->_helper->formatCustomer($customer);
    }
}