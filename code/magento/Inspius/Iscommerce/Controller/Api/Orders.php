<?php
/**
 * Copyright © 2016 Inspius. All rights reserved.
 * Author: Phong Nguyen
 * Author URI: http://inspius.com
 */
namespace Inspius\Iscommerce\Controller\Api;

use Inspius\Iscommerce\Model\Message;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Cart\ShippingMethodConverter;
use Magento\Quote\Model\QuoteFactory;

class Orders extends AbstractApi
{
    const ORDER_GET_PRICE = 'get_price';
    const ORDER_CREATE = 'create_order';
    const ORDER_GET = 'get_order';
    const ORDER_LIST_BY_CUSTOMER = 'list_customer_order';
    const ORDER_CREATE_CART = 'create_cart';
    const ORDER_LIST_COUNTRIES = 'list_countries';

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $_quoteRepository;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var \Magento\Quote\Model\QuoteIdMaskFactory
     */
    protected $_quoteIdMaskFactory;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $_countryFactory;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $_paymentHelper;

    /**
     * @var \Magento\Payment\Model\Checks\SpecificationFactory
     */
    protected $_methodSpecificationFactory;

    /**
     * @var \Magento\Quote\Model\Quote\AddressFactory
     */
    protected $_addressFactory;

    /**
     * @var ShippingMethodConverter
     */
    protected $_converter;

    /**
     * @var \Magento\Quote\Model\Quote\TotalsCollector
     */
    protected $_totalsCollector;

    /**
     * @var \Magento\Store\Model\StoreManager
     */
    protected $_storeManager;

    /**
     * @var QuoteFactory
     */
    protected $_quoteFactory;

    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $_transactionFactory;

    /**
     * Orders constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Event\Manager $manager
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param \Magento\Payment\Model\Checks\SpecificationFactory $specificationFactory
     * @param \Magento\Quote\Model\Quote\AddressFactory $addressFactory
     * @param ShippingMethodConverter $converter
     * @param \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param QuoteFactory $quoteFactory
     * @param \Inspius\Iscommerce\Helper\Data $helper
     * @param \Inspius\Iscommerce\Model\Client $client
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Event\Manager $manager,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Payment\Model\Checks\SpecificationFactory $specificationFactory,
        \Magento\Quote\Model\Quote\AddressFactory $addressFactory,
        \Magento\Quote\Model\Cart\ShippingMethodConverter $converter,
        \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector,
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Inspius\Iscommerce\Helper\Data $helper,
        \Inspius\Iscommerce\Model\Client $client
    )
    {
        $this->_quoteRepository = $quoteRepository;
        $this->_quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->_productFactory = $productFactory;
        $this->_customerFactory = $customerFactory;
        $this->_orderFactory = $orderFactory;
        $this->_countryFactory = $countryFactory;
        $this->_paymentHelper = $paymentHelper;
        $this->_methodSpecificationFactory = $specificationFactory;
        $this->_addressFactory = $addressFactory;
        $this->_converter = $converter;
        $this->_totalsCollector = $totalsCollector;
        $this->_storeManager = $storeManager;
        $this->_quoteFactory = $quoteFactory;
        $this->_transactionFactory = $transactionFactory;
        parent::__construct($context, $resultJsonFactory, $scopeConfig, $manager, $helper, $client);
    }

    /**
     * decide which action to do
     *
     * @return array
     * @throws \Exception
     */
    public function _getResponse()
    {
        $data = [];
        if ($action = $this->_getParam('task')) {
            switch ($action) {
                case self::ORDER_GET_PRICE:
                    $data = $this->_getPrice();
                    break;
                case self::ORDER_CREATE:
                    $data = $this->_createOrder();
                    break;
                case self::ORDER_GET:
                    $data = $this->_getOrder();
                    break;
                case self::ORDER_LIST_BY_CUSTOMER:
                    $data = $this->_getCustomerOrder();
                    break;
                case self::ORDER_CREATE_CART:
                    $data = $this->_createNewCart();
                    break;
                case self::ORDER_LIST_COUNTRIES:
                    $data = $this->_getCountryList();
                    break;
                default:
                    break;
            }
            return $data;
        }
        throw new \Exception(Message::USER_NO_ROUTE);
    }

    /**
     * create a new cart
     *
     * @return array
     * @throws \Exception
     */
    protected function _createNewCart()
    {
        $cartId = $this->_createCart();
        return [
            'cart_id' => $cartId,
            'price' => $this->_getPrice($cartId),
            'shipping_methods' => $this->_getShippingMethod($cartId),
            'payment_methods' => $this->_getPaymentMethod($cartId)
        ];
    }

