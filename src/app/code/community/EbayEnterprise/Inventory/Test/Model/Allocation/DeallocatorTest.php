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

class EbayEnterprise_Inventory_Test_Model_Allocation_DeallocatorTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /** @var \eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi */
    protected $api;
    /** @var EbayEnterprise_Inventory_Helper_Data */
    protected $helper;
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $coreHelper;
    /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
    protected $configModel;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $logContext;

    public function setUp()
    {
        parent::setUp();

        // Prevent log context from needing session while gather context data for logging.
        $this->logContext = $this->getHelperMock('ebayenterprise_magelog/context', ['getMetaData']);
        $this->logContext->method('getMetaData')->will($this->returnValue([]));

        $this->api = $this->getMock(
            '\eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi'
        );

        $this->configModel = $this->buildCoreConfigRegistry(['apiService' => 'inventory', 'apiOperation' => 'allocate']);

        $this->helper = $this->getHelperMock('ebayenterprise_inventory');
        $this->helper->method('getConfigModel')->will($this->returnValue($this->configModel));

        $this->coreHelper = $this->getHelperMock('eb2ccore', ['getSdkApi']);
        $this->coreHelper->method('getSdkApi')->will($this->returnValue($this->api));
    }

    /**
     * Provide exceptions that can be thrown from the SDK and the exception
     * expected to be thrown after handling the SDK exception.
     *
     * @return array
     */
    public function provideSdkExceptions()
    {
        $invalidPayload = '\eBayEnterprise\RetailOrderManagement\Payload\Exception\InvalidPayload';
        $networkError = '\eBayEnterprise\RetailOrderManagement\Api\Exception\NetworkError';
        $unsupportedOperation = '\eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedOperation';
        $unsupportedHttpAction = '\eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedHttpAction';
        return [
            [$invalidPayload],
            [$networkError],
            [$unsupportedOperation],
            [$unsupportedHttpAction],
        ];
    }

    /**
     * GIVEN An <sdkApi> that will thrown an <exception> of <exceptionType> when making a request.
     * WHEN A request is made.
     * THEN The <exception> will be caught.
     *
     * @param string
     * @dataProvider provideSdkExceptions
     */
    public function testSdkExceptionHandling($exceptionType)
    {
        $exception = new $exceptionType(__METHOD__ . ': Test Exception');

        // Assert that the send method is called and that it will throw
        // an exception. This is the closest thing to a meaningful assertion
        // this test can make.
        $this->api->expects($this->once())
            ->method('send')
            ->will($this->throwException($exception));

        $reservation = Mage::getModel('ebayenterprise_inventory/allocation_reservation');

        // Create deallocator mock to be tested. Mocked methods allow only
        // exception handling to be tested without depending upon methods needed
        // to create the API instance or fill out the payloads.
        $deallocator = $this->getModelMock(
            'ebayenterprise_inventory/allocation_deallocator',
            ['prepareApi', 'prepareRequest'],
            false,
            [['log_context' => $this->logContext]]
        );
        $deallocator->method('prepareApi')->will($this->returnValue($this->api));
        $deallocator->method('prepareRequest')->will($this->returnSelf());

        // Nothing obvious to assert other than the side-effect of the send
        // method being called. All exceptions should be caught and suppressed.
        // The method doesn't return anything, just triggers side-effects.
        // Trigger the method and just make sure the send method gets called
        // and no exceptions arise.
        $deallocator->rollback($reservation);
    }
}
