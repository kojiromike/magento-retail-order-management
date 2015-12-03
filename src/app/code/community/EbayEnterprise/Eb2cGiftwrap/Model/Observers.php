<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class EbayEnterprise_Eb2cGiftwrap_Model_Observers
{
    /** @var EbayEnterprise_Eb2cGiftwrap_Model_Order_Create_Gifting */
    protected $orderGifting;

    /**
     * @param array
     */
    public function __construct($args = [])
    {
        list(
            $this->orderGifting
        ) = $this->checkTypes(
            $this->nullCoalesce($args, 'order_gifting', Mage::getModel('eb2cgiftwrap/order_create_gifting'))
        );
    }

    /**
     * @param EbayEnterprise_Eb2cGiftwrap_Model_Order_Create_Gifting
     * @return array
     */
    protected function checkTypes(
        EbayEnterprise_Eb2cGiftwrap_Model_Order_Create_Gifting $orderGifting
    ) {
        return func_get_args();
    }

    /**
     * @param array
     * @param string|int
     * @param mixed
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $key, $default)
    {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }

    /**
     * Listen to the 'ebayenterprise_feed_dom_loaded' event
     * @see EbayEnterprise_Catalog_Model_Feed_Abstract::processFile
     * process a dom document
     * @param  Varien_Event_Observer $observer
     * @return self
     */
    public function processDom(Varien_Event_Observer $observer)
    {
        Varien_Profiler::start(__METHOD__);
        $event = $observer->getEvent();
        $fileDetail = $event->getFileDetail();
        $importConfig = Mage::getModel('eb2cgiftwrap/feed_import_config');
        $importData = $importConfig->getImportConfigData();
        $feedConfig = $fileDetail['core_feed']->getFeedConfig();

        // only process the import if the event type is in the allowabled event type configuration for this feed
        if (in_array($feedConfig['event_type'], explode(',', $importData['allowable_event_type']))) {
            $fileDetail['doc'] = $event->getDoc();
            Mage::getModel('ebayenterprise_catalog/feed_file', $fileDetail)->process(
                $importConfig,
                Mage::getModel('eb2cgiftwrap/feed_import_items')
            );
        }
        Varien_Profiler::stop(__METHOD__);
        return $this;
    }

    /**
     * Add order level gifting to the payload for order create requests.
     *
     * @param Varien_Event_Observer
     */
    public function handleEbayEnterpriseOrderCreateShipGroup(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        $address = $event->getAddress();
        $order = $event->getOrder();

        // Gift message id is inconsistent between OPC and multiship checkout.
        // In OPC, gift message id will *only* be on the order. In multiship,
        // gift message id will *only* be on the address the message applies to.
        // This ensures that if the order has a gift message, it gets copied down
        // to the primary address.
        if ($order->hasGiftMessageId() && $address->isPrimaryShippingAddress()) {
            // Technically, this could override a gift message already assigned
            // to the address but that should never happen (in Vanilla Magento).
            // If the order has a gift message, the address will not (OPC). If the
            // address has a gift message, the order will not (multiship checkout).
            $address->setGiftMessageId($order->getGiftMessageId());
        }

        $this->orderGifting->injectGifting($address, $event->getShipGroupPayload());

        return $this;
    }

    /**
     * Add item level gifting to the payload for order create requests.
     *
     * @param Varien_Event_Observer
     */
    public function handleEbayEnterpriseOrderCreateItem(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();

        $this->orderGifting->injectGifting($event->getItem(), $event->getItemPayload());

        return $this;
    }
}
