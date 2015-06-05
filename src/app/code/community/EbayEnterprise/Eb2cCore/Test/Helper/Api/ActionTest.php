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

class EbayEnterprise_Eb2cCore_Test_Helper_Api_ActionTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    public function setUp()
    {
        parent::setUp();

        // suppressing the real session from starting
        $session = $this->getModelMockBuilder('core/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->replaceByMock('singleton', 'core/session', $session);
    }
    /**
     * throw an exception.
     * @
     */
    public function testThrowException()
    {
        $this->setExpectedException('EbayEnterprise_Eb2cCore_Exception_Critical');
        Mage::helper('eb2ccore/api_action')->throwException();
    }
    /**
     * always return an empty string.
     */
    public function testReturnEmpty()
    {
        $this->assertSame('', Mage::helper('eb2ccore/api_action')->returnEmpty());
    }
    /**
     * return the response body
     */
    public function testReturnBody()
    {
        $response = $this->getMock('Zend_Http_Response', array('getStatus', 'isSuccessful', 'getBody'), array(200, array()));
        $response->expects($this->once())
            ->method('getBody')
            ->will($this->returnValue('the response text'));
        $this->assertSame('the response text', Mage::helper('eb2ccore/api_action')->returnBody($response));
    }
    /**
     * display a general notice in the cart when a request is configured to
     * fail loudly
     */
    public function testDisplayDefaultMessage()
    {
        $session = $this->getModelMockBuilder('core/session')
            ->disableOriginalConstructor()
            ->setMethods(array('addError'))
            ->getMock();
        $this->replaceByMock('singleton', 'core/session', $session);

        $session->expects($this->once())
            ->method('addError')
            ->with($this->identicalTo(EbayEnterprise_Eb2cCore_Helper_Api_Action::EBAY_ENTERPRISE_EB2CCORE_REQUEST_FAILED))
            ->will($this->returnSelf());
        $this->assertSame('', Mage::helper('eb2ccore/api_action')->displayDefaultMessage());
    }
}
