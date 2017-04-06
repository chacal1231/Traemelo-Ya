<?php

/**
 * Copyright © 2016 Inspius. All rights reserved.
 * Author: Phong Nguyen
 * Author URI: http://inspius.com
 */

namespace Inspius\Iscommerce\Controller\Api;

class Settings extends AbstractApi {

    /**
     * @var \Magento\Framework\Locale\Format
     */
    protected $_format;

    /**
     * @var \Magento\Directory\Model\Currency
     */
    protected $_priceCurrency;

    /**
     * @var \Inspius\Iscommerce\Model\SettingFactory
     */
    protected $_settingFactory;

    /**
     * Settings constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Locale\Format $format
     * @param \Magento\Directory\Model\PriceCurrency $priceCurrency
     * @param \Inspius\Iscommerce\Helper\Data $helper
     * @param \Inspius\Iscommerce\Model\Client $client
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Locale\Format $format,
        \Magento\Directory\Model\PriceCurrency $priceCurrency,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Event\Manager $manager,
        \Inspius\Iscommerce\Helper\Data $helper,
        \Inspius\Iscommerce\Model\SettingFactory $settingFactory,
        \Inspius\Iscommerce\Model\Client $client
    ) {
        $this->_format = $format->getPriceFormat();
        $this->_priceCurrency = $priceCurrency;
        $this->_settingFactory = $settingFactory;
        parent::__construct($context, $resultJsonFactory, $scopeConfig, $manager, $helper, $client);
    }

    /**
     * return setting array
     *
     * @return array
     */
    public function _getResponse() {
        /* @var $settings \Inspius\Iscommerce\Model\Setting */
        $settings = $this->_settingFactory->create();
        
        $params = $this->_request->getParams();

        $price = $this->_priceCurrency->getCurrency()->formatTxt(1000, ['display' => \Magento\Framework\Currency::NO_SYMBOL]);
        $priceHtml = '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">' . $this->_formatCurrency($this->_priceCurrency->getCurrencySymbol()) . '</span>' . $price . '</span>';

        $settings->addElements([
            'thousand_separator' => $this->_format['groupSymbol'],
            'decimal_separator' => $this->_format['decimalSymbol'],
            'number_decimals' => $this->_format['precision'],
            'samplePrice' => $price,
            'samplePriceHtml' => $priceHtml,

            'contact_map_lat' => $this->_getSetting('icymobi_config/contact/latitude'),
            'contact_map_lng' => $this->_getSetting('icymobi_config/contact/longitude'),
            'contact_map_title' => $this->_getSetting('icymobi_config/contact/title'),
            'contact_map_content' => $this->_getSetting('icymobi_config/contact/content'),

            'disable_app' => $this->_getSetting('icymobi_config/maintenance/disable'),
            'disable_app_message' => $this->_getSetting('icymobi_config/maintenance/disable_text'),

            'category_display' => $this->_getSetting('icymobi_config/product_category/display')
        ]);

        // add an event here
        $this->_eventManager->dispatch('icymobi_settings_event', ['settings' => $settings, 'params' => $params]);

        return $settings->getSettings();
    }

    /**
     * transform currency symbol to html entity
     *
     * @param $symbol
     * @return mixed
     */
    private function _formatCurrency($symbol) {
        return preg_replace_callback('/[\x{80}-\x{10FFFF}]/u', function ($match) {
            $match = mb_convert_encoding($match[0], 'UCS-4BE', 'UTF-8');
            list(, $match) = (strlen($match) === 4) ? @unpack('N', $match) : @unpack('n', $match);
            return sprintf('&#%d;', $match);
        }, $symbol);
    }

}
