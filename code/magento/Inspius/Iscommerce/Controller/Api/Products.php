<?php
/**
 * Copyright © 2016 Inspius. All rights reserved.
 * Author: Phong Nguyen
 * Author URI: http://inspius.com
 */
namespace Inspius\Iscommerce\Controller\Api;

use Inspius\Iscommerce\Model\Message;

class Products extends AbstractApi
{
    const REQUEST_SINGLE = 'single';
    const REQUEST_CATEGORY = 'category';
    const REQUEST_TYPE = 'type';
    const REQUEST_SEARCH = 'search';

    protected $_productGroup = ['featured', 'onsale', 'best_seller', 'most_view', 'new'];
    
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $_categoryFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Report\Bestsellers\CollectionFactory
     */
    protected $_bestsellerCollectionFactory;

    /**
     * @var \Magento\Reports\Model\ResourceModel\Report\Product\Viewed\CollectionFactory
     */
    protected $_mostViewCollectionFactory;

    /**
     * @var \Magento\Store\Model\StoreManager
     */
    protected $_storeManager;

    /**
     * Products constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Sales\Model\ResourceModel\Report\Bestsellers\CollectionFactory $bestsellerCollectionFactory
     * @param \Magento\Reports\Model\ResourceModel\Report\Product\Viewed\CollectionFactory $mostViewCollectionFactory
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param \Inspius\Iscommerce\Helper\Data $helper
     * @param \Inspius\Iscommerce\Model\Client $client
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Sales\Model\ResourceModel\Report\Bestsellers\CollectionFactory $bestsellerCollectionFactory,
        \Magento\Reports\Model\ResourceModel\Report\Product\Viewed\CollectionFactory $mostViewCollectionFactory,
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Event\Manager $manager,
        \Inspius\Iscommerce\Helper\Data $helper,
        \Inspius\Iscommerce\Model\Client $client
    )
    {
        $this->_categoryFactory = $categoryFactory;
        $this->_productFactory = $productFactory;
        $this->_bestsellerCollectionFactory = $bestsellerCollectionFactory;
        $this->_mostViewCollectionFactory = $mostViewCollectionFactory;
        $this->_storeManager = $storeManager;
        parent::__construct($context, $resultJsonFactory, $scopeConfig, $manager, $helper, $client);
    }

    /**
     * decide which action to handle
     * 
     * @return array
     * @throws \Exception
     */
    public function _getResponse()
    {
        $data = [];
        $type = $this->_getParam('type');
        $param = $this->_getParam('param');
        $param2 = $this->_getParam('param2');

        // additional params
        $defaultOrder = $this->_getSetting('icymobi_config/product_display/order_by') ? $this->_getSetting('icymobi_config/product_display/order_by') : 'entity_id';
        $defaultOrderDir = $this->_getSetting('icymobi_config/product_display/order') ? $this->_getSetting('icymobi_config/product_display/order') : 'desc';

        $orderBy = $this->_request->getParam('orderby', $defaultOrder);
        $order = $this->_request->getParam('order', $defaultOrderDir);
        $page = $this->_request->getParam('page', 1);
        $perPage = $this->_request->getParam('per_page', 10);

        // get product by type
        switch ($type) {
            case self::REQUEST_TYPE:
                if ($param && in_array(strtolower($param), $this->_productGroup)) {
                    $fn = '_get' . ucfirst(str_replace('_', '', $param));
                    $data = $this->$fn($page, $perPage);
                }
                break;
            case self::REQUEST_SINGLE:
                if ($param && is_numeric($param)) {
                    $data = $this->_getSingleProduct($param);
                }
                break;
            case self::REQUEST_CATEGORY:
                if ($param && is_numeric($param)) {
                    $data = $this->_getCategoryProducts($param, $order, $orderBy, $page, $perPage);
                }
                break;
            case self::REQUEST_SEARCH:
                if ($param) {
                    if ($param2 && $param2 !== 'all') {
                        $data = $this->_searchProduct($param, $param2, $order, $orderBy, $page, $perPage);
                    } else {
                        $data = $this->_searchProduct($param, 'all', $order, $orderBy, $page, $perPage);
                    }
                }
                break;
            default:
                $data = $this->_getAllProduct($order, $orderBy, $page, $perPage);
                break;
        }

        return $data;
    }

