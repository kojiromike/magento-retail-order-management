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


class EbayEnterprise_Eb2cCore_Test_Helper_ValidatorTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * Test doign simple validations on API settings. When all are valid, should
	 * simply return self
	 */
	public function testValidateSettings()
	{
		$helper = Mage::helper('eb2ccore/validator');
		$this->assertSame(
			$helper,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$helper,
				'_validateApiSettings',
				array('STORE_ID', 'API_KEY', 'example.com')
			)
		);
	}
	public function provideSettingsAndExceptions()
	{
		return array(
			array('', '', '', 'Store Id, API Key, API Hostname'),
			array('', '', 'example.com', 'Store Id, API Key'),
			array('', 'apikey-123', 'example.com', 'Store Id'),
		);
	}
	/**
	 * Test doing simple validations on the settings - basically ensure that none
	 * are empty. If any are, an exception should be thrown which includes the
	 * settings that are invalid.
	 * @param  string $storeId
	 * @param  string $apiKey
	 * @param  string $hostname
	 * @param  string $exceptionMessage
	 * @dataProvider provideSettingsAndExceptions
	 */
	public function testValidateInvalidSettings($storeId, $apiKey, $hostname, $exceptionMessage)
	{
		$this->setExpectedException(
			'EbayEnterprise_Eb2cCore_Exception_Api_Configuration',
			$exceptionMessage
		);
		$translationHelper = $this->getHelperMock('eb2ccore/data', array('__'));
		$translationHelper->expects($this->once())
			->method('__')
			->will($this->returnArgument(1));
		$this->replaceByMock('helper', 'eb2ccore', $translationHelper);
		$helper = Mage::helper('eb2ccore/validator');
		EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$helper,
			'_validateApiSettings',
			array($storeId, $apiKey, $hostname)
		);
	}
	/**
	 * Test validating SFTP settings when settings are all potentially valid - not
	 * empty or easily detectable errors.
	 */
	public function testValidateSftpSettings()
	{
		$helper = Mage::helper('eb2ccore/validator');
		$this->assertSame(
			$helper,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$helper,
				'_validateSftpSettings',
				array('host.example.com', 'test_user', '--- Private Key ---', '22')
			)
		);
	}
	/**
	 * Provide invalid SFTP configurations - missing/empty values or easily
	 * detectable errors.
	 * @return array
	 */
	public function provideSftpSettingsAndExceptions()
	{
		return array(
			array('', '', '', '', 'Remote Host, SFTP User Name, Private Key, Remote Port'),
			array('', '', '', '0', 'Remote Host, SFTP User Name, Private Key, Remote Port'),
			array('', '', '', '22', 'Remote Host, SFTP User Name, Private Key'),
			array('', '', '---- PRIVATE KEY ----', '22', 'Remote Host, SFTP User Name'),
			array('', 'test_user', '---- PRIVATE KEY ----', '22', 'Remote Host'),
		);
	}
	/**
	 * Test doing simple validations on the settings - basically ensure that none
	 * are empty or obviously wrong. If any are, an exception should be thrown
	 * which includes the settings that are invalid.
	 * @param string $host
	 * @param string $username
	 * @param string $privateKey
	 * @param string $port
	 * @param string $exceptionMessage
	 * @dataProvider provideSftpSettingsAndExceptions
	 */
	public function testValidateInvalidSftpSettings($host, $username, $privateKey, $port, $exceptionMessage)
	{
		$this->setExpectedException(
			'EbayEnterprise_Eb2cCore_Exception_Sftp_Configuration',
			$exceptionMessage
		);
		$translationHelper = $this->getHelperMock('eb2ccore/data', array('__'));
		$translationHelper->expects($this->once())
			->method('__')
			->will($this->returnArgument(1));
		$this->replaceByMock('helper', 'eb2ccore', $translationHelper);
		$helper = Mage::helper('eb2ccore/validator');
		EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$helper,
			'_validateSftpSettings',
			array($host, $username, $privateKey, $port)
		);
	}
}
