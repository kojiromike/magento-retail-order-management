<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Inventory_Test_Model_Overrides_CartTest extends EcomDev_PHPUnit_Test_Case_Controller
{
	protected $_cart;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		$_SESSION = array();
		$_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
		$this->app()->getRequest()->setBaseUrl($_baseUrl);
		$this->_cart = $this->_getCart();
		Mage::app()->getConfig()->reinit(); // re-initialize configuration to get fresh loaded data
	}

	/**
	 * Get Cart instantiated object.
	 *
	 * @return TrueAction_Eb2c_Inventory_Override_Model_Cart
	 */
	protected function _getCart()
	{
		if (!$this->_cart) {
			$this->_cart = Mage::getModel('eb2cinventoryoverride/cart');
		}
		return $this->_cart;
	}

	public function providerAddProduct()
	{
		$productMock = $this->getMock('Mage_Catalog_Model_Product', array('getId', 'getWebsiteIds', 'hasOptionsValidationFail'));
		$productMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$productMock->expects($this->any())
			->method('getWebsiteIds')
			->will($this->returnValue(array(0,1,2,3,4)));
		$productMock->expects($this->any())
			->method('hasOptionsValidationFail')
			->will($this->returnValue(true));

		$product = Mage::getModel('catalog/product')->load(1);
		$product->setWebsiteIds(array(0,1,2,3,4));
		return array(
			array($productMock, null)
		);
	}

	/**
	 * testing addProduct method
	 *
	 * @test
	 * @expectedException Mage_Core_Exception
	 * @dataProvider providerAddProduct
	 * @loadFixture loadWebsiteConfig.yaml
	 */
	public function testAddProduct($productInfo, $requestInfo=null)
	{
		$quoteMock = $this->getMock('Mage_Sales_Model_Quote', array('addProduct'));
		$quoteMock->expects($this->any())
			->method('addProduct')
			->will($this->returnValue('some message'));

		$this->assertNotNull(
			$this->_getCart()->addProduct($productInfo, $requestInfo)
		);

		// testing when product id is not a valid integer value
		$productInfo->setId(-1);
		$this->assertNotNull(
			$this->_getCart()->addProduct($productInfo, $requestInfo)
		);

	}

	/**
	 * testing addProduct method, with exception being thrown
	 *
	 * @test
	 * @dataProvider providerAddProduct
	 * @expectedException Mage_Core_Exception
	 * @loadFixture loadWebsiteConfig.yaml
	 */
	public function testAddProductWithThrownException($productInfo, $requestInfo=null)
	{
		$quoteMock = $this->getMock('Mage_Sales_Model_Quote', array('addProduct'));
		$quoteMock->expects($this->any())
			->method('addProduct')
			->will(
				$this->throwException(new Mage_Core_Exception('Unit test Exception'))
			);

		$this->_getCart()->setQuote($quoteMock);

		$this->assertNotNull(
			$this->_getCart()->addProduct($productInfo, $requestInfo)
		);
	}
}
