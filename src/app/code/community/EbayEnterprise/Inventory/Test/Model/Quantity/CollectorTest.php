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

class EbayEnterprise_Inventory_Test_Model_Quantity_CollectorTest extends EcomDev_PHPUnit_Test_Case
{
	/** @var EbayEnterprise_Inventory_Model_Session */
	protected $_inventorySession;
	/** @var EbayEnterprise_Inventory_Helper_Quantity_Sdk */
	protected $_quantitySdkHelper;
	/** @var EbayEnterprise_Inventory_Helper_Quantity */
	protected $_quantityHelper;
	/** @var EbayEnterprise_Inventory_Helper_Item_Selection */
	protected $_itemSelection;
	/** @var EbayEnterprise_Inventory_Model_Quantity_Results */
	protected $_quantityResults;
	/** @var Mage_Sales_Model_Quote */
	protected $_quote;
	/** @var Mage_Sales_Model_Quote_Item */
	protected $_quoteItem;
	/** @var Mage_Sales_Model_Quote_Item[] */
	protected $_quoteItems;
	/** @var Mage_Sales_Model_Quote_Item[] */
	protected $_filteredQuoteItems;
	/** @var array Key/value pairs of sku => total quantity */
	protected $_currentItemQuantityData;
	/** @var EbayEnterprise_Inventory_Model_Quantity_Collector */
	protected $_quantityCollector;

	public function setUp()
	{
		// Mock session storage for previously collected quantity results
		$this->_inventorySession = $this
			->getModelMockBuilder('ebayenterprise_inventory/session')
			->setMethods(['getQuantityResults', 'setQuantityResults', 'getResultsCollectedFor', 'setResultsCollectedFor'])
			->disableOriginalConstructor()
			->getMock();

		// Mock of the sdk helper for getting new quantity results from the API
		$this->_quantitySdkHelper = $this->getHelperMock(
			'ebayenterprise_inventory/quantity_sdk',
			['requestQuantityForItems']
		);

		// A quantity results stub, expected to be returned by the session or
		// sdk helper.
		$this->_quantityResults = $this
			->getModelMockBuilder('ebayenterprise_inventory/quantity_results')
			->disableOriginalConstructor()
			->setMethods(['checkResultsApplyToItems'])
			->getMock();

		// Stub quote object to collect quantity records for.
		$this->_quote = $this->getModelMock('sales/quote', ['getAllItems']);
		// Stub quote item that should be sent to the inventory service.
		$this->_quoteItem = $this->getModelMock('sales/quote_item', []);
		// Quote items and filtered quote items allow tests to distinguish
		// between all items in the quote and just items that matter to the
		// inventory service. All quote items includes an additional quote item
		// that is not expected to be sent to the quantity API.
		$this->_quoteItems = [$this->_quoteItem, $this->getModelMock('sales/quote_item', [])];
		$this->_filteredQuoteItems = [$this->_quoteItem];
		// Stub quote to return all quote items.
		$this->_quote->expects($this->any())
			->method('getAllItems')
			->will($this->returnValue($this->_quoteItems));
		// Stub filtering from the full quote items down to the filtered
		// array of quote items. Tests that expect a list of items that matter
		// to the inventory service can expect the filted quote items array
		// instead of the unfiltered quote items array.
		$this->_itemSelection = $this->getHelperMock(
			'ebayenterprise_inventory/item_selection',
			['selectFrom']
		);
		$this->_itemSelection->expects($this->any())
			->method('selectFrom')
			->with($this->identicalTo($this->_quoteItems))
			->will($this->returnValue($this->_filteredQuoteItems));

		// Mock helper for calculating item quantities.
		$this->_quantityHelper = $this->getHelperMock(
			'ebayenterprise_inventory/quantity',
			['calculateTotalQuantitiesBySku']
		);
		$this->_currentItemQuantityData = ['a-sku' => 100];
		$this->_quantityHelper->expects($this->any())
			->method('calculateTotalQuantitiesBySku')
			->with($this->_filteredQuoteItems)
			->will($this->returnValue($this->_currentItemQuantityData));

		$this->_quantityCollector = Mage::getModel(
			'ebayenterprise_inventory/quantity_collector',
			[
				'quantity_sdk_helper' => $this->_quantitySdkHelper,
				'quantity_helper' => $this->_quantityHelper,
				'item_selection' => $this->_itemSelection,
				'inventory_session' => $this->_inventorySession,
			]
		);
	}

