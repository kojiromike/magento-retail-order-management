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

class EbayEnterprise_Inventory_Test_Model_Quantity_Request_BuilderTest extends EcomDev_PHPUnit_Test_Case
{
	/** @var Mage_Sales_Model_Quote_Item_Abstract */
	protected $_item;
	/** @var eBayEnterprise\RetailOrderManagement\Payload\Inventory\IQuantityRequest */
	protected $_requestPayload;
	/** @var eBayEnterprise\RetailOrderManagement\Payload\Inventory\IQuantityItemIterable */
	protected $_quantityItemIterable;
	/** @var eBayEnterprise\RetailOrderManagement\Payload\Inventory\IRequestQuantityItem */
	protected $_emptyItem;
	/** @var eBayEnterprise\RetailOrderManagement\Payload\Inventory\IRequestQuantityItem */
	protected $_populatedItem;
	/** @var EbayEnterprise_Inventory_Helper_Quantity_Payload */
	protected $_payloadHelper;
	/** @var EbayEnterprise_Inventory_Helper_Item_Selection */
	protected $_itemSelection;

	public function setUp()
	{
		$this->_item = $this->getModelMock('sales/quote_item_abstract', [], true);

		$this->_requestPayload = $this->getMockForAbstractClass(
			'eBayEnterprise\RetailOrderManagement\Payload\Inventory\IQuantityRequest',
			['getQuantityItems', 'setQuantityItems']
		);
		$this->_quantityItemIterable = $this->getMockForAbstractClass(
			'eBayEnterprise\RetailOrderManagement\Payload\Inventory\IQuantityItemIterable',
			['offsetSet', 'getEmptyQuantityItem']
		);
		$this->_emptyItem = $this->getMockForAbstractClass(
			'eBayEnterprise\RetailOrderManagement\Payload\Inventory\IRequestQuantityItem'
		);
		$this->_populatedItem = $this->getMockForAbstractClass(
			'eBayEnterprise\RetailOrderManagement\Payload\Inventory\IRequestQuantityItem'
		);

		$this->_payloadHelper = $this->getHelperMockBuilder('ebayenterprise_inventory/quantity_payload')
			->setMethods(['itemToRequestQuantityItem'])
			->getMock();

		$this->_itemSelection = $this->getHelperMockBuilder('ebayenterprise_inventory/item_selection')
			->setMethods(['selectFrom'])
			->getMock();
	}

	/**
	 * When getting the request from a request builder
	 * the payload the builder has should be populated with
	 * item data and returned.
	 */
	public function testGetRequest()
	{
		// Create a set of items for the quote and set the quote
		// up to return them.
		$quoteItems = [$this->_item];
		// Connect SDK payloads.
		$this->_requestPayload->expects($this->any())
			->method('getQuantityItems')
			->will($this->returnValue($this->_quantityItemIterable));
		// Side effect test - ensure the mutated quantity items iterable
		// is set back to the payload.
		$this->_requestPayload->expects($this->once())
			->method('setQuantityItems')
			->with($this->identicalTo($this->_quantityItemIterable));

		// Testing that proper item is added to the iterable:

		// Rig the iterable to return the "empty" item.
		$this->_quantityItemIterable->expects($this->any())
			->method('getEmptyQuantityItem')
			->will($this->returnValue($this->_emptyItem));
		// Set the payload helper to fill out the payload:
		// given the expected item and the empty payload,
		// the helper should return the populated item payload.
		$this->_payloadHelper->expects($this->any())
			->method('itemToRequestQuantityItem')
			->with($this->identicalTo($this->_item), $this->identicalTo($this->_emptyItem))
			->will($this->returnValue($this->_populatedItem));
		// Side-effect test: there should be one - and only one - payload
		// added to the iterable: the populated item payload.
		$this->_quantityItemIterable->expects($this->once())
			->method('offsetSet')
			->with($this->identicalTo($this->_populatedItem))
			->will($this->returnValue(null));

		$requestBuilder = Mage::getModel(
			'ebayenterprise_inventory/quantity_request_builder',
			[
				'items' => $quoteItems,
				'request_payload' => $this->_requestPayload,
				'payload_helper' => $this->_payloadHelper,
				'item_selection' => $this->_itemSelection,
			]
		);
		$this->assertSame(
			$this->_requestPayload,
			$requestBuilder->getRequest()
		);
	}
}
