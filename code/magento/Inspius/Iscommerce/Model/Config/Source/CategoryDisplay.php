<?php
/**
 * Copyright © 2016 Inspius. All rights reserved.
 * Author: Phong Nguyen
 * Author URI: http://inspius.com
 */

namespace Inspius\Iscommerce\Model\Config\Source;

class CategoryDisplay implements \Magento\Framework\Option\ArrayInterface
{
    const DISPLAY_PRODUCTS = 'products';
    const DISPLAY_SUBCATEGORIES = 'subcategories';

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => self::DISPLAY_PRODUCTS, 'label' => __('Show Products')], ['value' => self::DISPLAY_SUBCATEGORIES, 'label' => __('Show Subcategories')]];
    }
}