	/**
	 * When getting quantity results, if no results have been retrieved yet,
	 * no available in the session, a new result set should be retrieved from
	 * the quantity API, placed into the session and returned.
	 */
	public function testGetQuantityResultsForQuoteNoSaved()
	{
		// Expect no results already in the session.
		$this->_inventorySession->expects($this->any())
			->method('getQuantityResults')
			->will($this->returnValue(null));
		// Side-effect test: ensure that new results are requested from the
		// API via the SDK helper.
		$this->_quantitySdkHelper->expects($this->once())
			->method('requestQuantityForItems')
			->with($this->identicalTo($this->_filteredQuoteItems))
			->will($this->returnValue($this->_quantityResults));
		// Side-effect test: ensure that the newly collected results are put
		// into the session for later use.
		$this->_inventorySession->expects($this->once())
			->method('setQuantityResults')
			->with($this->identicalTo($this->_quantityResults))
			->will($this->returnSelf());

		$this->assertSame(
			$this->_quantityResults,
			$this->_quantityCollector->getQuantityResultsForQuote($this->_quote)
		);
	}

	/**
	 * When the session has results that apply to the quote, the results
	 * stored in the session should be returned and no new quantity results
	 * should be requested.
	 */
	public function testGetQuantityResultsForQuoteFromSession()
	{
		// Expect the session to return quantity results.
		$this->_inventorySession->expects($this->any())
			->method('getQuantityResults')
			->will($this->returnValue($this->_quantityResults));
		// Ensure that no new quantity results are requested.
		$this->_quantitySdkHelper->expects($this->never())
			->method('requestQuantityForItems');
		// Expect the current sku quantity data to match the data the results
		// were collected with - allows results to be applied to the current
		// quote data.
		$this->_quantityResults->expects($this->any())
			->method('checkResultsApplyToItems')
			->with($this->identicalTo($this->_currentItemQuantityData))
			->will($this->returnValue(true));

		$this->assertSame(
			$this->_quantityResults,
			$this->_quantityCollector->getQuantityResultsForQuote($this->_quote)
		);
	}

	/**
	 * When the session has results but they no longer apply to the quote,
	 * a new set of results should be requested and replace the existing
	 * results in the session.
	 */
	public function testGetQuantityResultsForQuoteSessionDataOutdated()
	{
		$outdatedResults = $this->getModelMockBuilder('ebayenterprise_inventory/quantity_results')
			->disableOriginalConstructor()
			->setMethods(['checkResultsApplyToItems'])
			->getMock();
		$outdatedResults->expects($this->any())
			->method('checkResultsApplyToItems')
			->with($this->_currentItemQuantityData)
			->will($this->returnValue(false));

		// Expect the session to return quantity results.
		$this->_inventorySession->expects($this->any())
			->method('getQuantityResults')
			->will($this->returnValue($outdatedResults));
		// Side-effect test: ensure that new results are requested from the
		// API via the SDK helper.
		$this->_quantitySdkHelper->expects($this->once())
			->method('requestQuantityForItems')
			->with($this->identicalTo($this->_filteredQuoteItems))
			->will($this->returnValue($this->_quantityResults));
		// Side-effect test: ensure that the newly collected results are put
		// into the session for later use.
		$this->_inventorySession->expects($this->once())
			->method('setQuantityResults')
			->with($this->identicalTo($this->_quantityResults))
			->will($this->returnSelf());

		$this->assertSame(
			$this->_quantityResults,
			$this->_quantityCollector->getQuantityResultsForQuote($this->_quote)
		);
	}

	/**
	 * When clearing collected results, any stored quantity results should
	 * be removed from storage, preventing them from being reused.
	 */
	public function testClearResults()
	{
		// Side-effect test: results currently stored in session, setting the
		// sessions quantity results to null will remove any existing results
		// from being reused.
		$this->_inventorySession->expects($this->once())
			->method('setQuantityResults')
			->with($this->identicalTo(null))
			->will($this->returnSelf());

		$this->assertSame(
			$this->_quantityCollector,
			$this->_quantityCollector->clearResults()
		);
	}
}
