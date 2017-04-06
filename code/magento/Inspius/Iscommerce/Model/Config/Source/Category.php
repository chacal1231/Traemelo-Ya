<?php
/**
 * Copyright © 2016 Inspius. All rights reserved.
 * Author: Phong Nguyen
 * Author URI: http://inspius.com
 */

namespace Inspius\Iscommerce\Model\Config\Source;

class Category implements \Magento\Framework\Option\ArrayInterface
{
    protected $_categoryFactory;

    public function __construct(
        \Magento\Catalog\Model\CategoryFactory $categoryFactory
    )
    {
        $this->_categoryFactory = $categoryFactory;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        /* @var $categoryCollection \Magento\Catalog\Model\ResourceModel\Category\Collection */
        $categoryCollection = $this->_categoryFactory->create()->getCollection()->addFieldToSelect('*');
        $categories = [];
        $this->_formatCategoryList($categoryCollection, $categories);
        return $categories;
    }

    private function _formatCategoryList($collection, &$list = [], $parent = 1, $level = 0)
    {
        $prefix = '';
        for ($i = 0; $i < $level; $i++) {
            $prefix .= '_';
        }
        /* @var $collection \Magento\Catalog\Model\ResourceModel\Category\Collection */
        if ($collection->count() > 0) {
            $level = $level + 2;
            foreach ($collection as $category) {
                /* @var $category \Magento\Catalog\Model\Category */
                if ($category->getIsActive() && $category->getParentId() == $parent) {
                    $list[] = [
                        'value' => $category->getId(),
                        'label' => $prefix . $category->getName()
                    ];
                    $this->_formatCategoryList($collection, $list, $category->getId(), $level);
                }
            }
        }
    }
}
