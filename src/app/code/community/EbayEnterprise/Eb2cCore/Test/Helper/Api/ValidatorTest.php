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


class EbayEnterprise_Eb2cCore_Test_Helper_Api_ValidatorTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /**
     * Get an eb2ccore helper to translate the messages. Replaces the translate
     * method with a simpler callback that will simply prepend the string
     * "Translated_" to the beginning of the first argument it is passed.
     * @return Mock_EbayEnterprise_Eb2cCore_Helper_Data
     */
    protected function _getMockTranslationHelper()
    {
        $helper = $this->getHelperMock('eb2ccore/data', array('__'));
        // replace the translation method to simply prepend "Translated_"
        $helper->expects($this->once())
            ->method('__')
            ->will($this->returnCallback(
                function ($msg) {
                    return 'Translated_' . $msg;
                }
            ));
        return $helper;
    }
    /**
     * Test returning the response type for when an invalid hostname was used.
     */
    public function testReturnInvalidHostnameResponse()
    {
        // replace the helper used to translate the messages - will replace actual
        // translation with simply prepending "Translated_" to the message
        $this->replaceByMock('helper', 'eb2ccore', $this->_getMockTranslationHelper());
        $this->assertSame(
            array(
                'message' => 'Translated_EbayEnterprise_Eb2cCore_Api_Validator_Invalid_Hostname',
                'success' => false
            ),
            Mage::helper('eb2ccore/api_validator')->returnInvalidHostnameResponse()
        );
    }
    /**
     * Test returning the response message for when an unknown failure occurred.
     */
    public function testReturnUnknownErrorResponse()
    {
        // replace the helper used to translate the messages - will replace actual
        // translation with simply prepending "Translated_" to the message
        $this->replaceByMock('helper', 'eb2ccore', $this->_getMockTranslationHelper());
        $this->assertSame(
            array(
                'message' => 'Translated_EbayEnterprise_Eb2cCore_Api_Validator_Unknown_Failure',
                'success' => false
            ),
            Mage::helper('eb2ccore/api_validator')->returnUnknownErrorResponse()
        );
    }
    /**
     * Provide status codes for client errors and the message that should be
     * returned for that error.
     * @return array
     */
    public function provideStatusCodeAndResponseMessage()
    {
        return array(
            array(401, array(
                'message' => 'Translated_EbayEnterprise_Eb2cCore_Api_Validator_Invalid_Api_Key',
                'success' => false
            )),
            array(403, array(
                'message' => 'Translated_EbayEnterprise_Eb2cCore_Api_Validator_Invalid_Store_Id',
                'success' => false
            )),
            array(408, array(
                'message' => 'Translated_EbayEnterprise_Eb2cCore_Api_Validator_Network_Timeout',
                'success' => false
            )),
            array(404, array(
                'message' => 'Translated_EbayEnterprise_Eb2cCore_Api_Validator_Unknown_Failure',
                'success' => false
            )),
        );
    }
    /**
     * Test getting the response messages for various client errors.
     * @param  int $statusCode HTTP status code
     * @param  string $responseMessage Message for the error
     * @dataProvider provideStatusCodeAndResponseMessage
     */
    public function testReturnClientErrorResponse($statusCode, $responseMessage)
    {
        // replace the helper used to translate the messages - will replace actual
        // translation with simply prepending "Translated_" to the message
        $this->replaceByMock('helper', 'eb2ccore', $this->_getMockTranslationHelper());
        $response = $this->getMockBuilder('Zend_Http_Response')
            ->disableOriginalConstructor()
            ->setMethods(array('getStatus'))
            ->getMock();
        $response->expects($this->any())
            ->method('getStatus')
            ->will($this->returnValue($statusCode));
        $this->assertSame(
            $responseMessage,
            Mage::helper('eb2ccore/api_validator')->returnClientErrorResponse($response)
        );
    }
    /**
     * Test getting the success message for a successful request.
     */
    public function testReturnSuccessResponse()
    {
        // replace the helper used to translate the messages - will replace actual
        // translation with simply prepending "Translated_" to the message
        $this->replaceByMock('helper', 'eb2ccore', $this->_getMockTranslationHelper());
        $this->assertSame(
            array(
                'message' => 'Translated_EbayEnterprise_Eb2cCore_Api_Validator_Success',
                'success' => true
            ),
            Mage::helper('eb2ccore/api_validator')->returnSuccessResponse()
        );
    }
}
