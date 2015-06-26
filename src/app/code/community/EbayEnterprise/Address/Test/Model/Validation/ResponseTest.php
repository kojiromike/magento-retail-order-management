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

use eBayEnterprise\RetailOrderManagement\Payload\Address\IValidationReply;

class EbayEnterprise_Address_Test_Model_Validation_ResponseTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $_logger;
    /** @var EbayEnterprise_Address_Helper_Data */
    protected $_helper;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $_context;

    public function setUp()
    {
        $this->_logger = $this->getHelperMock('ebayenterprise_magelog/data', ['warning']);
        $this->_context = $this->getHelperMock('ebayenterprise_magelog/context', ['getMetaData']);
        $this->_context->expects($this->any())
            ->method('getMetaData')
            ->will($this->returnValue([]));
    }

    /**
     * provide normal, expected result codes
     * @return array
     */
    public function provideNormalResultCodes()
    {
        return [
            [IValidationReply::RESULT_VALID],
            [IValidationReply::RESULT_CORRECTED_WITH_SUGGESTIONS],
            [IValidationReply::RESULT_FAILED],
            [IValidationReply::RESULT_NOT_SUPPORTED],
        ];
    }

    /**
     * will not log a warning when the result code is
     * a code returned under normal circumstances
     * @dataProvider provideNormalResultCodes
     */
    public function testLogResultCode($code)
    {
        $response = $this->getModelMockBuilder('ebayenterprise_address/validation_response')
            ->setConstructorArgs([[
                'api' => $this->getMock('\eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi'),
                'logger' => $this->_logger,
                'context' => $this->_context,
            ]])
            ->setMethods(['_extractResponseData'])
            ->getMock();
        $response->setResultCode($code);
        $this->_logger->expects($this->never())
            ->method('warning');
        EcomDev_Utils_Reflection::invokeRestrictedMethod($response, '_logResultCode');
    }

    /**
     * provide error/unknown result codes
     * @return array
     */
    public function provideFailureResultCodes()
    {
        return [
            [IValidationReply::RESULT_UNABLE_TO_CONTACT_PROVIDER],
            [IValidationReply::RESULT_TIMEOUT],
            [IValidationReply::RESULT_PROVIDER_ERROR],
            [IValidationReply::RESULT_MALFORMED],
            ['AN_UNKNOWN_CODE_SHOULD_SHOULD_GET_LOGGED'],
        ];
    }

    /**
     * will log a warning when the result code is
     * for an error or is unknown
     * @dataProvider provideFailureResultCodes
     */
    public function testLogResultCodeWithError($code)
    {
        $response = $this->getModelMockBuilder('ebayenterprise_address/validation_response')
            ->setConstructorArgs([[
                'api' => $this->getMock('\eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi'),
                'logger' => $this->_logger,
                'context' => $this->_context,
            ]])
            ->setMethods(['_extractResponseData'])
            ->getMock();
        $response->setResultCode($code);
        $this->_logger->expects($this->once())
            ->method('warning');
        EcomDev_Utils_Reflection::invokeRestrictedMethod($response, '_logResultCode');
    }
}