    /**
     * create a new cart
     *
     * @return \Zend_Http_Response
     * @throws \Exception
     */
    protected function _createCart()
    {
        /* @var $response \Zend_Http_Response */
        $response = $this->_client->request($this->_url->getBaseUrl() . 'rest/V1/guest-carts', \Zend_Http_Client::POST, [], true, 'undefined');
        if ($response) {
            // response is cart id
            $response = str_replace("\"", "", $response);
            $this->_addCreatedViaAttributeToQuote($response);
            return $response;
        }
        throw new \Exception(Message::CART_CANNOT_CREATE);
    }

    /**
     * add create via attr to cart
     *
     * @param $quoteMask
     */
    private function _addCreatedViaAttributeToQuote($quoteMask)
    {
        $quote = $this->_getCart($quoteMask);
        $quote->setCreatedVia(\Inspius\Iscommerce\Helper\Data::QUOTE_CREATED_VIA_NAME)->save();
    }


    /**
     * get shipping method list
     *
     * @param null $cartId
     * @return array
     * @throws \Exception
     */
    protected function _getShippingMethod($cartId = null)
    {
        if (!$cartId) {
            $cartId = $this->_getParam('cart_id');
            if (!$cartId) {
                throw new \Exception(Message::CART_CANNOT_GET_SHIPPING_METHOD);
            }
        }

        /* @var $cart \Magento\Quote\Model\Quote */
        $cart = $this->_getCart($cartId);

        // add address to order
        $cart = $this->_addAddressToOrder($cart);

        $shippingAddress = $cart->getShippingAddress();
        $shippingAddress->collectShippingRates();
        $shippingRates = $shippingAddress->getGroupedAllShippingRates();
        $methods = [];
        foreach ($shippingRates as $carrierRates) {
            foreach ($carrierRates as $rate) {
                /* @var $rate \Magento\Quote\Model\Quote\Address\Rate */
                $methods[] = $this->_helper->formatShippingMethod($rate);
            }
        }

        return [
            'zones' => [],
            'default' => [
                'zone_id' => 0,
                'zone_name' => "Rest of the World",
                'zone_order' => 0,
                'zone_location' => [],
                'meta_data' => [],
                'shipping_methods' => $methods
            ]
        ];
    }

    /**
     * add address info to order
     *
     * @param $cartId
     * @param null $shippingMethodCode
     * @param null $shippingMethodCarrierCode
     * @throws \Exception
     */
    protected function _addAddressToOrder($cart)
    {
        /* @var $cart \Magento\Quote\Model\Quote */
        $billingAddress = json_decode(stripslashes($this->_getParam('billing')), true);
        $shippingAddress = json_decode(stripslashes($this->_getParam('shipping')), true);

        $billingAddress = $this->_addressFactory->create()->setData([
            'firstname' => $billingAddress['first_name'],
            'lastname' => $billingAddress['last_name'],
            'street' => [
                $billingAddress['address_1'],
                $billingAddress['address_2'],
            ],
            'city' => $billingAddress['city'],
            'region' => $billingAddress['state'],
            'country_id' => $billingAddress['country'],
            'postcode' => $billingAddress['postcode'],
            'email' => $billingAddress['email'],
            'telephone' => $billingAddress['phone']
        ]);

        $shippingAddress = $this->_addressFactory->create()->setData([
            'firstname' => $shippingAddress['first_name'],
            'lastname' => $shippingAddress['last_name'],
            'street' => [
                $shippingAddress['address_1'],
                $shippingAddress['address_2'],
            ],
            'city' => $shippingAddress['city'],
            'region' => $shippingAddress['state'],
            'country_id' => $shippingAddress['country'],
            'postcode' => $shippingAddress['postcode'],
            'telephone' => $shippingAddress['phone'],
            'save_as_billing' => 0,
            'collect_shipping_rates' => 1
        ]);
        $cart->setBillingAddress($billingAddress);
        $cart->setShippingAddress($shippingAddress);

        return $cart->collectTotals()->save();
    }

