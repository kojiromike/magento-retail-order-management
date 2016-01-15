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

    /**
     * Scenario: handle catalog product save after for new stock data
     * Given a Varien Event Observer object containing a catalog/product object
     * When handling catalog product save after for new stock data
     * Then, if the event contains a valid product object and the product object has new stock data
     * Then, we proceed to save the new stock data for the product.
     */
    public function testHandleCatalogProductSaveAfter()
    {
        $stockData = ['product_id' => 0];
        $productId = 17;
        /** @var Mage_Catalog_Model_Product */
        $product = Mage::getModel('catalog/product', [
            'entity_id' => $productId,
            'new_product_stock_data' => $stockData,
        ]);
        /** @var EbayEnterprise_Catalog_Helper_Map_Stock */
        $stock = $this->getHelperMock('ebayenterprise_catalog/map_stock', ['saveStockItem']);
        $stock->expects($this->once())
            ->method('saveStockItem')
            ->with($this->identicalTo(['product_id' => $productId]), $this->identicalTo($productId))
            ->will($this->returnSelf());
        /** @var Varien_Event_Observer */
        $eventObserver = $this->_buildEventObserver(['data_object' => $product]);
        /** @var EbayEnterprise_Catalog_Model_Observers */
        $observer = Mage::getModel('ebayenterprise_catalog/observers', ['stock' => $stock]);
        $this->assertNull($observer->handleCatalogProductSaveAfter($eventObserver));
    }
}
