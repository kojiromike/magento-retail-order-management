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

class EbayEnterprise_Eb2cOrder_Test_Helper_FactoryTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	/** @var EbayEnterprise_Eb2cOrder_Helper_Factory */
	protected $_factory;

	public function setUp()
	{
		parent::setUp();
		$this->_factory = Mage::helper('eb2corder/factory');
	}

	/**
	 * Test that the controller method eb2corder/factory::getCoreSessionModel()
	 * is invoked and it will instantiate an object of type core/session and return this object.
	 */
	public function testGetCoreSessionModel()
	{
		/** @var Mock_Mage_Core_Model_Session */
		$session = $this->getModelMockBuilder('core/session')
			// Disabling the constructor in order to prevent session_start() function from being
			// called which causes headers already sent exception from being thrown.
			->disableOriginalConstructor()
			->getMock();
		$this->replaceByMock('singleton', 'core/session', $session);

		$this->assertSame($session, $this->_factory->getCoreSessionModel());
	}

	/**
	 * Test that the controller method eb2corder/factory::_getNewRomOrderDetailModel()
	 * is invoked and it will instantiate an object of type eb2corder/detail and return this object.
	 */
	public function testGetNewRomOrderDetailModel()
	{
		/** @var Mock_EbayEnterprise_Eb2cOrder_Model_Detail */
		$detail = $this->getModelMock('eb2corder/detail');
		$this->replaceByMock('model', 'eb2corder/detail', $detail);
		$this->assertSame($detail, $this->_factory->getNewRomOrderDetailModel());
	}
}
