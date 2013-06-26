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
		$product = Mage::getModel('catalog/product')->load(1);
		$product->setWebsiteIds(array(0,1,2,3,4));
		return array(
			array($product, null)
		);
	}

	/**
	 * testing addProduct method
	 *
	 * @test
	 * @dataProvider providerAddProduct
	 * @expectedException Exception
	 * @loadFixture loadWebsiteConfig.yaml
	 */
	public function testAddProduct($productInfo, $requestInfo=null)
	{
		$this->assertNotNull(
			$this->_getCart()->addProduct($productInfo, $requestInfo)
		);
	}
}
