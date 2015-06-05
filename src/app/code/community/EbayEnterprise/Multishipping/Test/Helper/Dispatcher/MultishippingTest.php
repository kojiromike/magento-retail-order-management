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

class EbayEnterprise_Multishipping_Test_Helper_Dispatcher_MultishippingTest extends EcomDev_PHPUnit_Test_Case
{
    /** @var EbayEnterprise_Multishipping_Helper_Dispatcher_Onepage */
    protected $_dispatcher;
    /** @var Mage_Sales_Model_Quote */
    protected $_quote;
    /** @var Mage_Sales_Model_Order */
    protected $_order;

    protected function setUp()
    {
        $this->_dispatcher = Mage::helper('ebayenterprise_multishipping/dispatcher_multishipping');
        $this->_quote = Mage::getModel('sales/quote');
        $this->_order = Mage::getModel('sales/order');
        // Disable events to prevent unintended side-effects of the events.
        Mage::app()->disableEvents();
    }

    protected function tearDown()
    {
        // Re-enable events after tests to restore normal Magento behavior.
        Mage::app()->enableEvents();
    }

    /**
     * When dispatching multiship before order submit events, ensure that the
     * expected events are dispatched.
     */
    public function testDispatchBeforeOrderSubmit()
    {
        $this->_dispatcher->dispatchBeforeOrderSubmit($this->_quote, $this->_order);
        $this->assertEventDispatchedExactly('checkout_type_multishipping_create_orders_single', 1);
    }

    /**
     * When dispatching multiship order submit failure events, ensure that the
     * expected events are dispatched.
     */
    public function testDispatchOrderSubmitFailure()
    {
        $this->_dispatcher->dispatchOrderSubmitFailure($this->_quote, $this->_order);
        $this->assertEventDispatchedExactly('checkout_multishipping_refund_all', 1);
    }
}
