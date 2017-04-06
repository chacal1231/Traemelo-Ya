<?php
/**
 * Copyright © 2016 Inspius. All rights reserved.
 * Author: Phong Nguyen
 * Author URI: http://inspius.com
 */
namespace Inspius\Iscommerce\Block\Adminhtml\System\Config;

class Address extends \Magento\Config\Block\System\Config\Form\Field
{
    const FIELD_ADDRESS = 'icymobi_config_contact_address';
    const FIELD_LATITUDE = 'icymobi_config_contact_latitude';
    const FIELD_LONGITUDE = 'icymobi_config_contact_longitude';

    /**
     * Set template to itself
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('system/config/address.phtml');
        }
        return $this;
    }

    /**
     * Retrieve element HTML markup
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->_toHtml();
    }
}