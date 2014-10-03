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

class EbayEnterprise_Catalog_Test_Model_ObserversTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * @loadFixture readOnlyAttributes.yaml
	 * lockReadOnlyAttributes reads the config for the attribute codes it needs to protect
	 * from admin panel edits by issuing a lockAttribute against the attribute code.
	 */
	public function testLockReadOnlyAttributes()
	{
		$product = $this->getModelMock('catalog/product', array('lockAttribute'));
		$product->expects($this->exactly(3))
			->method('lockAttribute');

		$varienEvent = $this->getMock('Varien_Event', array('getProduct'));
		$varienEvent->expects($this->once())
			->method('getProduct')
			->will($this->returnValue($product));

		$varienEventObserver = $this->getMock('Varien_Event_Observer', array('getEvent'));
		$varienEventObserver->expects($this->once())
			->method('getEvent')
			->will($this->returnValue($varienEvent));

		Mage::getModel('ebayenterprise_catalog/observers')->lockReadOnlyAttributes($varienEventObserver);
	}
}
