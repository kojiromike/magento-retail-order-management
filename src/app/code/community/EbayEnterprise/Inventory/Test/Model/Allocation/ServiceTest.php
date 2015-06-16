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

class EbayEnterprise_Inventory_Test_Model_Allocation_ServiceTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $logContext;
    /** @var EbayEnterprise_Inventory_Model_Session */
    protected $inventorySession;
    /** @var EbayEnterprise_Inventory_Helper_Data */
    protected $invHelper;


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

            $this->request = $this->getMockForAbstractClass(
                '\eBayEnterprise\RetailOrderManagement\Payload\Inventory\IAllocationRollbackRequest'
            );
            $this->reply = $this->getMockForAbstractClass(
                '\eBayEnterprise\RetailOrderManagement\Payload\Inventory\IAllocationRollbackReply'
            );
            $this->httpApi = $this->getMockBuilder('\eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi')
                ->disableOriginalConstructor()
                ->setMethods(['send', 'getRequestBody', 'getResponseBody', 'setRequestBody'])
                ->getMock();
            $this->httpApi->expects($this->any())
                ->method('setRequestBody')
                ->with($this->isInstanceOf(
                    '\eBayEnterprise\RetailOrderManagement\Payload\Inventory\IAllocationRollbackRequest'
                ))
                ->will($this->returnSelf());
            $this->httpApi->expects($this->any())
                ->method('getRequestBody')
                ->will($this->returnValue($this->request));
            $this->httpApi->expects($this->any())
                ->method('getResponseBody')
                ->will($this->returnValue($this->reply));
    }

    public function testUndoAllocation()
    {
        $this->inventorySession = $this->getModelMockBuilder('ebayenterprise_inventory/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $svc = $this->getModelMockBuilder('ebayenterprise_inventory/allocation_service')
            ->setMethods(['getInventorySession', 'createDeallocator'])
            ->setConstructorArgs([[
                'logger' => $this->logger,
                'log_context' => $this->logContext
            ]])
            ->getMock();
        $reservation = Mage::getModel('ebayenterprise_inventory/allocation_reservation');
        $result = Mage::getModel('ebayenterprise_inventory/allocation_result', ['reservation' => $reservation]);

        $coreHelper = $this->getHelperMockBuilder('eb2ccore/data')
            ->disableOriginalConstructor()
            ->setMethods(['getSdkApi'])
            ->getMock();

        $coreHelper->expects($this->once())
            ->method('getSdkApi')
            ->with(
                $this->identicalTo('inventory'),
                $this->identicalTo('allocations/delete')
            )
            ->will($this->returnValue($this->httpApi));

        $this->request->expects($this->once())
            ->method('setReservationId')
            ->with($reservation->getId())
            ->will($this->returnSelf());

        $deallocator = Mage::getModel('ebayenterprise_inventory/allocation_deallocator',
            ['logger' => $this->logger, 'log_context' => $this->logContext, 'core_helper' => $coreHelper]);

        $this->inventorySession->setAllocationResult($result);
        $this->assertSame($result, $this->inventorySession->getAllocationResult());
        $svc->expects($this->atLeastOnce())
            ->method('getInventorySession')
            ->will($this->returnValue($this->inventorySession));
        $svc->expects($this->atLeastOnce())
            ->method('createDeallocator')
            ->will($this->returnValue($deallocator));
        $svc->undoAllocation();
    }
}
