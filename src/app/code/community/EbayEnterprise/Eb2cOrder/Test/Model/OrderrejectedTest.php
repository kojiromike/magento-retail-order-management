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

use eBayEnterprise\RetailOrderManagement\Payload;
use eBayEnterprise\RetailOrderManagement\Payload\OrderEvents;

class EbayEnterprise_Eb2cOrder_Test_Model_OrderrejectedTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	const PAYLOAD_CUSTOMER_ORDER_ID = '10000003';
	const PAYLOAD_STORE_ID = 'GTA36';
	const PAYLOAD_ORDER_CREATE_TIMESTAMP = '2014-11-26T08:09:33-04:00';
	const PAYLOAD_REASON = 'Testing invalid payment reason message';
	const PAYLOAD_CODE = 'Invalid Payment';

	/** @var OrderEvents\OrderRejected $_payload */
	protected $_payload;
	/** @var EbayEnterprise_Eb2cOrder_Model_Orderrejected $_orderrejected */
	protected $_orderrejected;
	/** @var EbayEnterprise_Eb2cOrder_Helper_Event $_eventHelper */
	protected $_eventHelper;

	public function setUp()
	{
		parent::setUp();
		$this->_payload = new OrderEvents\OrderRejected(
			new Payload\ValidatorIterator([$this->getMock('\eBayEnterprise\RetailOrderManagement\Payload\IValidator')]),
			$this->getMock('\eBayEnterprise\RetailOrderManagement\Payload\ISchemaValidator')
		);
		$this->_payload->setCustomerOrderId(static::PAYLOAD_CUSTOMER_ORDER_ID)
			->setStoreId(static::PAYLOAD_STORE_ID)
			->setOrderCreateTimestamp(new DateTime(static::PAYLOAD_ORDER_CREATE_TIMESTAMP))
			->setReason(static::PAYLOAD_REASON)
			->setCode(static::PAYLOAD_CODE);

		$this->_eventHelper = $this->getHelperMock('eb2corder/event', array('attemptCancelOrder'));

		$this->_orderrejected = Mage::getModel('eb2corder/orderrejected', array(
			'payload' => $this->_payload,
			'order_event_helper' => $this->_eventHelper
		));
	}
	/**
	 * This is a procedural test, testing that the method `eb2corder/orderrejected::process`
	 * when invoke will call the expected methods and pass in the expected parameteWr values.
	 */
	public function testUpdateRejectedStatus()
	{
		$id = 5;
		$eventName = 'OrderRejected';

		$order = $this->getModelMock('sales/order', array('loadByIncrementId'));
		$order->setId($id);
		$order->expects($this->once())
			->method('loadByIncrementId')
			->with($this->identicalTo(static::PAYLOAD_CUSTOMER_ORDER_ID))
			->will($this->returnSelf());
		$this->replaceByMock('model', 'sales/order', $order);

		$this->_eventHelper->expects($this->once())
			->method('attemptCancelOrder')
			->with($this->identicalTo($order), $this->identicalTo($eventName))
			->will($this->returnSelf());

		$this->_orderrejected->process();
	}
}
