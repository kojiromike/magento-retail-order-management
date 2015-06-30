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

use \eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderItem;

/**
 * apply estimated shipping data to the order create request
 */
class EbayEnterprise_Inventory_Test_Model_Order_Create_Item_AllocationTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    // the quantity of items allocated is insufficient to complete the order.
    const INSUFFICIENT_STOCK_MESSAGE
        = 'EbayEnterprise_Inventory_Quote_Insufficient_Stock_Message';
    // the item is completely out of stock.
    const OUT_OF_STOCK_MESSAGE
        = 'EbayEnterprise_Inventory_Quote_Out_Of_Stock_Message';

    /** @var EbayEnterprise_Inventory_Helper_Data */
    protected $invHelper;
    /** @var EbayEnterprise_Inventory_Model_Allocation_Service */
    protected $allocationService;
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $logContext;

    public function setUp()
    {
        parent::setUp();
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

    public function testInjectReservationInfo()
    {
        $reservation = Mage::getModel('ebayenterprise_inventory/allocation_reservation');
        $allocation = Mage::getModel(
            'ebayenterprise_inventory/allocation',
            [
                'sku' => 'thesku',
                'item_id' => 1,
                'quantity_allocated' => 1,
                'reservation' => $reservation
            ]
        );
        $allocationService = $this->getModelMock(
            'ebayenterprise_inventory/allocation_service',
            ['getItemAllocationInformation', 'undoAllocation']
        );
        $allocationService->expects($this->once())
            ->method('getItemAllocationInformation')
            ->with($this->isInstanceOf('Mage_Sales_Model_Order_Item'))
            ->will($this->returnValue($allocation));
        $orderItem = Mage::getModel('sales/order_item', ['qty_ordered' => 1]);
        $itemPayload = $this->getMockBuilder('\eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderItem')
            ->disableOriginalConstructor()
            ->setMethods(['setReservationId'])
            ->getMockForAbstractClass();
        $itemPayload->expects($this->once())
            ->method('setReservationId')
            ->with($this->identicalTo($reservation->getId()))
            ->will($this->returnSelf());
        $allocationInjector = Mage::getModel(
            'ebayenterprise_inventory/order_create_item_allocation',
            [
                'allocation_service' => $allocationService,
                'logger' => $this->logger,
                'log_context' => $this->logContext,
            ]
        );
        $allocationInjector->injectReservationInfo($itemPayload, $orderItem);
    }

    public function provideAmountAllocated()
    {
        return [
            [0, 'EbayEnterprise_Inventory_Quote_Out_Of_Stock_Message'],
            [1, 'EbayEnterprise_Inventory_Quote_Insufficient_Stock_Message'],
        ];
    }

    /**
     * verify an exeption is thrown with the correct message
     *
     * @param int
     * @param string
     * @expectedException EbayEnterprise_Inventory_Exception_Allocation_Availability_Exception
     * @dataProvider provideAmountAllocated
     */
    public function testInjectReservationInfoException($amountAllocated, $message)
    {
        $reservation = Mage::getModel('ebayenterprise_inventory/allocation_reservation');
        $allocation = Mage::getModel(
            'ebayenterprise_inventory/allocation',
            [
                'sku' => 'thesku',
                'item_id' => 1,
                'quantity_allocated' => $amountAllocated,
                'reservation' => $reservation
            ]
        );
        $allocationService = $this->getModelMock(
            'ebayenterprise_inventory/allocation_service',
            ['getItemAllocationInformation', 'undoAllocation']
        );
        $orderItem = Mage::getModel('sales/order_item', ['qty_ordered' => 2]);
        $invHelper = $this->getHelperMock('ebayenterprise_inventory/data', ['__']);

        $allocationService->expects($this->once())
            ->method('getItemAllocationInformation')
            ->with($this->isInstanceOf('Mage_Sales_Model_Order_Item'))
            ->will($this->returnValue($allocation));
        $itemPayload = $this->getMockBuilder('\eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderItem')
            ->disableOriginalConstructor()
            ->setMethods(['setReservationId'])
            ->getMockForAbstractClass();
        $itemPayload->expects($this->never())
            ->method('setReservationId');
        $invHelper->expects($this->once())
            ->method('__')
            ->with($this->identicalTo($message))
            ->will($this->returnArgument(0));

        $allocationInjector = Mage::getModel(
            'ebayenterprise_inventory/order_create_item_allocation',
            [
                'helper' => $invHelper,
                'allocation_service' => $allocationService,
                'logger' => $this->logger,
                'log_context' => $this->logContext,
            ]
        );
        $allocationInjector->injectReservationInfo($itemPayload, $orderItem);
    }
}
