<?php
/**
 * Copyright © 2016 Inspius. All rights reserved.
 * Author: Phong Nguyen
 * Author URI: http://inspius.com
 */
namespace Inspius\Iscommerce\Controller\Api;

use Inspius\Iscommerce\Model\Message;
use Magento\Review\Model\Review;
use Magento\Review\Model\Rating;

class Reviews extends AbstractApi
{
    const REVIEW_ADD_NEW = 'add';

    /**
     * @var \Magento\Review\Model\ReviewFactory
     */
    protected $_reviewFactory;

    /**
     * @var \Magento\Review\Model\RatingFactory
     */
    protected $_ratingFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var \Magento\Store\Model\StoreManager
     */
    protected $_storeManager;

    /**
     * Reviews constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Review\Model\ReviewFactory $reviewFactory
     * @param \Magento\Review\Model\RatingFactory $ratingFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param \Inspius\Iscommerce\Helper\Data $helper
     * @param \Inspius\Iscommerce\Model\Client $client
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Review\Model\ReviewFactory $reviewFactory,
        \Magento\Review\Model\RatingFactory $ratingFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Event\Manager $manager,
        \Inspius\Iscommerce\Helper\Data $helper,
        \Inspius\Iscommerce\Model\Client $client
    )
    {
        $this->_reviewFactory = $reviewFactory;
        $this->_ratingFactory = $ratingFactory;
        $this->_productFactory = $productFactory;
        $this->_customerFactory = $customerFactory;
        $this->_storeManager = $storeManager;
        parent::__construct($context, $resultJsonFactory, $scopeConfig, $manager, $helper, $client);
    }

    /**
     * decide action to perform
     *
     * @return array
     * @throws \Exception
     */
    public function _getResponse()
    {
        $id = $this->_getParam('id');
        $action = $this->_getParam('task');
        if ($id && is_numeric($id)) {
            if ($action && $action == self::REVIEW_ADD_NEW) {
                return $this->_addNewComment($id);
            }
            return $this->_getReviewList($id);
        }
        throw new \Exception(Message::REVIEW_ID_NOT_FOUND);
    }

    /**
     * get Reviews for a product
     *
     * @param $id
     * @return array
     */
    protected function _getReviewList($id)
    {
        $reviewList = [];
        $reviews = $this->_reviewFactory->create()
            ->getCollection()
            ->addFieldToSelect('*')
            ->addFieldToFilter('entity_pk_value', ['eq' => $id])
            ->addFieldToFilter('status_id', ['eq' => Review::STATUS_APPROVED])
            ->setOrder('review_id', \Magento\Framework\Data\Collection\AbstractDb::SORT_ORDER_DESC);
        foreach ($reviews as $review) {
            /* @var $review \Magento\Review\Model\Review */
            $rating = $this->_ratingFactory->create()->getReviewSummary($review->getId())->getSum() / 20;
            $reviewList[] = [
                'id' => $review->getId(),
                'date_created' => $review->getCreatedAt(),
                'review' => $review->getDetail(),
                'rating' => $rating,
                'name' => $review->getNickname(),
                'link_avatar' => '',
                'email' => ''
            ];
        }
        return $reviewList;
    }

    /**
     * add new review for product
     *
     * @param $id
     * @return array
     * @throws \Exception
     */
    protected function _addNewComment($id)
    {
        $product = $this->_productFactory->create()->load($id);
        if ($product->getId()) {
            $rating = $this->_getParam('rating');
            $nickname = $this->_getParam('user_login');
            $detail = $this->_getParam('comment');
            $customerId = $this->_getParam('user_id');

            $customer = $this->_customerFactory->create()->load($customerId);
            if (!$customer->getId()) $customerId = null;

            if ($rating && is_numeric($rating) && 0 < $rating && $rating <= 5 && $nickname && $detail) {
                $review = $this->_reviewFactory->create()->setData([
                    'nickname' => $nickname,
                    'title' => 'Review for ' . $product->getName(),
                    'detail' => $detail
                ]);
                $review->unsetData('review_id');
                $validate = $review->validate();
                if ($validate) {
                    try {
                        $options = $this->_getRatingOptions();
                        if (isset($options[$rating])) {
                            $review->setEntityId($review->getEntityIdByCode(Review::ENTITY_PRODUCT_CODE))
                                ->setEntityPkValue($product->getId())
                                ->setStatusId(Review::STATUS_PENDING)
                                ->setCustomerId($customerId)
                                ->setStoreId($this->_storeManager->getStore()->getId())
                                ->setStores([$this->_storeManager->getStore()->getId()])
                                ->save();

                            $this->_ratingFactory->create()
                                ->setRatingId($options[$rating]['rating_id'])
                                ->setReviewId($review->getId())
                                ->setCustomerId($customerId)
                                ->addOptionVote($options[$rating]['option_id'], $product->getId());
                            $review->aggregate();
                            return $this->_getReviewList($id);
                        }
                    } catch (\Exception $e) {
                        throw new \Exception(Message::REVIEW_ADD_NEW_FAILED);
                    }
                }
            }
            throw new \Exception(Message::REVIEW_INVALID_DATA);
        }
        throw new \Exception(Message::REVIEW_ADD_NEW_ID_NOT_FOUND);
    }

    /**
     * get rating option list
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function _getRatingOptions()
    {
        $ratingObj = $this->_ratingFactory->create()->getResourceCollection()->addEntityFilter(
            Rating::ENTITY_PRODUCT_CODE
        )->setPositionOrder()->addRatingPerStoreName(
            $this->_storeManager->getStore()->getId()
        )->setStoreFilter(
            $this->_storeManager->getStore()->getId()
        )->setActiveFilter(
            true
        )->load()->addOptionToItems()->getFirstItem();
        $rates = [];
        foreach ($ratingObj->getOptions() as $rate) {
            $rates[$rate->getValue()] = $rate->getData();
        }
        return $rates;
    }
}