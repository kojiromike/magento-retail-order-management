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


class EbayEnterprise_Eb2cGiftwrap_Test_Model_Feed_Import_ItemsTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /**
     * Test that when invoked, the method EbayEnterprise_Eb2cGiftwrap_Model_Feed_Import_Items::buildCollection
     * will return a collection of giftwrap object base on the passed in array of skus.
     */
    public function testBuildGiftwrapCollection()
    {
        $skus = array('12345', '4321');

        $giftwrapCollectionMock = $this->getResourceModelMockBuilder('eb2cgiftwrap/wrapping_collection')
            ->disableOriginalConstructor()
            ->setMethods(array('addFieldToSelect', 'addFieldToFilter', 'load'))
            ->getMock();

        $giftwrapCollectionMock->expects($this->any())
            ->method('addFieldToSelect')
            ->with($this->equalTo(array('*')))
            ->will($this->returnSelf());
        $giftwrapCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->with($this->identicalTo('eb2c_sku'), $this->identicalTo(array('in' => $skus)))
            ->will($this->returnSelf());
        $giftwrapCollectionMock->expects($this->any())
            ->method('load')
            ->will($this->returnSelf());
        $this->replaceByMock('resource_model', 'eb2cgiftwrap/wrapping_collection', $giftwrapCollectionMock);

        $this->assertSame($giftwrapCollectionMock, Mage::getModel('eb2cgiftwrap/feed_import_items')->buildCollection($skus));

    }
    /**
     * Test that when invoked, the method EbayEnterprise_Eb2cGiftwrap_Model_Feed_Import_Items::createNewItem
     * will return a new instantance of Mage_Catalog_Model_Giftwrap with dummny data.
     */
    public function testCreateNewGiftwrap()
    {
        $sku = '14-83774';
        $wrap = Mage::getModel('enterprise_giftwrapping/wrapping', array('eb2c_sku' => $sku));
        $helper = $this->getHelperMock('eb2cgiftwrap/data', array('createNewGiftwrapping'));
        $helper->expects($this->any())
            ->method('createNewGiftwrapping')
            ->with($this->equalTo($sku), $this->identicalTo(array()))
            ->will($this->returnValue($wrap));
        $this->replaceByMock('helper', 'eb2cgiftwrap', $helper);

        $this->assertSame($wrap, Mage::getModel('eb2cgiftwrap/feed_import_items')->createNewItem($sku));
    }
}
