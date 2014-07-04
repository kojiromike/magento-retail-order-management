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


class EbayEnterprise_Eb2cCustomerService_Test_Helper_DataTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * Test getting a config registry instance with the eb2ccsr
	 * configuration loaded.
	 */
	public function testGetConfigModel()
	{
		$cfg = $this->getModelMock('eb2ccore/config_registry', array('addConfigModel', 'setStore'));
		$cfg->expects($this->once())
			->method('setStore')
			->with($this->identicalTo(null))
			->will($this->returnSelf());
		$cfg->expects($this->once())
			->method('addConfigModel')
			->with($this->isInstanceOf('EbayEnterprise_Eb2cCustomerService_Model_Config'))
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2ccore/config_registry', $cfg);

		$this->assertSame(
			$cfg,
			Mage::helper('eb2ccsr')->getConfigModel()
		);
	}
	/**
	 * Test validating the token by making a request using the token
	 * request model and checking if the response is valid using the
	 * response model. When successful, method should simply return self.
	 */
	public function testValidateTokenPass()
	{
		$token = '123-abc';
		$response = '<MockTokenResponse/>';
		$csrData = array('rep_id' => '123-abc', 'name' => 'Some Body');
		$cfg = $this->buildCoreConfigRegistry(array(
			'isCsrLoginEnabled' => true
		));

		$tokenRequest = $this->getModelMockBuilder('eb2ccsr/token_request')
			->setMethods(array('makeRequest'))
			->getMock();
		$this->replaceByMock('model', 'eb2ccsr/token_request', $tokenRequest);
		$tokenResponse = $this->getModelMockBuilder('eb2ccsr/token_response')
			->setMethods(array('isTokenValid', 'getCSRData'))
			->getMock();
		$this->replaceByMock('model', 'eb2ccsr/token_response', $tokenResponse);
		$adminSession = $this->getModelMockBuilder('admin/session')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$this->replaceByMock('model', 'admin/session', $adminSession);
		$helper = $this->getHelperMock('eb2ccsr/data', array('getConfigModel'));

		$helper->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue($cfg));
		$tokenRequest->expects($this->once())
			->method('makeRequest')
			->will($this->returnValue($response));
		$tokenResponse->expects($this->once())
			->method('getCSRData')
			->will($this->returnValue($csrData));
		$tokenResponse->expects($this->once())
			->method('isTokenValid')
			->will($this->returnValue(true));

		$this->assertSame(
			$helper,
			$helper->validateToken($token)
		);
		// Test that the response model has had the response message set on it
		$this->assertSame($response, $tokenResponse->getMessage());
		// The admin session should have a eb2ccsr/representative instance
		// with data matching the data extracted from the message by
		// the response model
		$this->assertInstanceOf(
			'EbayEnterprise_Eb2cCustomerService_Model_Representative',
			$adminSession->getCustomerServiceRep()
		);
		$this->assertSame($csrData, $adminSession->getCustomerServiceRep()->getData());
	}
	/**
	 * When the token is not valid, throw an exception.
	 * @param bool $isCsrEnabled
	 * @dataProvider provideTrueFalse
	 */
	public function testValidateTokenFail($isCsrEnabled)
	{
		$token = '123-abc';
		$response = '<MockTokenResponse/>';
		$cfg = $this->buildCoreConfigRegistry(array(
			'isCsrLoginEnabled' => $isCsrEnabled
		));

		$tokenRequest = $this->getModelMockBuilder('eb2ccsr/token_request')
			->disableOriginalConstructor()
			->setMethods(array('makeRequest'))
			->getMock();
		$this->replaceByMock('model', 'eb2ccsr/token_request', $tokenRequest);
		$tokenResponse = $this->getModelMockBuilder('eb2ccsr/token_response')
			->disableOriginalConstructor()
			->setMethods(array('isTokenValid'))
			->getMock();
		$this->replaceByMock('model', 'eb2ccsr/token_response', $tokenResponse);
		$helper = $this->getHelperMock('eb2ccsr/data', array('getConfigModel'));

		$helper->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue($cfg));
		if ($isCsrEnabled) {
			$tokenRequest->expects($this->once())
				->method('makeRequest')
				->will($this->returnValue($response));
		} else {
			$tokenRequest->expects($this->never())
				->method('makeRequest');
		}
		$tokenResponse->expects($this->once())
			->method('isTokenValid')
			->will($this->returnValue(false));

		$this->setExpectedException(
			'EbayEnterprise_Eb2cCustomerService_Exception_Authentication',
			'Unable to authenticate.'
		);
		$helper->validateToken($token);
	}
}
