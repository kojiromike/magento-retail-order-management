<?php
/**
 * @category  TrueAction
 * @package   TrueAction_Eb2c
 * @copyright Copyright (c) 2013 True Action (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Inventory_Test_Helper_DataTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_helper;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_helper = $this->_getHelper();
		Mage::app()->getConfig()->reinit(); // re-initialize config to get fresh loaded data
	}

	/**
	 * Get helper instantiated object.
	 *
	 * @return TrueAction_Eb2c_Inventory_Helper_Data
	 */
	protected function _getHelper()
	{
		$this->_helper = Mage::helper('eb2cinventory');
		return $this->_helper;
	}

	/**
	 * testing getXmlNs method
	 *
	 * @test
	 */
	public function getXmlNs()
	{
		$this->assertSame(
			'http://api.gsicommerce.com/schema/checkout/1.0',
			$this->_getHelper()->getXmlNs()
		);
	}

	/**
	 * testing getOperationUri method
	 *
	 * @test
	 * @loadFixture loadConfig.yaml
	 */
	public function getOperationUri()
	{
		$config = new TrueAction_Eb2c_Core_Helper_Config();
		$config->developer_mode = 0;
		$config->quantity_api_uri = '';
		$config->inventory_detail_uri = '';
		$config->allocation_uri = '';
		$config->rollback_allocation_uri = '';

		$reflector = new ReflectionObject($this->_getHelper());
		$configModel = $reflector->getProperty('configModel');
		$configModel->setAccessible(true);
		$configModel->setValue($this->_getHelper(), $config);

		$this->assertSame(
			'https://developer.na.gsipartners.com/v1.10/stores/ABCD/inventory/quantity/get.xml',
			$this->_getHelper()->getOperationUri('check_quantity')
		);

		$this->assertSame(
			'https://developer.na.gsipartners.com/v1.10/stores/ABCD/inventory/details/get.xml',
			$this->_getHelper()->getOperationUri('get_inventory_details')
		);

		$this->assertSame(
			'https://developer.na.gsipartners.com/v1.10/stores/ABCD/inventory/allocations/create.xml',
			$this->_getHelper()->getOperationUri('allocate_inventory')
		);

		$this->assertSame(
			'https://developer.na.gsipartners.com/v1.10/stores/ABCD/inventory/allocations/delete.xml',
			$this->_getHelper()->getOperationUri('rollback_allocation')
		);
	}

	public function providerGetRequestId()
	{
		return array(
			array(43)
		);
	}

	/**
	 * testing helper data getRequestId method
	 *
	 * @test
	 * @loadFixture loadConfig.yaml
	 * @dataProvider providerGetRequestId
	 */
	public function testGetRequestId($entityId)
	{
		$this->assertSame(
			'TAN-CLI-ABCD-43',
			$this->_getHelper()->getRequestId($entityId)
		);
	}

	public function providerGetReservationId()
	{
		return array(
			array(43)
		);
	}

	/**
	 * testing helper data getReservationId method
	 *
	 * @test
	 * @loadFixture loadConfig.yaml
	 * @dataProvider providerGetReservationId
	 */
	public function testGetReservationId($entityId)
	{
		$this->assertSame(
			'TAN-CLI-ABCD-43',
			$this->_getHelper()->getReservationId($entityId)
		);
	}
}
