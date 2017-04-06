<?php
/**
 * Copyright © 2016 Inspius. All rights reserved.
 * Author: Phong Nguyen
 * Author URI: http://inspius.com
 */
namespace Inspius\Iscommerce\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    const QUOTE_CREATED_VIA_NAME = 'Icymobi';

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $_categoryFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var \Magento\Review\Model\RatingFactory
     */
    protected $_ratingFactory;
	
	/**
	* @var \Magento\Catalog\Helper\Image
	*/
    protected $_productImageHelper;

    /**
     * @var \Magento\Shipping\Model\CarrierFactory
     */
    protected $_carrierFactory;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $_paymentHelper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Data constructor.
     * 
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Review\Model\RatingFactory $ratingFactory
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Review\Model\RatingFactory $ratingFactory,
	    \Magento\Catalog\Helper\Image $productImageHelper,
        \Magento\Shipping\Model\CarrierFactory $carrierFactory,
        \Magento\Payment\Helper\Data $paymentHelper,
		\Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItemRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->_productFactory = $productFactory;
        $this->_categoryFactory = $categoryFactory;
        $this->_ratingFactory = $ratingFactory;
	    $this->_productImageHelper = $productImageHelper;
        $this->_carrierFactory = $carrierFactory;
        $this->_paymentHelper = $paymentHelper;
		$this->_stockItemRepository = $stockItemRepository;
        $this->_scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * return formatted category list
     * 
     * @param \Magento\Catalog\Model\Category $category
     * @return array
     */
    public function formatCategory(\Magento\Catalog\Model\Category $category)
    {
        return [
            'id' => (int)$category->getId(),
            'name' => $category->getName(),
            'slug' => $category->getUrlKey(),
            'parent' => $category->getParentId(),
            'description' => $category->getDescription() ? $category->getDescription() : '',
            'display' => 'default',
            'menu_order' => $category->getPosition(),
            'count' => $category->getProductCount(),
            'image' => [
                'src' => $category->getImageUrl() ? $category->getImageUrl() : '',
                'title' => $category->getImage() ? $category->getImage() : '',
                'alt' => '',
            ]
        ];
    }

    /**
     * return formatted customer info
     * 
     * @param \Magento\Customer\Model\Customer $customer
     * @return array
     */
    public function formatCustomer(\Magento\Customer\Model\Customer $customer)
    {
        return [
            'id' => $customer->getId(),
            'email' => $customer->getEmail(),
            'first_name' => $customer->getFirstname(),
            'last_name' => $customer->getLastname(),
            'avatar' => null,
            'avatar_url' => null,
            'username' => null,
            'billing' => $customer->getDefaultBillingAddress() ? $this->formatAddress($customer, $customer->getDefaultBillingAddress()) : [],
            'shipping' => $customer->getDefaultShippingAddress() ? $this->formatAddress($customer, $customer->getDefaultShippingAddress()) : [],
        ];
    }

    /**
     * return formatted address
     * 
     * @param \Magento\Customer\Model\Customer $customer
     * @param \Magento\Customer\Model\Address $address
     * @return array
     */
    public function formatAddress(\Magento\Customer\Model\Customer $customer, \Magento\Customer\Model\Address $address)
    {
        return [
            "first_name" => $address ? $address->getFirstname() : '',
            "last_name" => $address ? $address->getLastname() : '',
            "company" => $address ? $address->getCompany() : '',
            "address_1" => $address ? $address->getStreetLine(1) : '',
            "address_2" => $address ? $address->getStreetLine(2) : '',
            "city" => $address ? $address->getCity() : '',
            "state" => $address ? $address->getRegionCode() ? $address->getRegionCode() : $address->getRegion() : '',
            "postcode" => $address ? $address->getPostcode() : '',
            "country" => $address ? $address->getCountryId() : '',
            "email" => $address ? $customer->getEmail() : '',
            "phone" => $address ? $address->getTelephone() : '',
        ];
    }

    /**
     * set data to address object
     * 
     * @param \Magento\Customer\Model\Address $address
     * @param $data
     * @return \Magento\Customer\Model\Address
     */
    public function setAddressData(\Magento\Customer\Model\Address $address, $data)
    {
        $address->setData([
            'firstname' => $data['first_name'] ? $data['first_name'] : '',
            'lastname' => $data['last_name'] ? $data['last_name'] : '',
            'email' => $data['email'] ? $data['email'] : '',
            'telephone' => $data['first_name'] ? $data['first_name'] : '',
            'company' => isset($data['company']) && $data['company'] ? $data['company'] : '',
            'street' => [
                $data['address_1'] ? $data['address_1'] : '',
                $data['address_2'] ? $data['address_2'] : ''
            ],
            'city' => $data['city'] ? $data['city'] : '',
            'region' => '',
            'region_id' => $data['state'] ? $data['state'] : '',
            'postcode' => $data['postcode'] ? $data['postcode'] : '',
            'country_id' => $data['country'] ? $data['country'] : '',
        ]);
        return $address;
    }

    /**
     * format magento shipping method to wc shipping method
     *
     * @param $method
     * @return array
     */
    public function formatShippingMethod($method)
    {
        /* @var $method \Magento\Quote\Model\Quote\Address\Rate */
        return [
            'min_amount' => 0,
            'requires' => '',
            'supports' => [],
            'carrier_id' => $method->getCarrier(),
            'id' => $method->getCode(),
            'method_title' => $method->getMethodTitle(),
            'method_description' => '',
            'enable' => 'yes',
            'title' => $method->getMethodTitle(),
            'rates' => [],
            'tax_status' => 'taxable',
            'fee' => null,
            'cost' => $method->getPrice(),
            'minimum_fee' => null,
            'instance_id' => null,
            "instance_form_fields" => [],
            "instance_settings" => [
                "title" => $method->getMethodTitle(),
                "requires" => "",
                "min_amount" => 0
            ],
            "availability" => null,
            "countries" => [],
            "plugin_id" => "",
            "errors" => [],
            "settings" => [],
            "form_fields" => [],
            "method_order" => 1,
            "has_settings" => true
        ];
    }

    /**
     * return formatted product
     * 
     * @param \Magento\Catalog\Model\Product $product
     * @param array $options
     * @return array
     */
    public function formatProduct(\Magento\Catalog\Model\Product $product, $options = [])
    {
        $single = ['simple', 'virtual', 'downloadable'];
        $extensionAttributes = $product->getExtensionAttributes();
        $productOptions = $extensionAttributes ? $this->_getOptions($product, $extensionAttributes->getConfigurableProductOptions(), $options) : $this->_getOptions($product, [], $options);

        /* @var $rating \Magento\Review\Model\Rating */
        $rating = $this->_ratingFactory->create();
        $ratingData = $rating->getEntitySummary($product->getId())->getData();

		$averageRating = $ratingWidth = 0;
        $ratingCount = isset($ratingData['count']) ? $ratingData['count'] : 0;
        $ratingSum = isset($ratingData['sum']) ? $ratingData['sum'] : 0;
		if($ratingCount > 0 && $ratingSum > 0) {
            $ratingWidth = $ratingSum / $ratingCount;
            $averageRating = strval(number_format(floatval($ratingWidth / 20), 2));
		}

        $stockItem = $this->_stockItemRepository->get($product->getId());

        return [
            'id' => (int)$product->getId(),
            'name' => $product->getName(),
            'slug' => $product->getUrlKey(),
            'permalink' => $product->getProductUrl(),
            'date_created' => $product->getCreatedAt(),
            'date_modified' => $product->getUpdatedAt(),
            'type' => !in_array($product->getTypeId(), $single) ? 'variable' : 'single',
            'status' => $product->getIsSalable() ? 'publish' : 'private',
            'featured' => $product->getIsFeatured() ? true : false,
            'on_sale' => $product->getIsSale() ? true : false,
            'new' => $product->getIsNew() ? true : false,

            'catalog_visibility' => $product->getVisibility() > 1 ? 'visible' : '',
            'description' => $product->getDescription(),
            'short_description' => $product->getShortDescription(),
            'sku' => $product->getSku(),
            'price' => $product->getFinalPrice(),
            'regular_price' => $product->getPrice(),
            'sale_price' => $product->getSpecialPrice() ? $product->getSpecialPrice() : '',
            'date_on_sale_from' => $product->getSpecialFromDate() ? $product->getSpecialFromDate() : '',
            'date_on_sale_to' => $product->getSpecialToDate() ? $product->getSpecialToDate() : '',
            'price_html' => '',

            'purchasable' => $product->getIsSalable() ? true : false,
            'total_sales' => '',
            'virtual' => $product->getTypeId() == 'virtual' ? true : false,
            'downloadable' => $product->getTypeId() == 'downloadable' ? true : false,
            'downloads' => [],
            'download_limit' => -1,
            'download_expiry' => -1,
            'download_type' => 'standard',
            'external_url' => '',
            'button_text' => '',

            'tax_status' => '',
            'tax_class' => $product->getAttributeText('tax_class_id'),
            'manage_stock' => $stockItem && $stockItem->getManageStock() ? true : false,
            'stock_quantity' => $stockItem ? $stockItem->getQty() : 0,
            'in_stock' => $stockItem && $stockItem->getIsInStock() ? true : false,
//			'manage_stock' => $stockItemRepository->getManageStock() ? true : false,
//			'stock_quantity' => $stockItemRepository->getQty(),
//			'in_stock' => $stockItemRepository->getIsInStock() ? true : false,
            'backorders' => 'no',
            "backorders_allowed" => false,
            "backordered" => false,
            "sold_individually" => in_array($product->getTypeId(), $single),
            "weight" => $product->getWeight() ? $product->getWeight() : 0,
            "dimensions" => [
                "length" => "",
                "width" => "",
                "height" => ""
            ],

            "shipping_required" => true,
            "shipping_taxable" => true,
            "shipping_class" => "",
            "shipping_class_id" => 0,
            "reviews_allowed" => true,
            "average_rating" => $averageRating,
            "rating_count" => $ratingCount,
            "rating_star_html" => "\n<div class=\"rate\">\n<span style=\"width: ".$ratingWidth."%;\"></span>\n</div>\n<span class=\"count\">(".$ratingCount.")</span>\n",
            "related_ids" => $product->getRelatedProductIds(),
            "upsell_ids" => $product->getUpSellProductIds(),
            "cross_sell_ids" => $product->getCrossSellProductIds(),
            "parent_id" => 0,
            "purchase_note" => "",

            "categories" => $this->_getCategory($product->getCategoryCollection()),
            "tags" => [],
            "images" => $this->_getImages($product, $product->getMediaGalleryImages()),
            "attributes" => $productOptions,
            "default_attributes" => [],
            "variations" => $extensionAttributes ? $this->_getVariations($extensionAttributes->getConfigurableProductLinks(), $productOptions) : [],
            "grouped_products" => [],
            "menu_order" => 0,
        ];
    }

    /**
     * return formatted variations
     * 
     * @param $variationIds
     * @param array $productOptions
     * @return array
     */
    private function _getVariations($variationIds, $productOptions = [])
    {
        $variations = [];
		
		if(is_array($variationIds)) {
			$productCollection = $this->_productFactory->create()->getCollection()->addIdFilter($variationIds)->addAttributeToSelect('*');
			foreach ($productCollection as $product) {
				/* @var $product \Magento\Catalog\Model\Product */
				$product = $this->_productFactory->create()->load($product->getId());
				$variations[] = $this->formatProduct($product, $productOptions);
			}
		}
		
        return $variations;
    }

    /**
     * return formatted product options
     * 
     * @param \Magento\Catalog\Model\Product $product
     * @param array $optionsList
     * @param array $productOptions
     * @return array
     */
    private function _getOptions(\Magento\Catalog\Model\Product $product, $optionsList = [], $productOptions = [])
    {
        $options = [];

        if (!empty($productOptions)) {
            foreach ($productOptions as $option) {
                $options[] = [
                    'id' => $option['id'],
                    'name' => $option['name'],
                    'option' => $product->getData(strtolower($option['name'])),
					'option_name' => $product->getAttributeText(strtolower($option['name']))
                ];
            }
        } else if (!empty($optionsList)) {
            foreach ($optionsList as $option) {
                /* @var $option \Magento\ConfigurableProduct\Api\Data\OptionInterface */
                $optionValues = [];
                foreach ($option->getOptions() as $value) {
                    $optionValues[] = [
                        'name' => $value['label'],
                        'value' => $value['value_index']
                    ];
                }
                $options[] = [
                    'id' => $option->getId(),
                    'name' => $option->getLabel(),
                    'position' => $option->getPosition(),
                    'visible' => true,
                    'variation' => true,
                    'options' => $optionValues,
                    'type' => 'dropdown'
                ];
            }
        }

        return $options;
    }

    /**
     * return formatted category list for product
     * 
     * @param $categoryCollection
     * @return array
     */
    private function _getCategory($categoryCollection)
    {
        $categories = [];
        $categoryCollection->addAttributeToSelect('*');
        foreach ($categoryCollection as $category) {
            /* @var $category \Magento\Catalog\Model\Category */
            $categories[] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'slug' => $category->getUrlKey()
            ];
        }
        return $categories;
    }

    /**
     * return list of image of a product
     * 
     * @param $gallery
     * @return array
     */
    private function _getImages($product, $gallery)
    {
        $images = [];
        $width = $this->_scopeConfig->getValue('icymobi_config/product_images/width', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) ?
            $this->_scopeConfig->getValue('icymobi_config/product_images/width', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) :
            500;
        $height = $this->_scopeConfig->getValue('icymobi_config/product_images/height', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) ?
            $this->_scopeConfig->getValue('icymobi_config/product_images/height', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) :
            500;

        if ($gallery) {
            foreach ($gallery as $image) {
                if ($image->getDisabled() == 0 && $image->getMediaType() == 'image') {
                    $resizedImage = $this->_productImageHelper
                        ->init($product, 'product_base_image')
                        ->setImageFile($image->getFile())
                        ->resize($width, $height)
                        ->keepAspectRatio(true);
                    $images[] = [
                        'id' => $image->getId(),
                        'src' => $resizedImage->getUrl(),
                        'name' => $image->getLabel(),
                        'alt' => $image->getLabel(),
                        'position' => $image->getPosition(),
						'file' => $image->getFile()
                    ];
                }
            }
			$mainImage = $product->getImage();
			for($i = 0; $i < count($images); $i++) {
				if($images[$i]['file'] === $mainImage) {
					//swap main image to 0
					$mainImage = $images[$i];
					$images[$i] = $images[0];
					$images[0] = $mainImage;
				}
			}
        }

        return $images;
    }

    /**
     * DEPRECATED format order based on api order result
     *
     * @param $order
     * @return array
     */
    private function _formatOrderDetail($order)
    {
        $items = $order['items'];
        $formattedItems = [];
        foreach ($items as $item) {
            $formattedItems[] = [
                'id' => $item['quote_item_id'],
                'name' => $item['name'],
                'sku' => $item['sku'],
                'product_id' => $item['product_id'],
                'variation_id' => $item['product_id'],
                'quantity' => $item['qty_ordered'],
                'tax_class' => '',
                'price' => $item['price'],
                'subtotal' => $item['row_total'],
                'subtotal_tax' => $item['tax_amount'],
                'total' => $item['row_total'],
                'total_tax' => $item['tax_amount'],
                'taxes' => [],
                'meta' => []
            ];
        }

        $billing = $order['billing_address'];
        $payment = $order['payment'];
        $shipping = $order['extension_attributes']['shipping_assignments'][0]['shipping'];
        return [
            'id' => $order['entity_id'],
            'parent_id' => 0,
            'status' => $order['status'],
            'order_key' => $order['increment_id'],
            'number' => $order['increment_id'],
            'currency' => $order['order_currency_code'],
            'date_created' => $order['created_at'],
            'date_modified' => $order['updated_at'],
            'customer_id' => $order['customer_id'],

            'discount_total' => $order['discount_amount'],
            'discount_tax' => $order['discount_tax_compensation_amount'],
            'shipping_total' => $order['shipping_amount'],
            'shipping_tax' => $order['shipping_tax_amount'],
            'cart_tax' => $order['tax_amount'],
            'total' => $order['base_grand_total'],
            'total_tax' => $order['tax_amount'],

            'billing' => [
                'first_name' => $billing['firstname'],
                'last_name' => $billing['lastname'],
                'company' => isset($billing['company']) ? $billing['company'] : '',
                'address_1' => $billing['street'][0],
                'address_2' => isset($billing['street'][1]) ? $billing['street'][1] : '',
                'city' => $billing['city'],
                'state' => $billing['region_id'],
                'postcode' => $billing['postcode'],
                'country' => $billing['country_id'],
                'email' => $billing['email'],
                'phone' => $billing['telephone']
            ],

            'shipping' => [
                'first_name' => $shipping['address']['firstname'],
                'last_name' => $shipping['address']['lastname'],
                'company' => isset($shipping['address']['company']) ? $shipping['address']['company'] : '',
                'address_1' => $shipping['address']['street'][0],
                'address_2' => isset($shipping['address']['street'][1]) ? $shipping['address']['street'][1] : '',
                'city' => $shipping['address']['city'],
                'state' => $shipping['address']['region_id'],
                'postcode' => $shipping['address']['postcode'],
                'country' => $shipping['address']['country_id'],
                'email' => $shipping['address']['email'],
                'phone' => $shipping['address']['telephone']
            ],

            'payment_method' => $payment['method'],
            'payment_method_title' => $payment['additional_information'][0] ? $payment['additional_information'][0] : '',
            'transaction_id' => '',
            'customer_note' => '',
            'line_items' => $formattedItems,
            'tax_lines' => [],
            'shipping_lines' => [
                [
                    'id' => '',
                    'method_title' => '',
                    'method_id' => $shipping['method'],
                    'total' => $shipping['total']['shipping_amount'],
                    'total_tax' => $shipping['total']['shipping_tax_amount'],
                    'taxes' => []
                ]
            ],
            'fee_lines' => [],
            'coupon_lines' => [
                [
                    'id' => '',
                    'code' => $order['coupon_code'],
                    'discount' => '',
                    'discount_tax' => ''
                ]
            ],
            'refunds' => []
        ];
    }

    /**
     * format order
     *
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function formatOrderObjectDetail(\Magento\Sales\Model\Order $order) {
        $formattedItems = [];
        $items =$order->getAllItems();
        foreach ($items as $item) {
            $formattedItems[] = [
                'id' => (int)$item->getQuoteItemId(),
                'name' => $item->getName(),
                'sku' => $item->getSku(),
                'product_id' => $item->getProductId(),
                'variation_id' => $item->getProductId(),
                'quantity' => $item->getQtyOrdered(),
                'tax_class' => '',
                'price' => $item->getPrice(),
                'subtotal' => $item->getRowTotal(),
                'subtotal_tax' => $item->getTaxAmount(),
                'total' => $item->getRowTotal(),
                'total_tax' => $item->getTaxAmount(),
                'taxes' => [],
                'meta' => []
            ];
        }

        $billing = $order->getBillingAddress();
        $shipping = $order->getShippingAddress();
        $payment = $order->getPayment();

        /* @var $paymentMethod \Magento\Payment\Model\MethodInterface */
        $paymentMethod = $this->_paymentHelper->getMethodInstance($payment->getMethod());

        /* @var $shippingMethodInfo \Magento\Framework\DataObject */
        $shippingMethodInfo = $order->getShippingMethod(true);
        /* @var $shippingMethod \Magento\Shipping\Model\Carrier\AbstractCarrier */
        $shippingMethod = $this->_carrierFactory->get($shippingMethodInfo->getData('carrier_code'));

        return [
            'id' => (int)$order->getId(),
            'parent_id' => 0,
            'status' => $order->getStatus(),
            'order_key' => $order->getIncrementId(),
            'number' => $order->getIncrementId(),
            'currency' => $order->getOrderCurrencyCode(),
            'date_created' => $order->getCreatedAt(),
            'date_modified' => $order->getUpdatedAt(),
            'customer_id' => $order->getCustomerId(),

            'discount_total' => abs($order->getDiscountAmount()),
            'discount_tax' => abs($order->getDiscountTaxCompensationAmount()),
            'shipping_total' => $order->getShippingAmount(),
            'shipping_tax' => $order->getShippingTaxAmount(),
            'cart_tax' => $order->getTaxAmount(),
            'total' => $order->getGrandTotal(),
            'total_tax' => $order->getTaxAmount(),

            'billing' => [
                'first_name' => $billing->getFirstname(),
                'last_name' => $billing->getLastname(),
                'company' => $billing->getCompany(),
                'address_1' => $billing->getStreetLine(1),
                'address_2' => $billing->getStreetLine(2),
                'city' => $billing->getCity(),
                'state' => $billing->getRegionId(),
                'postcode' => $billing->getPostcode(),
                'country' => $billing->getCountryId(),
                'email' => $billing->getEmail(),
                'phone' => $billing->getTelephone()
            ],

            'shipping' => [
                'first_name' => $shipping->getFirstname(),
                'last_name' => $shipping->getLastname(),
                'company' => $shipping->getCompany(),
                'address_1' => $shipping->getStreetLine(1),
                'address_2' => $shipping->getStreetLine(2),
                'city' => $shipping->getCity(),
                'state' => $shipping->getRegionId(),
                'postcode' => $shipping->getPostcode(),
                'country' => $shipping->getCountryId(),
                'email' => $shipping->getEmail(),
                'phone' => $shipping->getTelephone()
            ],

            'payment_method' => $payment->getMethod(),
            'payment_method_title' => $paymentMethod->getTitle(),
            'transaction_id' => '',
            'customer_note' => '',
            'line_items' => $formattedItems,
            'tax_lines' => [],
            'shipping_lines' => [
                [
                    'id' => '',
                    'method_title' => $shippingMethod->getConfigData('title'),
                    'method_id' => $shippingMethodInfo->getData('method'),
                    'total' => $order->getShippingAmount(),
                    'total_tax' => $order->getShippingTaxAmount(),
                    'taxes' => []
                ]
            ],
            'fee_lines' => [],
            'coupon_lines' => [
                [
                    'id' => '',
                    'code' => $order->getCouponCode(),
                    'discount' => '',
                    'discount_tax' => ''
                ]
            ],
            'refunds' => []
        ];
    }
}