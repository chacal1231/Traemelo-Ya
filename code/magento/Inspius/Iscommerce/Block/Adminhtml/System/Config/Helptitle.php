<?php
/**
 * Copyright © 2016 Inspius. All rights reserved.
 * Author: Phong Nguyen
 * Author URI: http://inspius.com
 */
namespace Inspius\Iscommerce\Block\Adminhtml\System\Config;

class Helptitle extends \Magento\Config\Block\System\Config\Form
{
    public function _toHtml()
    {
        $html = "For more information: &nbsp;
                    <span><a href=\"http://icymobi.com/about-us\" target=\"_blank\">About IcyMobi</a></span> &nbsp;
                    <span><a href=\"http://store.inspius.com/downloads/category/icymobi/\" target=\"_blank\">Official Plugins</a></span> &nbsp;
                    <span><a href=\"http://icymobi.com/help\" target=\"_blank\">Help &amp; Support</a></span> &nbsp;
                    <span><a href=\"http://icymobi.com/found-a-bug\" target=\"_blank\">Found a bug?</a></span> &nbsp;
                ";
        return $html . parent::_toHtml();
    }
}