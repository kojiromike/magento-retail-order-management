<?php
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
	 * @test
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
		$config = $this->_helper->getConfig();
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
		$orderHelper = $this->getHelperMock('eb2corder/data', array('getConfig'));
		$orderHelper->expects($this->any())
			->method('getConfig')
			->will($this->returnValueMap(array(
				array(0, $adminConfig),
				array(1, $storeConfig),
			)));
		EcomDev_Utils_Reflection::setRestrictedPropertyValue('Mage', '_app', $app);
		// should be able to replace the order id prefix from any config scope
		$this->assertSame('8888888', $orderHelper->removeOrderIncrementPrefix('77778888888'));
		$this->assertSame('8888888', $orderHelper->removeOrderIncrementPrefix('5558888888'));
		// when no matching prefix on the original increment id, should return unmodified value
		$this->assertSame('1238888888', $orderHelper->removeOrderIncrementPrefix('1238888888'));
		// must work with null as when the first increment id for a store is
		// created, the "last id" will be given as null
		$this->assertSame('', $orderHelper->removeOrderIncrementPrefix(null));
	}
}
