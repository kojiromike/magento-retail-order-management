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

class EbayEnterprise_Eb2cOrder_Test_Model_Eav_Entity_Increment_OrderTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	// @var Mage_Core_Model_App initial Mage::app value
	protected $_origApp;
	// @var int id of the store order is being placed in
	public $targetStoreId = 1;
	// @var int expected next increment id
	public $nextId = '55500000002';
	// @var int the last entity increment id
	public $lastId = '55500000001';
	// @var int expected increment prefix
	public $configPrefix = '555';
	// @var int increment prefix of a different store view
	public $configAltPrefix = '7777';
	// @var Mock_EbayEnterprise_Eb2cCore_Helper_Data mock core helper scripted to return config
	public $orderHelper;
	// @var Mock_EbayEnterprise_Eb2cCore_Model_Config_Registry config registry expected to be used
	public $coreConfig;
	// @var Mock_EbayEnterprise_Eb2cCore_Model_Config_Registry config registry not expected to be used
	public $coreConfigDefault;
	// @var Mock_Mage_Core_Model_App mock app scripted to return expected store information
	public $testApp;
	// @var EbayEnterprise_Eb2cOrder_Model_Eav_Entity_Increment_Order test object
	public $incrementModel;
	// @var Mock_Mage_Adminhtml_Model_Session_Quote scripted to return store order is meant for
	public $quoteSession;
	/**
	 * Set up dependent systems
	 */
	public function setUp()
	{
		$this->adminStore = Mage::getModel('core/store', array('store_id' => Mage_Core_Model_App::ADMIN_STORE_ID));
		$this->targetStore = Mage::getModel('core/store', array('store_id' => 1));

		// swap out the Mage::app instance with a scriptable mock
		$this->_origApp = Mage::app();
		$this->testApp = $this->getModelMock('core/app', array('getStores', 'getStore'));
		// script getting all stores but not getting the current store as that will be test dependent
		$this->testApp->expects($this->any())
			->method('getStores')
			->will($this->returnValueMap(array(
				array(true, false, array(0 => $this->adminStore, 1 => $this->targetStore)),
				array(false, false, array(1 => $this->targetStore)),
			)));

		$this->incrementModel = Mage::getModel('eb2corder/eav_entity_increment_order');

		$this->quoteSession = $this->getModelMockBuilder('adminhtml/session_quote')
			->disableOriginalConstructor()
			->setMethods(array('getStore'))
			->getMock();
		$this->quoteSession->expects($this->any())
			->method('getStore')
			->will($this->returnValue($this->targetStore));

		$this->coreConfigDefault = $this->buildCoreConfigRegistry(array('clientOrderIdPrefix' => $this->configAltPrefix));
		$this->coreConfig = $this->buildCoreConfigRegistry(array('clientOrderIdPrefix' => $this->configPrefix));

		$this->orderHelper = $this->getHelperMock('eb2corder/data', array('getConfig'));
		// script config for the admin store and target store
		$this->orderHelper->expects($this->any())
			->method('getConfig')
			->will($this->returnValueMap(array(
				array(Mage_Core_Model_App::ADMIN_STORE_ID, $this->coreConfigDefault),
				array($this->targetStoreId, $this->coreConfig),
			)));
	}

	/**
	 * Put the system back in order - put the original app back in place
	 */
	public function tearDown()
	{
		EcomDev_Utils_Reflection::setRestrictedPropertyValue('Mage', '_app', $this->_origApp);
		parent::tearDown();
	}
	/**
	 * Test getting the next increment id when in a frontend/non-admin store
	 */
	public function testGetNextId()
	{
		$this->testApp->expects($this->any())
			->method('getStore')
			->will($this->returnValue($this->targetStore));
		$this->replaceByMock('helper', 'eb2ccore', $this->orderHelper);
		$this->incrementModel->setData(array(
			'last_id' => $this->lastId,
			'prefix' => 'fake_prefix',
			'pad_length' => 8,
			'pad_char' => 0,
		));
		EcomDev_Utils_Reflection::setRestrictedPropertyValue('Mage', '_app', $this->testApp);
		$this->assertSame($this->nextId, $this->incrementModel->getNextId());
	}
	/**
	 * Test getting the next increment id when in the admin store
	 */
	public function testGetNextIdAdminStore()
	{
		$this->replaceByMock('singleton', 'adminhtml/session_quote', $this->quoteSession);

		$this->testApp->expects($this->any())
			->method('getStore')
			->will($this->returnValue($this->adminStore));
		$this->replaceByMock('helper', 'eb2ccore', $this->orderHelper);
		$this->incrementModel->setData(array(
			'last_id' => $this->lastId,
			'prefix' => 'fake_prefix',
			'pad_length' => 8,
			'pad_char' => 0,
		));
		EcomDev_Utils_Reflection::setRestrictedPropertyValue('Mage', '_app', $this->testApp);
		$this->assertSame($this->nextId, $this->incrementModel->getNextId());
	}
}
