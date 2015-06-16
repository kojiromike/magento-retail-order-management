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

class EbayEnterprise_Eb2cInventory_Model_Observer
{
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $context;

    public function __construct()
    {
        $this->logger = Mage::helper('ebayenterprise_magelog');
        $this->context = Mage::helper('ebayenterprise_magelog/context');
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
        $feedConfig = $fileDetail['core_feed']->getFeedConfig();
        // only process the import if the event type is an inventory type
        if ($feedConfig['event_type'] === Mage::helper('eb2cinventory')->getConfigModel()->feedEventType) {
            $fileDetail['doc'] = $event->getDoc();
            Mage::getModel('eb2cinventory/feed_item_inventories')->process($fileDetail['doc']);
        }
        Varien_Profiler::stop(__METHOD__);
        return $this;
    }
}
