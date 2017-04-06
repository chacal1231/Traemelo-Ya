<?php
/**
 * Copyright © 2016 Inspius. All rights reserved.
 * Author: Phong Nguyen
 * Author URI: http://inspius.com
 */

namespace Inspius\Iscommerce\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Integration\Model\ConfigBasedIntegrationManager;
use Magento\Framework\Setup\InstallDataInterface;

class InstallData implements InstallDataInterface
{
    private $integrationManager;

    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;
    
    public function __construct(
        ConfigBasedIntegrationManager $integrationManager,
        \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory
    )
    {
        $this->integrationManager = $integrationManager;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function install(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    )
    {
        $this->integrationManager->processIntegrationConfig(['iscommerceIntegration']);

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        $attributes = [
            'is_new' => [
                'type' => 'int',
                'label' => 'New'
            ],
            'is_featured' => [
                'type' => 'int',
                'label' => 'Featured'
            ],
            'is_sale' => [
                'type' => 'int',
                'label' => 'Sale'
            ]
        ];
        /**
         * Add attributes to the eav/attribute
         */
        foreach ($attributes as $name => $attribute) {
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                $name,
                [
                    'type' => $attribute['type'],
                    'backend' => '',
                    'frontend' => '',
                    'label' => $attribute['label'],
                    'input' => 'boolean',
                    'class' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => false,
                    'default' => 0,
                    'searchable' => true,
                    'filterable' => true,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => true,
                    'unique' => false,
                    'apply_to' => ''
                ]
            );
        }
    }
}