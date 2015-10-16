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

class EbayEnterprise_Inventory_Test_Model_Allocation_AllocatorTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /** @var \eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi */
    protected $api;
    /** @var EbayEnterprise_Inventory_Helper_Data */
    protected $helper;
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $coreHelper;
    /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
    protected $configModel;
    /** @var EbayEnterprise_Inventory_Model_Allocation_Allocator */
    protected $allocator;

    public function setUp()
    {
        parent::setUp();

        // Prevent log context from needing session while gather context data for logging.
        $logContext = $this->getHelperMock('ebayenterprise_magelog/context', ['getMetaData']);
        $logContext->method('getMetaData')->will($this->returnValue([]));

        $this->api = $this->getMock(
            '\eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi'
        );

        $this->configModel = $this->buildCoreConfigRegistry(['apiService' => 'inventory', 'apiOperation' => 'allocate']);

        $this->helper = $this->getHelperMock('ebayenterprise_inventory');
        $this->helper->method('getConfigModel')->will($this->returnValue($this->configModel));

        $this->coreHelper = $this->getHelperMock('eb2ccore', ['getSdkApi']);
        $this->coreHelper->method('getSdkApi')->will($this->returnValue($this->api));

        $this->allocator = Mage::getModel(
            'ebayenterprise_inventory/allocation_allocator',
            [
                'helper' => $this->helper,
                'core_helper' => $this->coreHelper,
                'log_context' => $logContext
            ]
        );
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
        $allocationFailure = 'EbayEnterprise_Inventory_Exception_Allocation_Failure_Exception';
        return [
            [$invalidPayload, $allocationFailure],
            [$networkError, $allocationFailure],
            [$unsupportedOperation, $allocationFailure],
            [$unsupportedHttpAction, $allocationFailure],
        ];
    }

    /**
     * GIVEN An <sdkApi> that will thrown an <exception> of <exceptionType> when making a request.
     * WHEN A request is made.
     * THEN The <exception> will be caught.
     * AND An exception of <expectedExceptionType> will be thrown.
     *
     * @param string
     * @param string
     * @dataProvider provideSdkExceptions
     */
    public function testSdkExceptionHandling($exceptionType, $expectedExceptionType)
    {
        $exception = new $exceptionType(__METHOD__ . ': Test Exception');

        $this->api->method('send')
            ->will($this->throwException($exception));

        $this->setExpectedException($expectedExceptionType);

        EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $this->allocator,
            'makeRequest',
            [$this->api]
        );
    }
}