    /**
     * get payment method list
     *
     * @param null $cartId
     * @return array
     * @throws \Exception
     */
    protected function _getPaymentMethod($cartId = null)
    {
        if (!$cartId) {
            $cartId = $this->_getParam('cart_id');
            if (!$cartId) {
                throw new \Exception(Message::CART_CANNOT_GET_SHIPPING_METHOD);
            }
        }

        /* @var $cart \Magento\Quote\Model\Quote */
        $cart = $this->_getCart($cartId);
        $store = $cart ? $cart->getStoreId() : null;
        $methods = [];
        $isFreeAdded = false;
        $allowedMethods = new \Magento\Framework\DataObject(['cashondelivery', 'banktransfer', 'purchaseorder', 'checkmo', 'free']);
        $canAppliedMethods = new \Magento\Framework\DataObject($this->_paymentHelper->getStoreMethods($store, $cart));
        $this->_eventManager->dispatch('icymobi_check_payment_method', [
            'allowed_methods' => $allowedMethods,
            'can_applied_methods' => $canAppliedMethods,
            'store' => $store,
            'quote' => $cart
        ]);

        foreach ($canAppliedMethods->toArray() as $method) {
            /* @var $method \Magento\Payment\Model\Method\AbstractMethod */
            if ($this->_canUseMethod($method, $cart, $allowedMethods->toArray())) {
                $methods[$method->getCode()] = [
                    'id' => $method->getCode(),
                    'title' => $method->getTitle() ? $method->getTitle() : '',
                    'description' => $method->getConfigData('instructions') ? $method->getConfigData('instructions') : ''
                ];
                if ($method->getCode() == \Magento\Payment\Model\Method\Free::PAYMENT_METHOD_FREE_CODE) {
                    $isFreeAdded = true;
                }
            }
        }
        if (!$isFreeAdded && $cart->getGrandTotal() == 0) {
            /** @var \Magento\Payment\Model\Method\Free $freeMethod */
            $freeMethod = $this->_paymentHelper->getMethodInstance(\Magento\Payment\Model\Method\Free::PAYMENT_METHOD_FREE_CODE);
            if ($freeMethod->isAvailableInConfig()) {
                $methods[$freeMethod->getCode()] = [
                    'id' => $freeMethod->getCode(),
                    'title' => $freeMethod->getTitle(),
                    'description' => $freeMethod->getConfigData('instructions') ? $freeMethod->getConfigData('instructions') : ''
                ];
            }
        }
        return $methods;
    }

    /**
     * Check payment method model
     *
     * @return bool
     */
    private function _canUseMethod($method, $quote, $allowedMethods = [])
    {
        /* @var $method \Magento\Payment\Model\Method\AbstractMethod */
        if (!in_array($method->getCode(), $allowedMethods)) {
            return false;
        }
        return $this->_methodSpecificationFactory->create(
            [
                \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_FOR_COUNTRY,
                \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_FOR_CURRENCY,
                \Magento\Payment\Model\Method\AbstractMethod::CHECK_ORDER_TOTAL_MIN_MAX,
            ]
        )->isApplicable(
            $method,
            $quote
        );
    }

    /**
     * create a cart and get price
     *
     * @param null $cartId
     * @return array
     * @throws \Exception
     */
    protected function _getPrice($cartId = null)
    {
        if (!$cartId) {
            $cartId = $this->_getParam('cart_id');
            if (!$cartId) {
                throw new \Exception(Message::CART_CANNOT_GET_PRICE);
            }
        }

        /* @var $cart \Magento\Quote\Model\Quote */
        $cart = $this->_getCart($cartId);

        // add coupon
        $coupon = $this->_getParam('coupon');
        $cart->setCouponCode($coupon);

        // add product
        $this->_addProductsToCart($cart);

        // call api to get price
        $total = $this->_client->request($this->_url->getBaseUrl() . "rest/V1/guest-carts/{$cartId}/totals", \Zend_Http_Client::GET);

        return [
            'discount_total' => $total['discount_amount'],
            'subtotal' => $total['subtotal'],
            'total' => $total['grand_total'],
        ];
    }

    /**
     * get cart by cart mask
     *
     * @param $cartId
     * @return \Magento\Quote\Model\Quote
     * @throws \Exception
     */
    protected function _getCart($cartId)
    {
        /* @var $cart \Magento\Quote\Model\Quote */
        $cart = $this->_quoteRepository->get($this->_getCartIdByMask($cartId));
        if ($cart->getId()) {
            return $cart;
        }
        throw new \Exception(Message::CART_NOT_FOUND);
    }

    /**
     * get cart id using quote mask
     *
     * @param $cartMask
     * @return mixed
     */
    private function _getCartIdByMask($cartMask)
    {
        /* @var $quoteMaskObj \Magento\Quote\Model\QuoteIdMask */
        $quoteMaskObj = $this->_quoteIdMaskFactory->create()->load($cartMask, 'masked_id');
        return $quoteMaskObj->getQuoteId();
    }

