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

class EbayEnterprise_Eb2cOrder_Test_Helper_DataTest extends EbayEnterprise_Eb2cOrder_Test_Abstract
{
	protected $_helper;
	// @var Mage_Core_Model_App original Mage::app instance
	protected $_origApp;
	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_origApp = EcomDev_Utils_Reflection::getRestrictedPropertyValue('Mage', '_app');
		$this->_helper = Mage::helper('eb2corder');
	}
	/**
	 * Restore original Mage::app instance
	 */
	public function tearDown()
	{
		EcomDev_Utils_Reflection::setRestrictedPropertyValue('Mage', '_app', $this->_origApp);
		parent::tearDown();
	}
	/**
	 * Make sure we get back a EbayEnterprise_Eb2cCore_Model_Config_Registry and that
	 * we can see some sensible values in it.
	 * @loadFixture basicTestConfig.yaml
	 */
	public function testGetConfig()
	{
		$this->replaceCoreConfigRegistry(
			array(
				'apiRegion' => 'api_rgn',
				'clientId'  => 'client_id',
			)
		);
		$config = $this->_helper->getConfigModel();
		$this->assertStringStartsWith(
			'api_rgn',
			$config->apiRegion
		);
		$this->assertStringStartsWith(
			'client_id',
			$config->clientId
		);
	}

	/**
	 * Testing getOperationUri method with both create and cancel operations
	 *
	 * @loadFixture basicTestConfig.yaml
	 */
	public function testGetOperationUri()
	{
		$this->replaceCoreConfigRegistry(
			array(
				'apiRegion' => 'api_rgn',
				'clientId'  => 'client_id',
			)
		);
		$this->assertStringEndsWith(
			'create.xml',
			$this->_helper->getOperationUri('create')
		);

		$this->assertStringEndsWith(
			'cancel.xml',
			$this->_helper->getOperationUri('cancel')
		);
	}

	/**
	 * Test method to map Eb2c Status to Mage State
	 * @loadFixture testMapEb2cStatus.yaml
	 */
	public function testMapEb2cStatus()
	{
		// clear out the helper's order status cache so it will get populated
		// with the fixture data
		EcomDev_Utils_Reflection::setRestrictedPropertyValue(
			Mage::helper('eb2corder'),
			'_orderStatusCollection',
			null
		);
		$aKnownEb2cStatus = 'Some Test Value Called Horse';
		$this->assertSame(
			'reined_in',
			Mage::helper('eb2corder')->mapEb2cOrderStatusToMage($aKnownEb2cStatus)
		);

		$anUnknownEb2cStatus = '8edfa9d(*&^(*&^(*Q&#^$*(&Q^#$&*^Q#$fa9d3b2';
		$this->assertSame(
			'new',
			Mage::helper('eb2corder')->mapEb2cOrderStatusToMage($anUnknownEb2cStatus)
		);

		// clear out the helper's order status cache so it doesn't stay populated
		// with the fixture data
		EcomDev_Utils_Reflection::setRestrictedPropertyValue(
			Mage::helper('eb2corder'),
			'_orderStatusCollection',
			null
		);
	}

	/**
	 * Test getting an order history URL for a given store
	 */
	public function testOrderHistoryUrl()
	{
		$order = Mage::getModel('sales/order', array('store_id' => 1, 'entity_id' => 5));

		$urlMock = $this->getModelMock('core/url', array('getUrl'));
		$urlMock->expects($this->once())
			->method('getUrl')
			->with($this->identicalTo('sales/order/view'), $this->identicalTo(array('_store' => 1, 'order_id' => 5)))
			->will($this->returnValue('http://test.example.com/mocked/order/create'));
		$this->replaceByMock('model', 'core/url', $urlMock);
		$this->assertSame(
			'http://test.example.com/mocked/order/create',
			Mage::helper('eb2corder')->getOrderHistoryUrl($order)
		);
	}
	/**
	 * Test removing the order increment id prefix.
	 */
	public function testRemoveOrderIncrementPrefix()
	{
		$admin = Mage::getModel('core/store', array('store_id' => 0));
		$default = Mage::getModel('core/store', array('store_id' => 1));

		$app = $this->getModelMock('core/app', array('getStores'));
		$app->expects($this->any())
			->method('getStores')
			->will($this->returnValueMap(array(
				array(true, false, array(0 => $admin, 1 => $default)),
				array(false, false, array(1 => $default))
			)));

		$adminConfig = $this->buildCoreConfigRegistry(array('clientOrderIdPrefix' => '555'));
		$storeConfig = $this->buildCoreConfigRegistry(array('clientOrderIdPrefix' => '7777'));
		$coreHelper = $this->getHelperMock('eb2ccore/data', array('getConfigModel'));
		$coreHelper->expects($this->any())
			->method('getConfigModel')
			->will($this->returnValueMap(array(
				array(0, $adminConfig),
				array(1, $storeConfig),
			)));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelper);
		EcomDev_Utils_Reflection::setRestrictedPropertyValue('Mage', '_app', $app);
		// should be able to replace the order id prefix from any config scope
		$this->assertSame('8888888', $this->_helper->removeOrderIncrementPrefix('77778888888'));
		$this->assertSame('8888888', $this->_helper->removeOrderIncrementPrefix('5558888888'));
		// when no matching prefix on the original increment id, should return unmodified value
		$this->assertSame('1238888888', $this->_helper->removeOrderIncrementPrefix('1238888888'));
		// must work with null as when the first increment id for a store is
		// created, the "last id" will be given as null
		$this->assertSame('', $this->_helper->removeOrderIncrementPrefix(null));
	}
	/**
	 * Test getting a collection of orders for use when displaying an order
	 * history summary.
	 */
	public function testGetCurCustomerOrders()
	{
		// data for the order, some of which should end up somewhere on the order
		$orderIncrementId = '000120000001';
		$customerPrefix = '0001';
		$customerId = 7;
		// expected parsed data coming from the service API call
		$orderSummaries = array(
			$orderIncrementId => new Varien_Object(array(
				'customer_order_id' => $orderIncrementId,
				'customer_id' => $customerPrefix . $customerId,
			)),
			'increment-not-in-mage' => new Varien_Object(array(
				'customer_order_id' => 'increment-not-in-mage',
				'customer_id' => $customerPrefix . $customerId,
			)),
		);

		// mock out the eb2ccore/data helper's config - script the customer id prefix
		$coreHelper = $this->getHelperMock('eb2ccore/data', array('getConfigModel'));
		$coreHelper->expects($this->any())
			->method('getConfigModel')
			->will($this->returnValue($this->buildCoreConfigRegistry(array('clientCustomerIdPrefix' => $customerPrefix))));
		$this->replaceByMock('helper', 'eb2ccre', $coreHelper);

		// mock out the order search model which is responsible for getting
		// the order summary data via an API call
		$orderSearch = $this->getModelMock('eb2corder/customer_order_search', array('getOrderSummaryData'));
		$orderSearch->expects($this->once())
			->method('getOrderSummaryData')
			->will($this->returnValue($orderSummaries));
		$this->replaceByMock('model', 'eb2corder/customer_order_search', $orderSearch);

		// current logged in customer
		$customer = Mage::getModel('customer/customer', array('entity_id' => $customerId));

		$orderHelper = $this->getHelperMock('eb2corder/data', array('_getCurrentCustomer'));
		$orderHelper->expects($this->any())
			->method('_getCurrentCustomer')
			->will($this->returnValue($customer));

		$orderCollection = $orderHelper->getCurCustomerOrders();
		// Ensure an eb2corder/summary_order_collection is returned
		$this->assertInstanceOf(
			'EbayEnterprise_Eb2cOrder_Model_Resource_Summary_Order_Collection',
			$orderCollection
		);
		// Using assertEquals false as this don't need to be strictly false, but
		// must be a falsey value (such as null) to indicate the collection has
		// yet to be loaded - collection must not have been loaded yet so pagination
		// or other filters can still be applied. Also means this test must be
		// careful not to trigger the collection::load.
		$this->assertEquals(false, $orderCollection->isLoaded());
		// returned collection must have the customer id matching the customer id
		// the request was made for - should be Magento customer id, not prefixed
		$this->assertSame($customerId, $orderCollection->getCustomerId());
	}
	/**
	 * Test getting orders where thare is not a customer logged in. Should return
	 * an empty order collection.
	 */
	public function testGetCurCustomerOrdersNoCustomer()
	{
		$emptyCollection = $this->getResourceModelMock('sales/order_collection', array('isLoaded', 'addFieldToFilter'));
		$emptyCollection->expects($this->any())
			->method('isLoaded')
			->will($this->returnValue(true));

		// assertion that the collection is guaranteed to be empty - filter by
		// entity id, pk, equals null
		$emptyCollection->expects($this->atLeastOnce())
			->method('addFieldToFilter')
			->with($this->identicalTo('entity_id'), $this->isNull())
			->will($this->returnSelf());

		$helper = $this->getHelperMock(
			'eb2corder/data',
			array('_getPrefixedCurrentCustomerId', '_getSummaryOrderCollection')
		);
		// simulate no current customer
		$helper->expects($this->any())
			->method('_getPrefixedCurrentCustomerId')
			->will($this->returnValue(null));
		// swap out mocked order collection
		$helper->expects($this->once())
			->method('_getSummaryOrderCollection')
			->will($this->returnValue($emptyCollection));
		// make the call to ensure proper behavior of collection filtering
		$helper->getCurCustomerOrders();
	}
	/**
	 * Provide a customer id, configured customer id prefix and the fully prefixed id
	 * @return array
	 */
	public function provideCustomerId()
	{
		$prefix = '0001';
		$customerId = '3';
		return array(
			array($customerId, $prefix, $prefix . $customerId),
			array(null, $prefix, null),
		);
	}
	/**
	 * Test getting the current user id, prefixed by the configured client
	 * customer id prefix.
	 * @param string|null $customer Customer retrieved from the session
	 * @param string $prefix Client customer id prefix
	 * @param string|null $prefixedId prefixed customer id
	 * @dataProvider provideCustomerId
	 */
	public function testGetPrefixedCurrentCustomerId($customerId, $prefix, $prefixedId)
	{
		$customer = Mage::getModel('customer/customer', array('entity_id' => $customerId));
		// when no existing customer logged in, session will return empty customer
		$customerSession = $this->getModelMockBuilder('customer/session')
			->disableOriginalConstructor()
			->setMethods(array('getCustomer'))
			->getMock();
		$customerSession->expects($this->any())
			->method('getCustomer')
			->will($this->returnValue($customer));
		$customerSession->setCustomer($customer);
		$this->replaceByMock('singleton', 'customer/session', $customerSession);

		$coreHelper = $this->getHelperMock('eb2ccore/data', array('getConfigModel'));
		$coreHelper->expects($this->any())
			->method('getConfigModel')
			->will($this->returnValue($this->buildCoreConfigRegistry(array('clientCustomerIdPrefix' => $prefix))));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelper);

		$helper = Mage::helper('eb2corder');
		$this->assertSame(
			$prefixedId,
			EcomDev_Utils_Reflection::invokeRestrictedMethod($helper, '_getPrefixedCurrentCustomerId')
		);
	}
	/**
	 * Test getting an order summary response from cache
	 */
	public function testGetCachedOrderSummaryResponse()
	{
		$helper = Mage::helper('eb2corder');
		EcomDev_Utils_Reflection::setRestrictedPropertyValue(
			$helper,
			'_orderSummaryResponses',
			array(
				'00001-' => '<customerIdOnly/>',
				'00001-12341234' => '<customerAndOrderId/>',
			)
		);
		// search by just customer id, customer id and order id
		$this->assertSame('<customerIdOnly/>', $helper->getCachedOrderSummaryResponse('00001', ''));
		$this->assertSame('<customerAndOrderId/>', $helper->getCachedOrderSummaryResponse('00001', '12341234'));
		// non-cached searches should return null - nothing found, nothing to return
		$this->assertNull($helper->getCachedOrderSummaryResponse('00006', '12341234'));

		// empty out the cache so as to not disrupt other test runs
		EcomDev_Utils_Reflection::setRestrictedPropertyValue(
			$helper,
			'_orderSummaryResponses',
			array()
		);
	}
	/**
	 * Test that the method EbayEnterprise_Eb2cOrder_Helper_Data::getOrderCollectionByIncrementIds
	 * return a Mage_Sales_Model_Resource_Order_Collection object.
	 */
	public function testGetOrderCollectionByIncrementIds()
	{
		$incrementIds = array('0004600007', '0004600009', '0004600011');
		$orderCollection = $this->getResourceModelMock('sales/order_collection', array(
			'addFieldToFilter'
		));
		$orderCollection->expects($this->once())
			->method('addFieldToFilter')
			->with($this->identicalTo('increment_id'), $this->identicalTo( array('in' => $incrementIds)))
			->will($this->returnSelf());
		$this->replaceByMock('resource_model', 'sales/order_collection', $orderCollection);

		$this->assertSame($orderCollection, Mage::helper('eb2corder')->getOrderCollectionByIncrementIds($incrementIds));
	}
	/**
	 * Test that the method EbayEnterprise_Eb2cOrder_Helper_Data::extractOrderEventIncrementIds
	 * will return an empty array when an empty xml string is passed to it.
	 */
	public function testextractOrderEventIncrementIds()
	{
		$this->assertSame(array(), Mage::helper('eb2corder')->extractOrderEventIncrementIds('', '//some/x/path'));
	}
	/**
	 * Test the helper method 'EbayEnterprise_Eb2cOrder_Helper_Data::calculateGwItemRowTotal' using a data provider
	 * that will test various scenarios and the expects the return value to be the same as known result. The first
	 * scenario will instantiate a 'sales/order_item' object and initialize it with known gift wrapping price and
	 * quantity data and then expects it to be the same as the product of quantity time the gift wrapping price.
	 * The second scenario will instantiate a 'sales/order' object and initialize it with a known quantity, a known
	 * gift wrapping price and expects only the gift wrapping price to be returned.
	 * @param string $class
	 * @param array $data
	 * @param float $expected
	 * @dataProvider dataProvider
	 */
	public function testcalculateGwItemRowTotal($class, array $data, $expected)
	{
		$this->assertSame($expected, Mage::helper('eb2corder')->calculateGwItemRowTotal(Mage::getModel($class, $data)));
	}
}
