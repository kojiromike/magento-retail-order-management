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

class EbayEnterprise_Inventory_Test_Model_Quantity_Response_ParserTest
	extends EcomDev_PHPUnit_Test_Case
{
	/** @var eBayEnterprise\RetailOrderManagement\Payload\Inventory\IQuantityReply */
	protected $_responsePayload;
	/** @var eBayEnterprise\RetailOrderManagement\Payload\Inventory\IReplyQuantityItem */
	protected $_quantityItem;
	/** @var int */
	protected $_quantityItemQuantity = 4000;
	/** @var int */
	protected $_quantityItemItemId = 33;
	/** @var string */
	protected $_quantityItemSku = 'the-sku';
	/** @var Iterable */
	protected $_quantityItemIterable;
	/** @var Iterable */
	protected $_emptyQuantityItemIterable;
	/** @var EbayEnterprise_Inventory_Helper_Quantity_Factory */
	protected $_quantityFactory;
	/** @var EbayEnterprise_Inventory_Model_Quantity_Response_Parser */
	protected $_responseParser;

	public function setUp()
	{
		$this->_responsePayload = $this->getMockForAbstractClass(
			'eBayEnterprise\RetailOrderManagement\Payload\Inventory\IQuantityReply',
			['getQuantityItems']
		);
		// Create an expected quantity item for the payload to contain.
		$this->_quantityItem = $this->getMockForAbstractClass(
			'eBayEnterprise\RetailOrderManagement\Payload\Inventory\IReplyQuantityItem',
			['getQuantity', 'getItemId', 'getLineId']
		);
		// Stub the item to return an expected quantity, item id and sku.
		$this->_quantityItem->expects($this->any())
			->method('getQuantity')
			->will($this->returnValue($this->_quantityItemQuantity));
		$this->_quantityItem->expects($this->any())
			->method('getLineId')
			->will($this->returnValue($this->_quantityItemItemId));
		$this->_quantityItem->expects($this->any())
			->method('getItemId')
			->will($this->returnValue($this->_quantityItemSku));
		// Create some item iterables that may be used in tests -
		// one with a quantity item and one empty. Both swap
		// an ArrayIterator to implement the Iterable interface
		// needed by these objects.
		$this->_quantityItemIterable = new ArrayIterator([$this->_quantityItem]);
		$this->_emptyQuantityItemIterable = new ArrayIterator([]);

		// Create a mock quantity factory that may be used to
		// create new quantity models.
		$this->_quantityFactory = $this->getHelperMock(
			'ebayenterprise_inventory/quantity_factory',
			['createQuantity']
		);

		// Create the response parser, injected with test mocks.
		$this->_responseParser = Mage::getModel(
			'ebayenterprise_inventory/quantity_response_parser',
			[
				'quantity_response' => $this->_responsePayload,
				'quantity_factory' => $this->_quantityFactory,
			]
		);
	}

	/**
	 * When getting quantity results from a response,
	 * an array of quantity models populated from the
	 * payload data should be returned.
	 */
	public function testGetQuantityResults()
	{
		// Create an expected quantity model - created with data
		// expected to be extracted from the response payload.
		$quantityModel = Mage::getModel(
			'ebayenterprise_inventory/quantity',
			[
				'sku' => $this->_quantityItemSku,
				'item_id' => $this->_quantityItemItemId,
				'quantity' => $this->_quantityItemQuantity
			]
		);

		// Set the response payload up to return the item iterable
		// containing a quantity item.
		$this->_responsePayload->expects($this->any())
			->method('getQuantityItems')
			->will($this->returnValue($this->_quantityItemIterable));

		// Set the quantity factory to return a correct quantity
		// model if given the proper sku, item id and quantity.
		$this->_quantityFactory->expects($this->any())
			->method('createQuantity')
			->with(
				$this->identicalTo($this->_quantityItemSku),
				$this->identicalTo($this->_quantityItemItemId),
				$this->identicalTo($this->_quantityItemQuantity)
			)
			->will($this->returnValue($quantityModel));

		$results = $this->_responseParser->getQuantityResults();
		// Verify the extracted results.
		$this->assertCount(1, $results, 'did not extract correct number of results');
		$resultQuantityModel = $results[0];
		$this->assertSame($this->_quantityItemSku, $resultQuantityModel->getSku(), 'sku of extracted quantity model does not match expected sku');
		$this->assertSame($this->_quantityItemItemId, $resultQuantityModel->getItemId(), 'item id of extracted quantity model does not match expected item id');
		$this->assertSame($this->_quantityItemQuantity, $resultQuantityModel->getQuantity(), 'quantity of extracted quantity model does not match expected quantity');
	}
}
