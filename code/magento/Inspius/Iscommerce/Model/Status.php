<?php
/**
 * Copyright © 2016 Inspius. All rights reserved.
 * Author: Phong Nguyen
 * Author URI: http://inspius.com
 */

namespace Inspius\Iscommerce\Model;

use Magento\Framework\Model\AbstractModel;

class Status extends AbstractModel
{
    const API_SUCCESS = 1;
    const API_FAILED = -1;
}