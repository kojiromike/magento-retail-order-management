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
 * @copyright   Copyright (c) 2013-2015 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

use eBayEnterprise\RetailOrderManagement\Payload\PayloadFactory;

class EbayEnterprise_Eb2cGiftwrap_Test_Model_Order_Create_GiftingTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/** @var Mage_Sales_Model_Order */
	protected $_order;
	/** @var Mage_Sales_Model_Order_Item */
	protected $_item;
	/** @var EbayEnterprise_Eb2cGiftwrap_Helper_Data (mock) */
	protected $_helperMock;
	/** @var Mage_GiftMessage_Model_Message (mock) */
	protected $_messageMock;
	/** @var Enterprise_GiftWrapping_Model_Wrapping (mock) */
	protected $_giftwrappingMock;
	/** @var PayloadFactory */
	protected $_payloadFactory;
	/** @var \eBayEnterprise\RetailOrderManagement\Payload\Order\OrderCreateRequest */
	protected $_orderCreateRequest;

	public function setUp()
	{
		$this->_order = Mage::getModel('sales/order');
		$this->_item = Mage::getModel('sales/order_item');
		$this->_helperMock = $this->getHelperMock('eb2cgiftwrap/data');
		$this->_messageMock = $this->getModelMock('giftmessage/message', array('load'));
		$this->_giftwrappingMock = $this->getModelMock('enterprise_giftwrapping/wrapping', array('load'));
		$this->_payloadFactory = new PayloadFactory;
		// This payload will be the source of all sub-payloads handled. Any gifting
		// subpayloads to be tested should come from it.
		$this->_orderCreateRequest = $this->_payloadFactory->buildPayload(
			'\eBayEnterprise\RetailOrderManagement\Payload\Order\OrderCreateRequest'
		);
	}

	/**
	 * When there is no gifting data for the item, nothing should be added to the payload.
	 */
	public function testNoGiftingData()
	{
		$orderGiftingPayload = $this->_orderCreateRequest->getShipGroups()->getEmptyShipGroup();

		Mage::getModel('eb2cgiftwrap/order_create_gifting')->injectGifting(
			$this->_order,
			$this->_order,
			$orderGiftingPayload
		);
		// Spot check to make sure no data was added to the payload.
		$this->assertNull($orderGiftingPayload->getGiftItemId());
		$this->assertNull($orderGiftingPayload->getGiftPricing());
		$this->assertNull($orderGiftingPayload->getGiftCardTo());
		$this->assertNull($orderGiftingPayload->getGiftMessageTo());
		$this->assertNull($orderGiftingPayload->getPackslipTo());
	}

	/**
	 * Test adding gift messages to the gifting payload.
	 *
	 * @param bool
	 * @dataProvider provideTrueFalse
	 */
	public function testOrderGiftingMessages($addCard)
	{
		$messageId = 1;
		$sender = 'From Name';
		$recipient = 'To Name';
		$message = 'Gift message text.';

		$this->_order->addData(array(
			'gw_add_card' => $addCard,
			'gift_message_id' => 1,
		));

		// Mock loading a gift message model for the gift message for the item.
		$this->_messageMock->addData(array(
			'gift_message_id' => $messageId,
			'customer_id' => 2,
			'sender' => $sender,
			'recipient' => $recipient,
			'message' => $message,
		));
		$this->_messageMock->expects($this->once())
			->method('load')
			->with($this->identicalTo($messageId))
			->will($this->returnSelf());
		$this->replaceByMock('model', 'gift/message', $this->_messageMock);

		$payload = $this->_orderCreateRequest->getShipGroups()->getEmptyShipGroup();

		$gifting = Mage::getModel('eb2cgiftwrap/order_create_gifting');
		$gifting->injectGifting($this->_order, $this->_order, $payload);

		// If the add card flag is true, message should be included as a gift card message.
		// When false, message should be included as a packslip message.
		$assertions = $addCard
			? array(
				'getGiftCardTo' => $recipient,
				'getGiftCardFrom' => $sender,
				'getGiftCardMessage' => $message,
			)
			: array(
				'getPackslipTo' => $recipient,
				'getPackslipFrom' => $sender,
				'getPackslipMessage' => $message,
			);

		foreach ($assertions as $method => $value) {
			$this->assertSame($value, $payload->$method());
		}
	}

	/**
	 * Test flagging the item to include gift wrapping and to insert the item
	 * id of the gift wrapping item.
	 */
	public function testAddGiftWrapping()
	{
		$giftWrapId = 1;
		$wrapItemId = 'SKU-12345';
		$wrapTaxClass = 'wraptaxclass';

		$this->_order->addData(array(
			'gw_id' => $giftWrapId,
			'wraptaxclass' => $wrapTaxClass,
		));

		// Mock loading the gift wrapping model for the gift wrapping for the item.
		$this->_giftwrappingMock->addData(array(
			'eb2c_sku' => $wrapItemId,
		));
		$this->_giftwrappingMock->expects($this->once())
			->method('load')
			->with($this->identicalTo($giftWrapId))
			->will($this->returnSelf());
		$this->replaceByMock('model', 'enterprise_giftwrapping/wrapping', $this->_giftwrappingMock);

		$payload = $this->_orderCreateRequest->getShipGroups()->getEmptyShipGroup();

		$gifting = Mage::getModel('eb2cgiftwrap/order_create_gifting');
		$gifting->injectGifting($this->_order, $this->_order, $payload);

		$this->assertSame($wrapItemId, $payload->getGiftItemId());
		$this->assertTrue($payload->getIncludeGiftWrapping());
	}

	/**
	 * When adding gift pricing to an order, amount and unit price should both be
	 * wrap price + card price (handled by the helper).
	 */
	public function testAddGiftPricingOrder()
	{
		$wrapPrice = 10.00;
		$cardPrice = 5.00;
		$giftPrice = $wrapPrice + $cardPrice;

		// Assume this method will return the proper amount for for the total
		// amount for gift pricing.
		$this->_helperMock->expects($this->any())
			->method('calculateGwItemRowTotal')
			->with($this->identicalTo($this->_order))
			->will($this->returnValue($giftPrice));

		$this->_order->addData(array(
			'gw_price' => $wrapPrice,
			'gw_card_price' => $cardPrice,
		));

		$payload = $this->_orderCreateRequest->getShipGroups()->getEmptyShipGroup();

		$gifting = Mage::getModel('eb2cgiftwrap/order_create_gifting', array('helper' => $this->_helperMock));
		$gifting->injectGifting($this->_order, $this->_order, $payload);

		$this->assertSame($giftPrice, $payload->getGiftPricing()->getAmount());
		$this->assertSame($giftPrice, $payload->getGiftPricing()->getUnitPrice());
	}

	/**
	 * When adding gift pricing to an item, amount should be wrap price * qty
	 * (handled by the helper method) and unit price should be just the gift
	 * wrapping price.
	 */
	public function testAddGiftPricingItem()
	{
		$wrapPrice = 10.00;
		$qty = 2;
		$giftPrice = $qty * $wrapPrice;

		// Assume this method will return the proper amount for for the total
		// amount for gift pricing.
		$this->_helperMock->expects($this->any())
			->method('calculateGwItemRowTotal')
			->with($this->identicalTo($this->_item))
			->will($this->returnValue($giftPrice));

		$this->_item->addData(array(
			'gw_price' => $wrapPrice,
			'qty' => $qty,
		));

		$payload = $this->_orderCreateRequest->getShipGroups()->getEmptyShipGroup();

		$gifting = Mage::getModel('eb2cgiftwrap/order_create_gifting', array('helper' => $this->_helperMock));
		$gifting->injectGifting($this->_item, $this->_order, $payload);

		$this->assertSame($giftPrice, $payload->getGiftPricing()->getAmount());
		$this->assertSame($wrapPrice, $payload->getGiftPricing()->getUnitPrice());
	}
}
