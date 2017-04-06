<?php
/**
 * Copyright © 2016 Inspius. All rights reserved.
 * Author: Phong Nguyen
 * Author URI: http://inspius.com
 */

namespace Inspius\Iscommerce\Model\Config\Source;

class SortDirection implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => \Magento\Framework\Data\Collection::SORT_ORDER_ASC, 'label' => __('Ascending')],
            ['value' => \Magento\Framework\Data\Collection::SORT_ORDER_DESC, 'label' => __('Descending')]
        ];
    }
}
