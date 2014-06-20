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


class EbayEnterprise_Eb2cProduct_Test_Model_Feed_Import_ConfigTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * Test that EbayEnterprise_Eb2cProduct_Model_Feed_Import_Config::getImportConfigData
	 * method will be invoke by this test and will return an array of key/value pairs
	 * @test
	 */
	public function testGetImportConfigData()
	{
		$path = 'eb2cproduct/feed/import_configuration';
		$data = array(
			'xslt_deleted_sku' => 'delete-template.xsl',
			'deleted_base_xpath' => 'sku',
		);

		$configRegistryMock = $this->getModelMock('eb2ccore/config_registry', array('getConfigData'));
		$configRegistryMock->expects($this->once())
			->method('getConfigData')
			->with($this->identicalTo($path))
			->will($this->returnValue($data));

		$helperMock = $this->getHelperMock('eb2cproduct/data', array('getConfigModel'));
		$helperMock->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue($configRegistryMock));
		$this->replaceByMock('helper', 'eb2cproduct', $helperMock);

		$this->assertSame($data, Mage::getModel('eb2cproduct/feed_import_config')->getImportConfigData());
	}
}