    /**
     * add product to cart
     *
     * @param $cartId
     * @throws \Exception
     */
    protected function _addProductsToCart($cart)
    {
        $jsonItems = $this->_getParam('line_items');
        if (!$jsonItems) throw new \Exception(Message::CART_NO_ITEM_FOUND);

        // decode the line items to array then get the ids to get the collection
        $items = json_decode(stripslashes($jsonItems), true);

        /* @var $cart \Magento\Quote\Model\Quote */
        $cart->removeAllItems();

        $productIds = [];
        foreach ($items as $item) {
            $productIds[] = isset($item['variation_id']) ? $item['variation_id'] : $item['product_id'];
        }

        /* @var $productCollection \Magento\Catalog\Model\ResourceModel\Product\Collection */
        $productCollection = $this->_productFactory->create()->getCollection()->addIdFilter($productIds)->addAttributeToSelect('price');

        foreach ($items as $item) {
            $productId = isset($item['variation_id']) ? $item['variation_id'] : $item['product_id'];
            /* @var $product \Magento\Catalog\Model\Product */
            $product = $productCollection->getItemById($productId);
            $cart->addProduct($product, $item['quantity']);
        }
        return $cart->collectTotals()->save();
    }

    /**
     * create order
     *
     * @return array
     * @throws \Exception
     */
    protected function _createOrder()
    {
        $cartId = $this->_getParam('cart_id');
        if (!$cartId) {
            throw new \Exception(Message::CART_NOT_FOUND);
        }

        /* @var $cart \Magento\Quote\Model\Quote */
        // get cart
        $cart = $this->_getCart($cartId);

        // add coupon
        $coupon = $this->_getParam('coupon');
        $cart->setCouponCode($coupon);

        // add product
        $this->_addProductsToCart($cart);

        // set payment method add address.
        $this->_setShippingMethod($cartId);

        // set payment method and place order
        $orderId = $this->_placeOrder($cartId);

        $orderId = str_replace("\"", "", $orderId);
        // add an event here
        $this->_eventManager->dispatch('icymobi_place_order_event', ['order_id' => $orderId, 'params' => $this->_request->getParams()]);

        return $this->_getOrderDetail($orderId);
    }

    /**
     * set shipping method to order
     *
     * @param $cartId
     * @return bool
     * @throws \Exception
     */
    protected function _setShippingMethod($cartId)
    {
        $shipping = $this->_getParam('shipping_lines');
        $shipping = json_decode(stripslashes($shipping), true);
        $shipping = !empty($shipping) ? $shipping[0] : [];

        if (!empty($shipping)) {
            if ($shipping['method_id'] && $shipping['carrier_id']) {
                // add address to order
                /* @var $cart \Magento\Quote\Model\Quote */
                $cart = $this->_getCart($cartId);
                $address = $cart->getShippingAddress();
                $address->setShippingMethod($shipping['method_id']);

                try {
                    $this->_totalsCollector->collectAddressTotals($cart, $address);
                } catch (\Exception $e) {
                    throw new \Exception(__('Unable to save address. Please, check input data.'));
                }

                if (!$address->getShippingRateByCode($address->getShippingMethod())) {
                    throw new \Exception(__('Carrier not found'));
                }

                if (!$cart->validateMinimumAmount($cart->getIsMultiShipping())) {
                    throw new \Exception($this->_scopeConfig->getValue(
                        'sales/minimum_order/error_message',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        $cart->getStoreId()
                    ));
                }

                try {
                    $address->save();
                    $cart->collectTotals()->save();
                } catch (\Exception $e) {
                    throw new \Exception(__('Unable to save shipping information. Please, check input data.'));
                }

                return true;
            }
        }
        throw new \Exception(Message::CART_CANNOT_SET_SHIPPING_METHOD);
    }

