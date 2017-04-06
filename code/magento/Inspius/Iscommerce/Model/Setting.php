<?php
/**
 * Copyright © 2016 Inspius. All rights reserved.
 * Author: Phong Nguyen
 * Author URI: http://inspius.com
 */
namespace Inspius\Iscommerce\Model;

use Magento\Framework\Model\AbstractModel;

class Setting extends AbstractModel
{
    protected $_settings = [];

    public function addElement($name, $value)
    {
        if ($name) {
            $this->_settings[$name] = $value ? $value : '';
        }
    }

    public function addElements($data = [])
    {
        foreach ($data as $name => $value) {
            $this->addElement($name, $value);
        }
    }

    public function removeElement($name)
    {
        if (isset($this->_settings[$name])) unset($this->_settings[$name]);
    }

    public function getSettings()
    {
        return $this->_settings;
    }
}