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

class EbayEnterprise_Order_Test_Block_Order_CancelTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /**
     * Create a block instance
     *
     * @param string
     * @return Mage_Core_Block_Abstract
     */
    protected function _createBlock($class)
    {
        return Mage::app()->getLayout()->createBlock($class);
    }

    /**
     * Test that the block method ebayenterprise_order/order_cancel::_prepareLayout()
     * is invoked, and it will call the method ebayenterprise_order/order_cancel::getLayout()
     * which will return an instance of type Mage_Core_Model_Layout. Then the model method
     * core/layout::getBlock() passing in the string literal 'head'. It will return
     * an instance that extends the abstract block class Mage_Core_Block_Abstract.
     * Then the block varien magic method core/abstract::setTitle() will be called and passed
     * the return value from calling the block method ebayenterprise_order/order_cancel::__()
     * passing a string literal as the first parameter and the return value from calling
     * the method sales/order::getRealOrderId(). Finally, the method
     * ebayenterprise_order/order_cancel::_prepareLayout() will return null.
     */
    public function testPrepareLayout()
    {
        /** @var string */
        $head = 'head';
        /** @var string */
        $orderId = '100000000093381';
        /** @var string */
        $partialTitle = 'Order # %s';
        /** @var string */
        $fullTitle = sprintf($partialTitle, $orderId);
        /** @var Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order', ['real_order_id' => $orderId]);

        /** @var Mage_Core_Block_Abstract */
        $block = $this->getMockForAbstractClass('Mage_Core_Block_Abstract', [], '', true, true, true, ['setTitle']);
        $block->expects($this->once())
            ->method('setTitle')
            ->with($this->identicalTo($fullTitle))
            ->will($this->returnSelf());

        /** @var Mage_Core_Model_Layout */
        $layout = $this->getModelMock('core/layout', ['getBlock']);
        $layout->expects($this->once())
            ->method('getBlock')
            ->with($this->identicalTo($head))
            ->will($this->returnValue($block));

        /** @var Mock_EbayEnterprise_Order_Block_Order_Cancel */
        $orderCancel = $this->getBlockMock('ebayenterprise_order/order_cancel', ['getLayout', 'getOrder', '__']);
        $orderCancel->expects($this->once())
            ->method('getLayout')
            ->will($this->returnValue($layout));
        $orderCancel->expects($this->once())
            ->method('getOrder')
            ->will($this->returnValue($order));
        $orderCancel->expects($this->once())
            ->method('__')
            ->with($this->identicalTo($partialTitle), $this->identicalTo($orderId))
            ->will($this->returnValue($fullTitle));

        $this->assertNull(EcomDev_Utils_Reflection::invokeRestrictedMethod($orderCancel, '_prepareLayout', []));
    }

    /**
     * Test that the block method ebayenterprise_order/order_cancel::getTextMessage()
     * is invoked, and it will call the helper method ebayenterprise_order/data::__()
     * passing as its parameter the value in the class constant
     * ebayenterprise_order/order_cancel::TEXT_MESSAGE. The helper method
     * ebayenterprise_order/data::__() will return a translated string. Then, finally
     * the method ebayenterprise_order/order_cancel::getTextMessage() will return this
     * translated text.
     */
    public function testGetTextMessage()
    {
        /** @var string */
        $translatedText = EbayEnterprise_Order_Block_Order_Cancel::TEXT_MESSAGE;
        /** @var EbayEnterprise_Order_Helper_Data */
        $orderHelper = $this->getHelperMock('ebayenterprise_order/data', ['__']);
        $orderHelper->expects($this->once())
            ->method('__')
            ->with($this->identicalTo(EbayEnterprise_Order_Block_Order_Cancel::TEXT_MESSAGE))
            ->will($this->returnValue($translatedText));

        /** @var Mock_EbayEnterprise_Order_Block_Order_Cancel */
        $orderCancel = $this->_createBlock('ebayenterprise_order/order_cancel');

        // Replacing the protected class property ebayenterprise_order/order_cancel::$_orderHelper with a mock.
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($orderCancel, '_orderHelper', $orderHelper);

        $this->assertSame($translatedText, $orderCancel->getTextMessage());
    }

    /**
     * Test that the block method ebayenterprise_order/order_cancel::getCancelOrderButton()
     * is invoked, and it will call the helper method ebayenterprise_order/data::__()
     * passing as its parameter the value in the class constant
     * ebayenterprise_order/order_cancel::CANCEL_ORDER_BUTTON. The helper method
     * ebayenterprise_order/data::__() will return a translated string. Then, finally
     * the method ebayenterprise_order/order_cancel::getCancelOrderButton() will return this
     * translated text.
     */
    public function testGetCancelOrderButton()
    {
        /** @var string */
        $translatedText = EbayEnterprise_Order_Block_Order_Cancel::CANCEL_ORDER_BUTTON;
        /** @var EbayEnterprise_Order_Helper_Data */
        $orderHelper = $this->getHelperMock('ebayenterprise_order/data', ['__']);
        $orderHelper->expects($this->once())
            ->method('__')
            ->with($this->identicalTo(EbayEnterprise_Order_Block_Order_Cancel::CANCEL_ORDER_BUTTON))
            ->will($this->returnValue($translatedText));

        /** @var Mock_EbayEnterprise_Order_Block_Order_Cancel */
        $orderCancel = $this->_createBlock('ebayenterprise_order/order_cancel');

        // Replacing the protected class property ebayenterprise_order/order_cancel::$_orderHelper with a mock.
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($orderCancel, '_orderHelper', $orderHelper);

        $this->assertSame($translatedText, $orderCancel->getCancelOrderButton());
    }

    /**
     * Test that the block method ebayenterprise_order/order_cancel::getCancelReasonHtmlSelect()
     * is invoked, and it will call the block method ebayenterprise_order/order_cancel::getLayout()
     * which will return instance of type core/layout. Then, the method core/layout::createBlock()
     * will be invoked and passed in the string literal 'core/html_select' and it will return instance
     * that extend the block abstract class core/abstract. Then, the varien magic method core/abstract::setName()
     * will be called and passed in the parameter name. Then, the varien magic method core/abstract::setId() will be called
     * and passed in the parameter id. Then, the varien magic method core/abstract::setTitle() will be called and passed
     * in the return value from calling the helper method ebayenterprise_order/data::__() and passed in the parameter title.
     * Then, the varien magic method core/abstract::setClass() will be called and passed in the string literal 'validate-select'.
     * Then, the varien magic method core/abstract::setValue() will be called and passed in the parameter defValue.
     * Then, the varien magic method core/abstract::setOptions() will be called and passed in the return value from calling
     * the helper method ebayenterprise_order/data::getCancelReasonOptionArray() which will return an array
     * with key label and value. And then, the method core/abstract::getHtml() will called and return a string
     * representing HTML select options. Finally, the method ebayenterprise_order/order_cancel::getCancelReasonHtmlSelect()
     * will return this string representing HTML select options.
     */
    public function testGetCancelReasonHtmlSelect()
    {
        /** @var string */
        $cssClass = 'validate-select';
        /** @var string */
        $blockName = 'core/html_select';
        /** @var string */
        $defValue = null;
        /** @var string */
        $name = 'cancel_reason';
        /** @var string */
        $id = 'cancel_reason';
        /** @var string */
        $title = 'Reason';
        /** @var string */
        $translatedTitle = $title;
        /** @var array */
        $optionArray = [
            ['value' => '', 'label' => ''],
            ['value' => 'reason_code_001', 'label' => 'Wrong Products'],
        ];
        /** @var string */
        $html = '';
        foreach ($optionArray as $option) {
            $html .= sprintf('<option value="%s">%s</option>', $option['value'], $option['label']);
        }

        /** @var Mage_Core_Block_Abstract */
        $block = $this->getMockForAbstractClass('Mage_Core_Block_Abstract', [], '', true, true, true, [
            'setName', 'setId', 'setTitle', 'setClass', 'setValue', 'setOptions', 'getHtml'
        ]);
        $block->expects($this->once())
            ->method('setName')
            ->with($this->identicalTo($name))
            ->will($this->returnSelf());
        $block->expects($this->once())
            ->method('setId')
            ->with($this->identicalTo($id))
            ->will($this->returnSelf());
        $block->expects($this->once())
            ->method('setTitle')
            ->with($this->identicalTo($translatedTitle))
            ->will($this->returnSelf());
        $block->expects($this->once())
            ->method('setClass')
            ->with($this->identicalTo($cssClass))
            ->will($this->returnSelf());
        $block->expects($this->once())
            ->method('setValue')
            ->with($this->identicalTo($defValue))
            ->will($this->returnSelf());
        $block->expects($this->once())
            ->method('setOptions')
            ->with($this->identicalTo($optionArray))
            ->will($this->returnSelf());
        $block->expects($this->once())
            ->method('getHtml')
            ->will($this->returnValue($html));

        /** @var Mage_Core_Model_Layout */
        $layout = $this->getModelMock('core/layout', ['createBlock']);
        $layout->expects($this->once())
            ->method('createBlock')
            ->with($this->identicalTo($blockName))
            ->will($this->returnValue($block));

        /** @var EbayEnterprise_Order_Helper_Data */
        $orderHelper = $this->getHelperMock('ebayenterprise_order/data', ['__', 'getCancelReasonOptionArray']);
        $orderHelper->expects($this->once())
            ->method('__')
            ->with($this->identicalTo($title))
            ->will($this->returnValue($translatedTitle));
        $orderHelper->expects($this->once())
            ->method('getCancelReasonOptionArray')
            ->will($this->returnValue($optionArray));

        /** @var Mock_EbayEnterprise_Order_Block_Order_Cancel */
        $orderCancel = $this->getBlockMock('ebayenterprise_order/order_cancel', ['getLayout', 'getOrder', '__']);
        $orderCancel->expects($this->once())
            ->method('getLayout')
            ->will($this->returnValue($layout));

        // Replacing the protected class property ebayenterprise_order/order_cancel::$_orderHelper with a mock.
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($orderCancel, '_orderHelper', $orderHelper);

        $this->assertSame($html, $orderCancel->getCancelReasonHtmlSelect($defValue, $name, $id, $title));
    }

    /**
     * Test that the block method ebayenterprise_order/order_cancel::getPostActionUrl()
     * is invoked, and it will call the block method ebayenterprise_order/order_cancel::getUrl()
     * and passed in as first parameter the return value from calling the method
     * ebayenterprise_order/order_cancel::_getCancelUrlPath() and as second parameter
     * an array with key 'order_id' mapped to the return value from calling the method sales/order::getRealOrderId().
     * The method ebayenterprise_order/order_cancel::getUrl() will return string representing the post action URL.
     * This return value will be the return value for the method ebayenterprise_order/order_cancel::getPostActionUrl().
     */
    public function testGetPostActionUrl()
    {
        /** @var string */
        $path = 'sales/order/romcancel';
        /** @var string */
        $orderId = '1000000039889321';
        /** @var array */
        $data = ['order_id' => $orderId];
        /** @var string */
        $url = "http://test-example.com/sales/order/romcancel/order_id/{$orderId}/";
        /** @var Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order', ['real_order_id' => $orderId]);

        /** @var Mock_EbayEnterprise_Order_Block_Order_Cancel */
        $orderCancel = $this->getBlockMock('ebayenterprise_order/order_cancel', ['getUrl', 'getOrder', '_getCancelUrlPath']);
        $orderCancel->expects($this->once())
            ->method('getUrl')
            ->with($this->identicalTo($path), $this->identicalTo($data))
            ->will($this->returnValue($url));
        $orderCancel->expects($this->once())
            ->method('getOrder')
            ->will($this->returnValue($order));
        $orderCancel->expects($this->once())
            ->method('_getCancelUrlPath')
            ->will($this->returnValue($path));

        $this->assertSame($url, $orderCancel->getPostActionUrl());
    }

    /**
     * Test that the block method ebayenterprise_order/order_cancel::getBackUrl()
     * is invoked, and it will call the helper method core/http::getHttpReferer()
     * which will return the referer URL. Finally, the block method
     * ebayenterprise_order/order_cancel::getBackUrl() will return this referer URL.
     */
    public function testGetBackUrl()
    {
        /** @var string */
        $refererUrl = 'http://test-example.com/sales/order/romview';

        /** @var Mage_Core_Helper_Http */
        $httpHelper = $this->getHelperMock('core/http', ['getHttpReferer']);
        $httpHelper->expects($this->once())
            ->method('getHttpReferer')
            ->will($this->returnValue($refererUrl));

        /** @var Mock_EbayEnterprise_Order_Block_Order_Cancel */
        $orderCancel = $this->_createBlock('ebayenterprise_order/order_cancel');

        // Replacing the protected class property ebayenterprise_order/order_cancel::$_coreHttp with a mock.
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($orderCancel, '_coreHttp', $httpHelper);

        $this->assertSame($refererUrl, $orderCancel->getBackUrl());
    }

    /**
     * Test that the block method ebayenterprise_order/order_cancel::getHelper()
     * is invoked, and it will return instance of type EbayEnterprise_Order_Helper_Data.
     */
    public function testGetHelper()
    {
        /** @var string */
        $helperClass = 'ebayenterprise_order';
        /** @var Mock_EbayEnterprise_Order_Helper_Data */
        $orderHelper = $this->getHelperMock('ebayenterprise_order/data');
        $this->replaceByMock('helper', 'ebayenterprise_order', $orderHelper);

        /** @var Mock_EbayEnterprise_Order_Block_Order_Cancel */
        $orderCancel = $this->_createBlock('ebayenterprise_order/order_cancel');

        $this->assertSame($orderHelper, $orderCancel->getHelper($helperClass));
    }

    /**
     * @return array
     */
    public function providerGetCancelUrlPath()
    {
        return [
            [true, EbayEnterprise_Order_Block_Order_Cancel::LOGGED_IN_CANCEL_URL_PATH],
            [false, EbayEnterprise_Order_Block_Order_Cancel::GUEST_CANCEL_URL_PATH],
        ];
    }

    /**
     * Test that the block method ebayenterprise_order/order_cancel::_getCancelUrlPath()
     * is invoked, and it will call the block method ebayenterprise_order/order_cancel::_getSession()
     * which will return an instance of type Mage_Customer_Model_Session. Then, the method
     * customer/session::isLoggedIn() will be invoked and if it returns a boolean value true
     * then the block method ebayenterprise_order/order_cancel::_getCancelUrlPath() will
     * return the class constant ebayenterprise_order/order_cancel::::LOGGED_IN_CANCEL_URL_PATH.
     * Otherwise, it will return the class constant ebayenterprise_order/order_cancel::::GUEST_CANCEL_URL_PATH.
     *
     * @param bool
     * @param string
     * @dataProvider providerGetCancelUrlPath
     */
    public function testGetCancelUrlPath($isLoggedIn, $result)
    {
        /** @var Mage_Customer_Model_Session */
        $session = $this->getModelMockBuilder('customer/session')
            ->setMethods(['isLoggedIn'])
            // Disabling the constructor in order to prevent session_start() function from being
            // called which causes headers already sent exception from being thrown.
            ->disableOriginalConstructor()
            ->getMock();
        $session->expects($this->once())
            ->method('isLoggedIn')
            ->will($this->returnValue($isLoggedIn));

        /** @var Mock_EbayEnterprise_Order_Block_Order_Cancel */
        $orderCancel = $this->getBlockMock('ebayenterprise_order/order_cancel', ['_getSession']);
        $orderCancel->expects($this->once())
            ->method('_getSession')
            ->will($this->returnValue($session));

        $this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $orderCancel,
            '_getCancelUrlPath',
            []
        ));
    }
}
