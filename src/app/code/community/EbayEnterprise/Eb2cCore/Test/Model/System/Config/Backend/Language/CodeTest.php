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

class EbayEnterprise_Eb2cCore_Test_Model_System_Config_Backend_Language_CodeTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/*
	 * @test
	 * Test that our method trims spaces and lower-cases its value
	 */
	public function testLanguageCodeToLower()
	{
		/*
		 * We've only overridden the _beforeSave method. We want to make sure it thinks
		 * there's been a change so we return true on 'isValueChanged'.
	 	 */
		$backendMock = $this->getModelMockBuilder('eb2ccore/system_config_backend_language_code')
			->setMethods(array( 'isValueChanged',))
			->getMock();
		$backendRefl = new ReflectionObject($backendMock);
		$beforeSave = $backendRefl->getMethod('_beforeSave');
		$beforeSave->setAccessible(true);
		$backendMock->expects($this->once())
			->method('isValueChanged')
			->will($this->returnValue(true));

		$this->assertSame('en-us',
			$backendMock->setValue("\r\n\t EN-US")->_beforeSave()->getValue());
	}
}
