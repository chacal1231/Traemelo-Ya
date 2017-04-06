<?php
/**
 * Copyright © 2016 Inspius. All rights reserved.
 * Author: Phong Nguyen
 * Author URI: http://inspius.com
 */
namespace Inspius\Iscommerce\Controller\Api;

use Inspius\Iscommerce\Model\Config\Source\CategoryDisplay;

class Categories extends AbstractApi
{
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $_categoryFactory;

    /**
     * Categories constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Inspius\Iscommerce\Helper\Data $helper
     * @param \Inspius\Iscommerce\Model\Client $client
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Event\Manager $manager,
        \Inspius\Iscommerce\Helper\Data $helper,
        \Inspius\Iscommerce\Model\Client $client
    )
    {
        $this->_categoryFactory = $categoryFactory;
        parent::__construct($context, $resultJsonFactory, $scopeConfig, $manager, $helper, $client);
    }

    /**
     * return category list
     *
     * @return array
     */
    public function _getResponse()
    {
        $rootId = $this->_getRootCategoryId();

        $categoryCollection = $this->_categoryFactory->create()->getCollection()->addFieldToSelect('*');

        $defaultOrder = $this->_getSetting('icymobi_config/product_category/order_by') ? $this->_getSetting('icymobi_config/product_category/order_by') : 'entity_id';
        $defaultOrderDir = $this->_getSetting('icymobi_config/product_category/order') ? $this->_getSetting('icymobi_config/product_category/order') : 'desc';
        $categoryCollection->addOrder($defaultOrder, $defaultOrderDir);

        $page = $this->_request->getParam('page', 1);
        $perPage = $this->_request->getParam('per_page', 20);
        if ($perPage !== 'all') {
            $categoryCollection->setPageSize($perPage)->setCurPage($page);
        }

        $categories = [];
        $this->_formatCategoryList($categoryCollection, $categories, $rootId);
        return $categories;
    }

    /**
     * get and format category list
     *
     * @param $collection
     * @param array $list
     * @param int $parent
     */
    private function _formatCategoryList($collection, &$list = [], $parent = 1)
    {
        /* @var $collection \Magento\Catalog\Model\ResourceModel\Category\Collection */
        if ($collection->count() > 0) {
            foreach ($collection as $category) {
                /* @var $category \Magento\Catalog\Model\Category */
                if ($category->getParentId() == $parent) {
                    if ($this->_getSetting('icymobi_config/product_category/display') == CategoryDisplay::DISPLAY_SUBCATEGORIES) {
                        $children = [];
                        $this->_formatCategoryList($collection, $children, $category->getId());
                        $list[] = array_merge(
                            $this->_helper->formatCategory($category),
                            ['children' => $children]
                        );
                    } else {
                        $list[] = $this->_helper->formatCategory($category);
                        $this->_formatCategoryList($collection, $list, $category->getId());
                    }
                }
            }
        }
    }

    /**
     * get root category id
     *
     * @return mixed|null
     */
    private function _getRootCategoryId()
    {
        $id = $this->_getSetting('icymobi_config/product_category/root');
        if (!$id) {
            // get the first child id of root category (id = 1) as the default id
            /* @var $default \Magento\Catalog\Model\Category */
            $default = $this->_categoryFactory->create()
                ->getCollection()
                ->addFieldToSelect('*')
                ->addFieldToFilter('parent_id', ['eq' => 1])->getFirstItem();
            $id = $default->getId();
        }

        return $id;
    }
}