    /**
     * place order
     *
     * @param $cartId
     * @return mixed
     * @throws \Exception
     */
    protected function _placeOrder($cartId)
    {
        $paymentMethod = $this->_getParam('payment_method');
        $customerId = $this->_getParam('customer_id');

        if ($paymentMethod) {
            $data = [
                'cartId' => $cartId,
                'paymentMethod' => [
                    'method' => $paymentMethod
                ]
            ];

            if ($customerId) {
                $customer = $this->_customerFactory->create()->load($customerId);
                if ($customer->getId()) {
                    $storeId = $this->_storeManager->getStore()->getId();
                    if (!in_array($storeId, $customer->getSharedStoreIds())) {
                        throw new \Exception(__('Cannot assign customer to the given cart. The cart belongs to different store.'));
                    }

                    try {
                        // set active quote of customer to false
                        $quote = $this->_quoteRepository->getActiveForCustomer($customer->getId());
                        if ($quote->getId()) {
                            $quote->setIsActive(false)->save();
                        }
                    } catch (NoSuchEntityException $exception) {
                        // no active quote for current customer, no problem
                    }

                    // set customer to current cart/quote and create order
                    $convertedCartId = $this->_getCartIdByMask($cartId);

                    $cart = $this->_quoteFactory->create()->load($convertedCartId);
                    $cart->setIsActive(true)
			->setCustomerId($customer->getId())
                        ->setCustomerEmail($customer->getEmail())
                        ->setCustomerFirstname($customer->getFirstname())
                        ->setCustomerLastname($customer->getLastname())
                        ->setCustomerIsGuest(1)
                        ->save();

                    $response = $this->_client->request($this->_url->getBaseUrl() . "rest/V1/carts/{$convertedCartId}/order", \Zend_Http_Client::PUT, $data);
                } else {
                    throw new \Exception(Message::ORDER_CANNOT_FIND_CUSTOMER);
                }
            } else {
                $response = $this->_client->request($this->_url->getBaseUrl() . "rest/V1/guest-carts/{$cartId}/order", \Zend_Http_Client::PUT, $data);
            }
            if ($response) {
                if (isset($response['message'])) {
                    throw new \Exception($response['message']);
                }
                $this->_deleteQuoteMask($cartId);
                return $response;
            }
            throw new \Exception(Message::ORDER_CANNOT_CREATE);
        }
        throw new \Exception(Message::ORDER_INVALID_DATA);
    }

    /**
     * delete quote mask obj after placing order
     *
     * @param $cartMask
     */
    private function _deleteQuoteMask($cartMask)
    {
        $quoteMaskObj = $this->_quoteIdMaskFactory->create()->load($cartMask, 'masked_id');
        if ($quoteMaskObj->getId()) {
            $quoteMaskObj->delete();
        }
    }

    /**
     * get order by id
     *
     * @return array
     * @throws \Exception
     */
    protected function _getOrder()
    {
        $orderId = $this->_getParam('id');
        if ($orderId) {
            return $this->_getOrderDetail($orderId);
        }
        throw new \Exception(Message::ORDER_NOT_FOUND);
    }

    /**
     * get order by id
     *
     * @param $id
     * @return array
     */
    protected function _getOrderDetail($id)
    {
        $id = str_replace("\"", "", $id);
        /* @var $order \Magento\Sales\Model\Order */
        $order = $this->_orderFactory->create()->getCollection()->addFieldToSelect('*')->addFieldToFilter('entity_id', ['eq' => $id])->getFirstItem();
        if ($order->getId()) {
            return $this->_helper->formatOrderObjectDetail($order);
        }
        throw new \Exception(Message::ORDER_NOT_FOUND);
    }

    /**
     * get orders by customer id
     *
     * @return array
     * @throws \Exception
     */
    protected function _getCustomerOrder()
    {
        $customerId = $this->_getParam('customer_id');
        $pageSize = $this->_getParam('per_page');
        $page = $this->_getParam('page');
        if ($customerId) {
            $formattedOrders = [];
            $orders = $this->_orderFactory->create()
                ->getCollection()
                ->addFieldToSelect('*')
                ->addFieldToFilter('customer_id', ['eq' => $customerId]);
            if ($pageSize) $orders->setPageSize($pageSize);
            if ($page) $orders->setCurPage($page);

            foreach ($orders as $order) {
                /* @var $order \Magento\Sales\Model\Order */
                $formattedOrders[] = $this->_helper->formatOrderObjectDetail($order);
            }

            return $formattedOrders;
        }
        throw new \Exception(Message::ORDER_NOT_FOUND);
    }

    /**
     * get country list
     *
     * @return array
     */
    protected function _getCountryList()
    {
        $formattedCountries = [];
        $countries = $this->_countryFactory->create()->getCollection();
        foreach ($countries as $country) {
            /* @var $country \Magento\Directory\Model\Country */
            $formattedRegions = [];
            $regions = $country->getRegions();
            foreach ($regions as $region) {
                /* @var $region \Magento\Directory\Model\Region */
                $formattedRegions[$region->getCode()] = $region->getName();
            }

            $formattedCountries[$country->getId()] = [
                'id' => $country->getId(),
                'name' => $country->getName(),
                'state' => $formattedRegions
            ];
        }
        return $formattedCountries;
    }

}
