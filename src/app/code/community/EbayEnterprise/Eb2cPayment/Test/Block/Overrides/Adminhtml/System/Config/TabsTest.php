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

class EbayEnterprise_Eb2cPayment_Test_Block_Overrides_Adminhtml_System_Config_TabsTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	public function tearDown()
	{
		parent::tearDown();
		Mage::unregister('_helper/eb2cpayment');
	}

	/**
	 * @test
	 * verify the isConfigSuppressed function is called with the correct parameters.
	 */
	public function testCheckSectionPermissionsSuppressionCall()
	{
		$sectionName = 'giftcard';
		$testModel = $this->getBlockMockBuilder('adminhtml/system_config_tabs')
			->disableOriginalConstructor()
			->setMethods(array('none'))
			->getMock();
		$this->assertInstanceOf(
			'EbayEnterprise_Eb2cPayment_Overrides_Block_Adminhtml_System_Config_Tabs',
			$testModel,
			'message'
		);

		$session = $this->getModelMockBuilder('admin/session')
			->disableOriginalConstructor()
			->setMethods(array('isAllowed'))
			->getMock();
		$session->expects($this->any())
			->method('isAllowed')
			->will($this->returnValue(true));
		$this->replaceByMock('singleton', 'admin/session', $session);

		$suppression = $this->getModelMock('eb2cpayment/suppression', array(
			'isConfigSuppressed',
		));
		$suppression->expects($this->once())
			->method('isConfigSuppressed')
			->with($this->identicalTo($sectionName));
		$this->replaceByMock('model', 'eb2cpayment/suppression', $suppression);

		$testModel->checkSectionPermissions('giftcard');
	}
}
