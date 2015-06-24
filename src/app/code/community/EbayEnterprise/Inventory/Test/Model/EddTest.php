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

class EbayEnterprise_Inventory_Test_Model_EddTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /**
     * Test that the model method ebayenterprise_inventory/edd::getEddMessage()
     * when invoked, will return a string content about estimated delivery date for an item in the quote.
     */
    public function testGetEddMessage()
    {
        /** @var string */
        $eddTemplate = 'You can expect to receive your item%s between %s and %s.';
        /** @var string */
        $stringDateFrom = '06/11/15';
        /** @var string */
        $stringDateTo = '06/15/15';
        /** @var string */
        $singularOrPlural = 's';
        /** @var string */
        $eddMessage = "You can expect to receive your item{$singularOrPlural} between $stringDateFrom and $stringDateTo.";
        /** @var DateTime */
        $from = new DateTime('2015-06-11 17:15:43');
        /** @var DateTime */
        $to = new DateTime('2015-06-15 10:15:43');
        /** @var int */
        $qty = 2;
        /** @var Mage_Sales_Model_Quote_Item */
        $item = Mage::getModel('sales/quote_item', ['qty' => $qty]);

        /** @var EbayEnterprise_Inventory_Model_Details_Item */
        $detailItem = $this->getModelMockBuilder('ebayenterprise_inventory/details_item')
            ->disableOriginalConstructor()
            ->setMethods(['getDeliveryWindowFromDate', 'getDeliveryWindowToDate'])
            ->getMock();
        $detailItem->expects($this->once())
            ->method('getDeliveryWindowFromDate')
            ->will($this->returnValue($from));
         $detailItem->expects($this->once())
            ->method('getDeliveryWindowToDate')
            ->will($this->returnValue($to));

        /** @var EbayEnterprise_Inventory_Model_Details_Service */
        $detailService = $this->getModelMockBuilder('ebayenterprise_inventory/details_service')
            ->disableOriginalConstructor()
            ->setMethods(['getDetailsForItem'])
            ->getMock();
        $detailService->expects($this->once())
            ->method('getDetailsForItem')
            ->with($this->identicalTo($item))
            ->will($this->returnValue($detailItem));

        /** @var EbayEnterprise_Inventory_Helper_Data */
        $inventoryHelper = $this->getHelperMock('ebayenterprise_inventory/data', ['__']);
        $inventoryHelper->expects($this->once())
            ->method('__')
            ->with(
                $this->identicalTo($eddTemplate),
                $this->identicalTo($singularOrPlural),
                $this->identicalTo($stringDateFrom),
                $this->identicalTo($stringDateTo)
            )
            ->will($this->returnValue($eddMessage));

        /** @var EbayEnterprise_Inventory_Model_Edd */
        $edd = Mage::getModel('ebayenterprise_inventory/edd', [
            'detail_service' => $detailService,
            'inventory_helper' => $inventoryHelper,
            'inventory_config' => $this->buildCoreConfigRegistry(['estimatedDeliveryTemplate' => $eddTemplate]),
        ]);

        $this->assertSame($eddMessage, $edd->getEddMessage($item));
    }
}
