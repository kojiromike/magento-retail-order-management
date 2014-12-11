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

class EbayEnterprise_Eb2cCustomerService_Test_Model_Overrides_Admin_SessionTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * Test getting the current user in session's start page url, running it
	 * through the adminhtml/url method is necessary to account for url secret
	 * key validation
	 * @param  bool $useSecretKey
	 * @dataProvider provideTrueFalse
	 */
	public function testGetStartpageUri($useSecretKey)
	{
		$adminUrl = 'admin/some/where';
		$adminWithKey = 'admin/some/where/key/123';
		$expectedUrl = $useSecretKey ? $adminWithKey : $adminUrl;

		$session = $this->getModelMockBuilder('admin/session')
			->disableOriginalConstructor()
			->setMethods(array('getUser'))
			->getMock();
		$user = $this->getModelMockBuilder('admin/user')
			->disableOriginalConstructor()
			->setMethods(array('getStartupPageUrl'))
			->getMock();
		$url = $this->getModelMockBuilder('adminhtml/url')
			->disableOriginalConstructor()
			->setMethods(array('useSecretKey', 'getUrl'))
			->getMock();
		$this->replaceByMock('model', 'adminhtml/url', $url);

		$session->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));
		$user->expects($this->once())
			->method('getStartupPageUrl')
			->will($this->returnValue($adminUrl));
		$url->expects($this->once())
			->method('useSecretKey')
			->will($this->returnValue($useSecretKey));
		if ($useSecretKey) {
			$url->expects($this->once())
				->method('getUrl')
				->with($this->identicalTo($adminUrl))
				->will($this->returnValue($adminWithKey));
		} else {
			$url->expects($this->never())
				->method('getUrl');
		}

		$this->assertSame(
			$expectedUrl,
			EcomDev_Utils_Reflection::invokeRestrictedMethod($session, '_getStartpageUri')
		);
	}
}
