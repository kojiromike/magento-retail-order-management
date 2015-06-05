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


class EbayEnterprise_Catalog_Test_Model_Pim_ProductTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /**
     * verify loads a product and calls the factory for each pim attribute
     */
    public function testLoadPimAttributesByProduct()
    {
        $config = array('the config');
        $attributes = array('_gsi_client_id','sku');

        $result = array();
        for ($i=0; $i < 3; $i++) {
            $result[] = $this->getModelMockBuilder('ebayenterprise_catalog/pim_attribute')
                ->disableOriginalConstructor()
                ->getMock();
        }

        $doc = Mage::helper('eb2ccore')->getNewDomDocument();

        $product = $this->getModelMockBuilder('catalog/product')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $factory = $this->getModelMockBuilder('ebayenterprise_catalog/pim_attribute_factory')
            ->disableOriginalConstructor()
            ->setMethods(array('getPimAttribute'))
            ->getMock();
        $factory->expects($this->exactly(2))
            ->method('getPimAttribute')
            ->will($this->returnValueMap(array(
                array($attributes[0], $product, $doc, $config, $result[1]),
                array($attributes[1], $product, $doc, $config, $result[2])
            )));
        $this->replaceByMock('singleton', 'ebayenterprise_catalog/pim_attribute_factory', $factory);

        $pimProduct = $this->getModelMockBuilder('ebayenterprise_catalog/pim_product')
            ->disableOriginalConstructor()
            ->setMethods(array('setPimAttributes', 'getPimAttributes'))
            ->getMock();
        $pimProduct->expects($this->once())
            ->method('setPimAttributes')
            ->with($this->identicalTo($result))
            ->will($this->returnSelf());
        $pimProduct->expects($this->once())
            ->method('getPimAttributes')
            ->will($this->returnValue(array($result[0])));

        $this->assertSame($pimProduct, $pimProduct->loadPimAttributesByProduct($product, $doc, $config, $attributes));
    }
    /**
     * verify an exception is thrown when missing arguments
     */
    public function testConstructInvalidArguments()
    {
        $expectedException = sprintf(
            EbayEnterprise_Catalog_Model_Pim_Product::ERROR_INVALID_ARGS,
            'EbayEnterprise_Catalog_Model_Pim_Product::_construct',
            'client_id, catalog_id, sku'
        );
        $initParams = array();
        $this->setExpectedException('Exception', $expectedException);

        $helper = $this->getHelperMockBuilder('eb2ccore/data')
            ->disableOriginalConstructor()
            ->setMethods(array('triggerError'))
            ->getMock();
        $helper->expects($this->once())
            ->method('triggerError')
            ->with($this->identicalTo($expectedException))
            ->will($this->throwException(new Exception($expectedException)));
        $this->replaceByMock('helper', 'eb2ccore', $helper);

        $product = $this->getModelMockBuilder('ebayenterprise_catalog/pim_product')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        EcomDev_Utils_Reflection::invokeRestrictedMethod($product, '_construct', array($initParams));
    }
    public function testConstructor()
    {
        $constructorArgs = array(
            'client_id' => 'ClientId',
            'catalog_id' => 'CatalogId',
            'sku' => '45-12345',
            'pim_attributes' => array(1,2,3),
        );
        $pimProduct = Mage::getModel('ebayenterprise_catalog/pim_product', $constructorArgs);
        $this->assertSame('ClientId', $pimProduct->getClientId());
        $this->assertSame('CatalogId', $pimProduct->getCatalogId());
        $this->assertSame('45-12345', $pimProduct->getSku());
        // pim_attributes should always be set to an empty array when constructing
        // a new PIM Product model.
        $this->assertSame(array(), $pimProduct->getPimAttributes());
    }
}