    /**
     * return all products
     * 
     * @param string $order
     * @param string $orderBy
     * @param int $page
     * @param int $perPage
     * @return array
     */
    protected function _getAllProduct($order = 'desc', $orderBy = 'entity_id', $page = 1, $perPage = 10)
    {
        $products = [];
        /* @var $productCollection \Magento\Catalog\Model\ResourceModel\Product\Collection */
        $productCollection = $this->_productFactory->create()->getCollection();
        $productCollection = $this->_addAdditionalParamToCollection($productCollection, $order, $orderBy, $page, $perPage);
        foreach ($productCollection as $product) {
            /* @var $product \Magento\Catalog\Model\Product */
            $product = $this->_productFactory->create()->load($product->getId());
            $products[] = $this->_helper->formatProduct($product);
        }
        return $products;
    }

    /**
     * return product by id
     * 
     * @param $productId
     * @return array
     * @throws \Exception
     */
    protected function _getSingleProduct($productId)
    {
        /* @var $product \Magento\Catalog\Model\Product */
        $product = $this->_productFactory->create()->load($productId);
        if ($product->getId()) {
            return $this->_helper->formatProduct($product);
        }
        throw new \Exception(Message::PRODUCT_NOT_FOUND);
    }

    /**
     * return product by category
     * 
     * @param $categoryId
     * @param string $order
     * @param string $orderBy
     * @param int $page
     * @param int $perPage
     * @return array
     * @throws \Exception
     */
    protected function _getCategoryProducts($categoryId, $order = 'desc', $orderBy = 'entity_id', $page = 1, $perPage = 10)
    {
        $category = $this->_categoryFactory->create()->load($categoryId);
        if ($category->getId()) {
            $products = [];
            /* @var $productCollection \Magento\Catalog\Model\ResourceModel\Product\Collection */
            $productCollection = $category->getProductCollection();
            $productCollection = $this->_addAdditionalParamToCollection($productCollection, $order, $orderBy, $page, $perPage);
            foreach ($productCollection as $product) {
                /* @var $product \Magento\Catalog\Model\Product */
				$product = $this->_productFactory->create()->load($product->getId());
                $products[] = $this->_helper->formatProduct($product);
            }
            return $products;
        }
        throw new \Exception(Message::CATEGORY_NOT_FOUND);
    }

    /**
     * return searched products
     * 
     * @param $term
     * @param string $order
     * @param string $orderBy
     * @param int $page
     * @param int $perPage
     * @return array
     */
    protected function _searchProduct($term, $category = 'all', $order = 'desc', $orderBy = 'entity_id', $page = 1, $perPage = 10)
    {
        $products = [];
        /* @var $productCollection \Magento\Catalog\Model\ResourceModel\Product\Collection */
        $productCollection = $this->_productFactory->create()->getCollection();
        $productCollection = $this->_addAdditionalParamToCollection($productCollection, $order, $orderBy, $page, $perPage);
        $productCollection->addFieldToFilter([
            ['attribute' => 'name', 'like' => "%$term%"],
            ['attribute' => 'sku', 'like' => "%$term%"]
        ]);

        if ($category !== 'all') {
            $category = explode(',', $category);
            if (!empty($category)) {
                $productCollection->addCategoriesFilter(['in' => $category]);
            }
        }

        foreach ($productCollection as $product) {
            /* @var $product \Magento\Catalog\Model\Product */
			$product = $this->_productFactory->create()->load($product->getId());
            $products[] = $this->_helper->formatProduct($product);
        }
        return $products;
    }

    /**
     * get on sale product
     *
     * @param int $page
     * @param int $perPage
     * @return array
     */
    protected function _getOnsale($page = 1, $perPage = 10)
    {
        $products = [];
        /* @var $productCollection \Magento\Catalog\Model\ResourceModel\Product\Collection */
        $productCollection = $this->_productFactory->create()->getCollection();
        $productCollection
            ->addFieldToSelect('*')
            ->addAttributeToFilter('visibility', ['neq' => 1])
            ->addAttributeToFilter('is_sale', ['eq' => 1])
            ->setPageSize($perPage)
            ->setCurPage($page);
        foreach ($productCollection as $product) {
            /* @var $product \Magento\Catalog\Model\Product */
			$product = $this->_productFactory->create()->load($product->getId());
            $products[] = $this->_helper->formatProduct($product);
        }
        return $products;
    }
    
