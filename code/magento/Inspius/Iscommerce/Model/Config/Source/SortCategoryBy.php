<?php
/**
 * Copyright © 2016 Inspius. All rights reserved.
 * Author: Phong Nguyen
 * Author URI: http://inspius.com
 */

namespace Inspius\Iscommerce\Model\Config\Source;

class SortCategoryBy implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'entity_id', 'label' => __('ID')],
            ['value' => 'name', 'label' => __('Category Name')],
        ];
    }
}
