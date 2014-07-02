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


class EbayEnterprise_Eb2cProduct_Test_Model_Feed_Import_ItemsTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * Build a product collection from a list of SKUs. The collection should only
	 * be expected to inlcude products that already exist in Magento. The
	 * collection should also load as little product data as possible while still
	 * allowing all of the necessary updates and saves to be performed.
	 */
	public function testBuildProductCollection()
	{
		$skus = array('12345', '4321');

		$productCollectionMock = $this->getResourceModelMockBuilder('eb2cproduct/feed_product_collection')
			->disableOriginalConstructor()
			->setMethods(array('addAttributeToSelect', 'addAttributeToFilter', 'load'))
			->getMock();

		$productCollectionMock->expects($this->any())
			->method('addAttributeToSelect')
			->with($this->equalTo(array('*')))
			->will($this->returnSelf());
		$productCollectionMock->expects($this->any())
			->method('addAttributeToFilter')
			->with($this->equalTo(array(
				array(
					'attribute' => 'sku',
					'in' => $skus,
				),
			)))
			->will($this->returnSelf());
		$productCollectionMock->expects($this->any())
			->method('load')
			->will($this->returnSelf());

		$this->replaceByMock('resource_model', 'eb2cproduct/feed_product_collection', $productCollectionMock);
		$this->assertSame($productCollectionMock, Mage::getModel('eb2cproduct/feed_import_items')->buildCollection($skus));
	}
}
