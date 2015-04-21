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

class EbayEnterprise_Multishipping_Test_Helper_Dispatcher_OnepageTest extends EcomDev_PHPUnit_Test_Case
{
	/** @var EbayEnterprise_Multishipping_Helper_Dispatcher_Onepage */
	protected $_dispatcher;
	/** @var Mage_Sales_Model_Quote */
	protected $_quote;
	/** @var Mage_Sales_Model_Order */
	protected $_order;

	protected function setUp()
	{
		$this->_dispatcher = Mage::helper('ebayenterprise_multishipping/dispatcher_onepage');
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
	 * When dispatching onepage before order submit events, ensure that the
	 * expected events are dispatched.
	 */
	public function testDispatchBeforeOrderSubmit()
	{
		$this->_dispatcher->dispatchBeforeOrderSubmit($this->_quote, $this->_order);
		$this->assertEventDispatchedExactly('checkout_type_onepage_save_order', 1);
		$this->assertEventDispatchedExactly('sales_model_service_quote_submit_before', 1);
	}

	/**
	 * When dispatching onepage order submit success events, ensure that the
	 * expected events are dispatched.
	 */
	public function testDispatchOrderSubmitSuccess()
	{
		$this->_dispatcher->dispatchOrderSubmitSuccess($this->_quote, $this->_order);
		$this->assertEventDispatchedExactly('sales_model_service_quote_submit_success', 1);
	}

	/**
	 * When dispatching onepage order submit failure events, ensure that the
	 * expected events are dispatched.
	 */
	public function testDispatchOrderSubmitFailure()
	{
		$this->_dispatcher->dispatchOrderSubmitFailure($this->_quote, $this->_order);
		$this->assertEventDispatchedExactly('sales_model_service_quote_submit_failure', 1);
	}

	/**
	 * When dispatching onepage after order submit events, ensure that the
	 * expected events are dispatched.
	 */
	public function testDispatchAfterOrderSubmit()
	{
		$this->_dispatcher->dispatchAfterOrderSubmit($this->_quote, $this->_order);
		$this->assertEventDispatchedExactly('sales_model_service_quote_submit_after', 1);
	}
}