    /**
     * get new product
     *
     * @param int $page
     * @param int $perPage
     * @return array
     */
    protected function _getNew($page = 1, $perPage = 10)
    {
        $products = [];
        /* @var $productCollection \Magento\Catalog\Model\ResourceModel\Product\Collection */
        $productCollection = $this->_productFactory->create()->getCollection();
        $productCollection
            ->addFieldToSelect('*')
            ->addAttributeToFilter('visibility', ['neq' => 1])
            ->addAttributeToFilter('is_new', ['eq' => 1])
            ->setPageSize($perPage)
            ->setCurPage($page);
        foreach ($productCollection as $product) {
            /* @var $product \Magento\Catalog\Model\Product */
			$product = $this->_productFactory->create()->load($product->getId());
            $products[] = $this->_helper->formatProduct($product);
        }
        return $products;
    }

    /**
     * get featured product
     *
     * @param int $page
     * @param int $perPage
     * @return array
     */
    protected function _getFeatured($page = 1, $perPage = 10)
    {
        $products = [];
        /* @var $productCollection \Magento\Catalog\Model\ResourceModel\Product\Collection */
        $productCollection = $this->_productFactory->create()->getCollection();
        $productCollection
            ->addFieldToSelect('*')
            ->addAttributeToFilter('visibility', ['neq' => 1])
            ->addAttributeToFilter('is_featured', ['eq' => 1])
            ->setPageSize($perPage)
            ->setCurPage($page);
        foreach ($productCollection as $product) {
            /* @var $product \Magento\Catalog\Model\Product */
			$product = $this->_productFactory->create()->load($product->getId());
            $products[] = $this->_helper->formatProduct($product);
        }
        return $products;
    }

    /**
     * get most view products
     *
     * @param int $page
     * @param int $perPage
     * @return array
     */
    protected function _getMostview($page = 1, $perPage = 10)
    {
        /* @var $mostViewCollection \Magento\Reports\Model\ResourceModel\Report\Product\Viewed\Collection */
        $mostViewCollection = $this->_mostViewCollectionFactory->create()->setModel('Magento\Catalog\Model\Product');
        return $this->_getReportProduct($mostViewCollection, $page, $perPage);
    }

    /**
     * get bestseller products
     *
     * @param int $page
     * @param int $perPage
     * @return array
     */
    protected function _getBestseller($page = 1, $perPage = 10)
    {
        /* @var $bestsellerCollection \Magento\Sales\Model\ResourceModel\Report\Bestsellers\Collection */
        $bestsellerCollection = $this->_bestsellerCollectionFactory->create()->setModel('Magento\Catalog\Model\Product');
        return $this->_getReportProduct($bestsellerCollection, $page, $perPage);
    }

    /**
     * get product from bestseller and most view products collection
     * 
     * @param \Magento\Sales\Model\ResourceModel\Report\Collection\AbstractCollection $collection
     * @param int $page
     * @param int $perPage
     * @return array
     */
    private function _getReportProduct(\Magento\Sales\Model\ResourceModel\Report\Collection\AbstractCollection $collection, $page = 1, $perPage = 10)
    {
        $products = [];
        $productIds = [];

        $collection
            ->addStoreFilter(array_keys($this->_storeManager->getStores()))
            ->setPeriod('month')
            ->setPageSize($perPage)
            ->setCurPage($page);

        foreach ($collection as $item) {
            $productIds[] = $item->getProductId();
        }

        $productCollection = $this->_productFactory->create()->getCollection()->addIdFilter($productIds)->addAttributeToSelect('*')->addAttributeToFilter('visibility', ['neq' => 1]);
        foreach ($productCollection as $product) {
            /* @var $product \Magento\Catalog\Model\Product */
			$product = $this->_productFactory->create()->load($product->getId());
            $products[] = $this->_helper->formatProduct($product);
        }
        return $products;
    }

    /**
     * add params to product collection
     * 
     * @param \Magento\Framework\Data\Collection\AbstractDb $collection
     * @param string $order
     * @param string $orderBy
     * @param int $page
     * @param int $perPage
     * @return \Magento\Framework\Data\Collection\AbstractDb
     */
    private function _addAdditionalParamToCollection(\Magento\Framework\Data\Collection\AbstractDb $collection, $order = 'desc', $orderBy = 'entity_id', $page = 1, $perPage = 10)
    {
        $collection
            ->addFieldToSelect('*')
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('visibility', ['neq' => 1])
            ->addAttributeToSort($orderBy, $order)
            ->setPageSize($perPage)
            ->setCurPage($page);

        return $collection;
    }
}