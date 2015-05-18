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

class EbayEnterprise_Inventory_Test_Model_Tax_Shipping_OriginTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /** @var EbayEnterprise_Inventory_Model_Details_Service */
    protected $detailsService;
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $logContext;

    public function setUp()
    {
        $this->logger= $this->getHelperMockBuilder('ebayenterprise_magelog/data')
            ->disableOriginalConstructor()
            ->getMock();
        $this->logContext = $this->getHelperMockBuilder('ebayenterprise_magelog/context')
            ->disableOriginalConstructor()
            ->getMock();
        $this->logContext->expects($this->any())
            ->method('getMetaData')
            ->will($this->returnValue([]));
    }


    public function testInjectShippingOriginForItem()
    {
        $detail = $this->getModelMockBuilder('ebayenterprise_inventory/details_item')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        EcomDev_Utils_Reflection::setRestrictedPropertyValues($detail, [
            'isAvailable' => true,
            'shipFromLines' => 'shipFromLines',
            'shipFromCity' => 'shipFromCity',
            'shipFromMainDivision' => 'shipFromMainDivision',
            'shipFromCountryCode' => 'shipFromCountryCode',
            'shipFromPostalCode' => 'shipFromPostalCode',
        ]);
        $item = $this->getModelMock('sales/quote_item', ['getId']);
        $item->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
        $this->detailsService = $this->getModelMockBuilder('ebayenterprise_inventory/details_service')
            ->disableOriginalConstructor()
            ->setMethods(['getDetailsForItem'])
            ->getMock();
        $this->detailsService->expects($this->any())
            ->method('getDetailsForItem')
            ->with($this->identicalTo($item))
            ->will($this->returnValue($detail));
        $address = $this->getModelMockBuilder('customer/address_abstract')
            ->disableOriginalConstructor()
            ->setMethods(['addData'])
            ->getMockForAbstractClass();
        $address->expects($this->once())
            ->method('addData')
            ->with($this->equalTo([
                'street' => 'shipFromLines',
                'city' => 'shipFromCity',
                'region_code' => 'shipFromMainDivision',
                'country_id' => 'shipFromCountryCode',
                'postcode' => 'shipFromPostalCode',
            ]))
            ->will($this->returnSelf());
        $origin = Mage::getModel(
            'ebayenterprise_inventory/tax_shipping_origin',
            ['details_service' => $this->detailsService, 'logger'=>$this->logger, 'log_context'=>$this->logContext]
        );
        $origin->injectShippingOriginForItem($item, $address);
    }
}
