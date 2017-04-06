<?php
/**
 * Copyright © 2016 Inspius. All rights reserved.
 * Author: Phong Nguyen
 * Author URI: http://inspius.com
 */

namespace Inspius\Iscommerce\Controller\Index;

use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\App\Action\Action;

class Index extends Action
{
    /**
     * @var ForwardFactory
     */
    protected $resultForwardFactory;
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        ForwardFactory $resultForwardFactory
    )
    {
        $this->resultForwardFactory = $resultForwardFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultForward = $this->resultForwardFactory->create();
        $resultForward->setController('api');
        return $resultForward->forward('index');
    }
}