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

use eBayEnterprise\RetailOrderManagement\Payload\Order\IEstimatedDeliveryDate;

class EbayEnterprise_Inventory_Test_Model_Order_Create_ItemTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /** @var EbayEnterprise_Inventory_Model_Details_Service */
    protected $detailService;
    protected $helperStub;
    protected $payload;
    protected $item;

    public function setUp()
    {
        parent::setUp();
        $this->payload = $this->mockPayload();
        $detail = $this->getModelMockBuilder('ebayenterprise_inventory/details_item')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        EcomDev_Utils_Reflection::setRestrictedPropertyValues($detail, [
        'isAvailable' => true,
        'deliveryWindowFromDate' => new DateTime(),
        'deliveryWindowToDate' => null,
        'shippingWindowFromDate' => null,
        'shippingWindowToDate' => null,
        ]);
        $config = $this->buildCoreConfigRegistry([
            'estimatedDeliveryTemplate' => 'eddtemplate'
        ]);
        $this->helperStub = $this->getHelperMockBuilder('ebayenterprise_inventory/data')
            ->disableOriginalConstructor()
            ->setMethods(['getConfigModel'])
            ->getMock();
        $this->helperStub->expects($this->any())
            ->method('getConfigModel')
            ->will($this->returnValue($config));
        $this->item = Mage::getModel('sales/quote_item');
        $this->detailService = $this->getModelMockBuilder('ebayenterprise_inventory/details_service')
            ->disableOriginalConstructor()
            ->setMethods(['getDetailsForItem'])
            ->getMock();
        $this->detailService->expects($this->once())
            ->method('getDetailsForItem')
            ->with($this->identicalTo($this->item))
            ->will($this->returnValue($detail));
        $this->order = Mage::getModel('sales/order');
    }

    protected function mockPayload()
    {
        $payload = $this->getMockBuilder('\eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderItem')
            ->getMockForAbstractClass();
        $payload->expects($this->once())
            ->method('setEstimatedDeliveryMode')
            ->with($this->identicalTo(IEstimatedDeliveryDate::MODE_LEGACY))
            ->will($this->returnSelf());
        $payload->expects($this->once())
            ->method('setEstimatedDeliveryMessageType')
            ->with($this->identicalTo(IEstimatedDeliveryDate::MESSAGE_TYPE_DELIVERYDATE))
            ->will($this->returnSelf());
        $payload->expects($this->once())
            ->method('setEstimatedDeliveryTemplate')
            ->with($this->identicalTo('eddtemplate'))
            ->will($this->returnSelf());
        $payload->expects($this->once())
            ->method('setReservationId')
            ->with($this->identicalTo('reservationid'))
            ->will($this->returnSelf());
        $payload->expects($this->once())
            ->method('setEstimatedDeliveryWindowFrom')
            ->with($this->isInstanceOf('DateTime'))
            ->will($this->returnSelf());
        $payload->expects($this->never())
            ->method('setEstimatedDeliveryWindowTo');
        $payload->expects($this->never())
            ->method('setEstimatedShippingWindowFrom');
        $payload->expects($this->never())
            ->method('setEstimatedShippingWindowTo');
        return $payload;
    }

    /**
     * verify
     * - estimated delivery date information is set on the payload
     * - invalid datestrings won't be used to set data on the payload
     * - the mode and message are each hardwired to a specific value
     * - the template is read from the config
     */
    public function testInjectShippingEstimates()
    {
        $handler = $this->getModelMockBuilder('ebayenterprise_inventory/order_create_item')
            ->setMethods(['getQuoteItem'])
            ->setConstructorArgs(
                [['helper' => $this->helperStub, 'detail_service' => $this->detailService]]
            )
            ->getMock();
        $handler->expects($this->once())
            ->method('getQuoteItem')
            ->with($this->isInstanceOf('Mage_Sales_Model_Order_Item'))
            ->will($this->returnValue($this->item));
        $orderItem = Mage::getModel(
            'sales/order_item',
            ['quote_item_id' => 1, 'eb2c_reservation_id' => 'reservationid']
        );
        $handler->injectShippingEstimates($this->payload, $orderItem);
    }
}
