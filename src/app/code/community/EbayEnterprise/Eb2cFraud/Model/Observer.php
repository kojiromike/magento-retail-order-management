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

class EbayEnterprise_Eb2cFraud_Model_Observer
{
    /**
     * get the request object in a way that can be stubbed in tests.
     * @return Mage_Core_Controller_Request_Http
     * @codeCoverageIgnore
     */
    protected function _getRequest()
    {
        return Mage::app()->getRequest();
    }
    /**
     * add order context information to the order create
     * request.
     * @param  Varien_Event_Observer $observer
     * @return self
     */
    public function handleOrderCreateContextEvent(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        $orderContext = $event->getOrderContext();
        Mage::getModel('eb2cfraud/order_create_context')
            ->addContextInfoToPayload($orderContext);
        return $this;
    }
}
