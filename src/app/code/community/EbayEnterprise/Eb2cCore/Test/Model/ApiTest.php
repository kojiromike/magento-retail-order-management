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

class EbayEnterprise_Eb2cCore_Test_Model_ApiTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    const MODEL_CLASS = 'EbayEnterprise_Eb2cCore_Model_Api';
    const ADAPTER_CLASS_NAME = 'Zend_Http_Client_Adapter_Socket';
    const MOCK_ADAPTER_CLASS_NAME = 'Mock_Zend_Http_Client_Adapter_Socket';

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

    public function provideApiCall()
    {
        $domDocument = Mage::helper('eb2ccore')->getNewDomDocument();
        $quantityRequestMessage = $domDocument->addElement('QuantityRequestMessage', null, 'http://api.gsicommerce.com/schema/checkout/1.0')->firstChild;
        $quantityRequestMessage->createChild(
            'QuantityRequest',
            null,
            array('lineId' => 1, 'itemId' => 'SKU-1234')
        );
        $quantityRequestMessage->createChild(
            'QuantityRequest',
            null,
            array('lineId' => 2, 'itemId' => 'SKU-4321')
        );
        return array(
            array(
                $domDocument,
                'http://example.com/',
                'Inventory-Service-Quantity-1.0.xsd'
            ),
        );
    }
    /**
     * Test that the request method returns a non-empty string on successful response.
     *
     */
    public function testRequest()
    {
        $strType = gettype('');
        $request = $this->getMockBuilder('DOMDocument')
            ->disableOriginalConstructor()
            ->setMethods(array('C14N'))
            ->getMock();
        $apiUri = 'http://example.com/';
        $xsdName = 'Inventory-Service-Quantity-1.0.xsd';
        $responseText = 'the response';
        $requestText = 'request';
        $httpClient = $this->getMock('Varien_Http_Client', array('setHeaders', 'setUri', 'setConfig', 'setRawData', 'setEncType', 'request'));
        $httpResponse = $this->getMock('Zend_Http_Response', array('getStatus', 'isSuccessful', 'getBody'), array(200, array()));
        $api = $this->getModelMock('eb2ccore/api', array('_processResponse', '_processException', 'schemaValidate'));

        $request->expects($this->at(0))
            ->method('C14N')
            ->will($this->returnValue($requestText));

        $httpClient->expects($this->once())
            ->method('setHeaders')
            ->with($this->identicalTo('apiKey'), $this->isType($strType))
            ->will($this->returnSelf());
        $httpClient->expects($this->once())
            ->method('setUri')
            ->with($this->isType($strType))
            ->will($this->returnSelf());
        $httpClient->expects($this->once())
            ->method('setConfig')
            ->with($this->logicalAnd(
                $this->arrayHasKey('adapter'),
                $this->arrayHasKey('timeout')
            ))
            ->will($this->returnSelf());
        $httpClient->expects($this->once())
            ->method('setRawData')
            ->with($this->isType($strType))
            ->will($this->returnSelf());
        $httpClient->expects($this->once())
            ->method('setEncType')
            ->with($this->identicalTo('text/xml'))
            ->will($this->returnSelf());
        $httpClient->expects($this->once())
            ->method('request')
            ->with($this->identicalTo('POST'))
            ->will($this->returnValue($httpResponse));

        $api->expects($this->once())
            ->method('_processResponse')
            ->with($this->identicalTo($httpResponse), $this->identicalTo($apiUri))
            ->will($this->returnValue($responseText));
        $api->expects($this->once())
            ->method('schemaValidate')
            ->with($this->identicalTo($request), $this->identicalTo($xsdName))
            ->will($this->returnSelf());

        $this->assertSame($responseText, $api->request($request, $xsdName, $apiUri, 0, self::ADAPTER_CLASS_NAME, $httpClient));
    }
    /**
     * Test that when an API key is provided as an argument, that the proper API
     * key gets used when setting up the client.
     */
    public function testRequestProvideApiKey()
    {
        $apiKey = 'the-api-key-to-use';
        $doc = new DOMDocument();

        $api = $this->getModelMock('eb2ccore/api', array('schemaValidate', '_setupClient', '_processResponse'));

        $response = $this->getMockBuilder('Zend_Http_Response')->disableOriginalConstructor()->getMock();

        $client = $this->getMock('Varien_Http_Client', array('request'));
        $client->expects($this->any())
            ->method('request')
            ->will($this->returnValue($response));

        $api->expects($this->once())
            ->method('_setupClient')
            // really only care that the right API key is used
            ->with($this->anything(), $this->identicalTo($apiKey))
            ->will($this->returnValue($client));

        $api->request($doc, 'some_file.xsd', 'http://example.com:80', 10, 'Zend_Http_Client_Adapter_Socket', $client, $apiKey);
    }
    /**
     * Test that we can handle a Zend_Http_Client_Exception
     *
     * @dataProvider provideApiCall
     */
    public function testZendHttpClientException($request, $apiUri, $xsdName)
    {
        $responseBody = '';
        $exception = new Zend_Http_Client_Exception;
        $api = $this->getModelMock('eb2ccore/api', array('_setupClient', '_processResponse', '_processException'));
        $httpClient = $this->getMock('Varien_Http_Client', array('request'));

        $httpClient->expects($this->once())
            ->method('request')
            ->with($this->identicalTo('POST'))
            ->will($this->throwException($exception));

        $api->expects($this->any())->method('_setupClient')->will($this->returnValue($httpClient));

        $api->expects($this->once())
            ->method('_processException')
            ->with($this->identicalTo($exception), $this->identicalTo($apiUri))
            ->will($this->returnValue($responseBody));

        $this->assertSame($responseBody, $api->request($request, $xsdName, $apiUri, 0, self::ADAPTER_CLASS_NAME, $httpClient));
    }
    /**
     * Mock out the config helper.
     */
    protected function _mockConfig()
    {
        $mock = $this->getModelMockBuilder('eb2ccore/config_registry')
            ->disableOriginalConstructor()
            ->setMethods(array('__get'))
            ->getMock();
        $mockConfig = array(
            array('apiEnvironment', 'prod'),
            array('apiMajorVersion', '1'),
            array('apiMinorVersion', '10'),
            array('apiRegion', 'eu'),
            array('apiXsdPath', 'app/code/local/EbayEnterprise/Eb2cCore/xsd'),
            array('storeId', 'store-123'),
        );
        $mock->expects($this->any())
            ->method('__get')
            ->will($this->returnValueMap($mockConfig));
        $this->replaceByMock('model', 'eb2ccore/config_registry', $mock);
    }

    /**
     * Call the configured method.
     */
    public function testProcessResponse()
    {
        $statusCode = 200;
        $handlerKey = 'success';
        $uri = 'https://some.url/to/service';
        $responseBody = 'this is some response text';
        $handlerConfig = array('callback' => array('method' => 'foo'));
        $response = $this->getMock('Zend_Http_Response', array('getStatus', 'asString'), array(200, array()));
        $callbackConfigWithArgs = array('method' => 'foo', 'parameters' => array($response));
        $helper = $this->getHelperMock('eb2ccore/data', array('invokeCallback'));
        $api = $this->getModelMock('eb2ccore/api', array('_getHandlerConfig', '_getHandlerKey'));

        $api->expects($this->once())
            ->method('_getHandlerKey')
            ->with($this->identicalTo($response))
            ->will($this->returnValue($handlerKey));

        $api->expects($this->once())
            ->method('_getHandlerConfig')
            ->with($this->identicalTo($handlerKey))
            ->will($this->returnValue($handlerConfig));

        $response->expects($this->once())
            ->method('getStatus')
            ->will($this->returnValue($statusCode));

        $helper->expects($this->once())
            ->method('invokeCallback')
            ->with($this->identicalTo($callbackConfigWithArgs))
            ->will($this->returnValue($responseBody));

        $this->replaceByMock('helper', 'eb2ccore', $helper);
        $result = EcomDev_Utils_Reflection::invokeRestrictedMethod($api, '_processResponse', array($response, $uri));
        $this->assertSame($responseBody, $result);
        $this->assertSame($statusCode, $api->getStatus());
    }

    /**
     * Call the configured method.
     */
    public function testProcessException()
    {
        $handlerKey = 'no_response';
        $uri = 'https://some.url/to/service';
        $handlerConfig = array('callback' => array('method' => 'foo'));
        $callbackConfig = array('method' => 'foo');
        $exception = new Zend_Http_Client_Exception('some error message');
        $helper = $this->getHelperMock('eb2ccore/data', array('invokeCallback'));
        $zendClient = $this->getMock('Zend_Http_Client', array('getUri'));
        $api = $this->getModelMock('eb2ccore/api', array('_getHandlerConfig', '_getHandlerKey'));

        $this->replaceByMock('helper', 'eb2ccore', $helper);

        $api->expects($this->once())->method('_getHandlerKey')->will($this->returnValue($handlerKey));
        $api->expects($this->once())
            ->method('_getHandlerConfig')
            ->with($this->identicalTo($handlerKey))
            ->will($this->returnValue($handlerConfig));

        $zendClient->expects($this->any())->method('getUri')->will($this->returnValue($uri));

        $helper->expects($this->once())
            ->method('invokeCallback')
            ->with($this->identicalTo($callbackConfig))
            ->will($this->returnValue('the callback output'));

        $result = EcomDev_Utils_Reflection::invokeRestrictedMethod($api, '_processException', array(
            $exception,
            $uri
        ));
        $this->assertSame('the callback output', $result);
        $this->assertSame(0, $api->getStatus());
    }
    /**
     * get configuration using the value returned from getStatusHandlerConfigPath.
     * if the path is empty get the default silent config.
     * otherwise get the default config for the alertLevel defined in the custom config
     * and merge the two arrays such that the defaults get overwritten by the custom
     * values.
     * return the handler config mapped to the supplied status key.
     */
    public function testGetHandlerConfig()
    {
        $configArray = array('config data');
        $mergedConfig = array('status' => array('success' => array('the success handler')));
        $handlerKey = 'success';
        $configPath = 'eb2ccore/api/status/handler/testhandler';

        $api = $this->getModelMock('eb2ccore/api', array('getIsFailureLoud', 'getStatusHandlerConfigPath', '_getMergedHandlerConfig'));
        $api->setStatusHandlerPath($configPath);
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($api, '_statusHandlerPath', $configPath);
        $api->expects($this->once())
            ->method('_getMergedHandlerConfig')
            ->with($this->identicalTo($configArray))
            ->will($this->returnValue($mergedConfig));

        $configRegistryMock = $this->getModelMock('eb2ccore/config_registry', array('getConfigData'));
        $configRegistryMock->expects($this->once())
            ->method('getConfigData')
            ->with($this->identicalTo($configPath))
            ->will($this->returnValue($configArray));
        $this->replaceByMock('model', 'eb2ccore/config_registry', $configRegistryMock);

        $result = EcomDev_Utils_Reflection::invokeRestrictedMethod($api, '_getHandlerConfig', array($handlerKey));
        $this->assertSame(array('the success handler'), $result);
    }
    public function provideForTestGetHandlerKey()
    {
        return array(
            array(null, 'no_response'),
            array(0, 'no_response'),
            array(400, 'client_error'),
            array(499, 'client_error'),
            array(500, 'server_error'),
            array(501, 'server_error'),
            array(599, 'server_error'),
        );
    }
    /**
     * return a string that is a key to the handler config for the class of status codes.
     * @dataProvider provideFortestGetHandlerKey
     */
    public function testGetHandlerKey($code, $key)
    {
        $response = $this->getMockBuilder('Zend_Http_Response')
            ->disableOriginalConstructor()
            ->setMethods(array('isSuccessful', 'isRedirect', 'getStatus'))
            ->getMock();
        $api = Mage::getModel('eb2ccore/api');

        $response->expects($this->any())
            ->method('isRedirect')
            ->will($this->returnValue(false));
        $response->expects($this->any())
            ->method('isSuccessful')
            ->will($this->returnValue(false));
        $response->expects($this->once())
            ->method('getStatus')
            ->will($this->returnValue($code));

        $this->assertSame($key, EcomDev_Utils_Reflection::invokeRestrictedMethod($api, '_getHandlerKey', array($response)));
    }
    public function provideForTestGetHandlerKeySuccess()
    {
        return array(
            array(true, true, 'success'),
            array(true, false, 'success'),
            array(false, true, 'success')
        );
    }
    /**
     * return a string that is a key to the handler config for the class of status codes.
     * @dataProvider provideForTestGetHandlerKeySuccess
     */
    public function testGetHandlerKeySuccess($success, $redirect, $key)
    {
        $response = $this->getMockBuilder('Zend_Http_Response')
            ->disableOriginalConstructor()
            ->setMethods(array('isSuccessful', 'isRedirect'))
            ->getMock();
        $api = Mage::getModel('eb2ccore/api');

        $response->expects($this->any())
            ->method('isRedirect')
            ->will($this->returnValue($redirect));
        $response->expects($this->any())
            ->method('isSuccessful')
            ->will($this->returnValue($success));

        $this->assertSame($key, EcomDev_Utils_Reflection::invokeRestrictedMethod($api, '_getHandlerKey', array($response)));
    }
    /**
     * by default return the silent config
     */
    public function testGetMergedHandlerConfigWithEmptyConfig()
    {
        $defaultConfig = array('default config');

        $configRegistryMock = $this->getModelMock('eb2ccore/config_registry', array('getConfigData'));
        $configRegistryMock->expects($this->once())
            ->method('getConfigData')
            ->with($this->identicalTo(EbayEnterprise_Eb2cCore_Model_Api::DEFAULT_HANDLER_CONFIG))
            ->will($this->returnValue($defaultConfig));
        $this->replaceByMock('model', 'eb2ccore/config_registry', $configRegistryMock);

        $api = $this->getModelMock('eb2ccore/api', array('getHandlerConfigPath'));
        $result = EcomDev_Utils_Reflection::invokeRestrictedMethod($api, '_getMergedHandlerConfig');
        $this->assertSame(array('default config'), $result);
    }
    /**
     * return the merged contents of the default (silent) config and the loud config and the passed in array
     * such that the loud config's values override the silent config's values and the passed config's values
     * override the loud config's values.
     */
    public function testGetMergedHandlerConfigWithLoudConfig()
    {
        $config = array('alert_level' => 'loud', 'nested' => array('foo' => 'fie'));
        $loudConfig = array('alert_level' => 'loud', 'nested' => array('foo' => 'fang'));
        $defaultConfig = array('alert_level' => 'silent', 'nested' => array('foo' => 'baz', 'gee' => 'wiz'));

        $configRegistryMock = $this->getModelMock('eb2ccore/config_registry', array('getConfigData'));
        $configRegistryMock->expects($this->exactly(2))
            ->method('getConfigData')
            ->with($this->isType('string'))
            ->will($this->returnValueMap(array(
                array(EbayEnterprise_Eb2cCore_Model_Api::DEFAULT_HANDLER_CONFIG, $defaultConfig),
                array(EbayEnterprise_Eb2cCore_Model_Api::DEFAULT_LOUD_HANDLER_CONFIG, $loudConfig),
            )));
        $this->replaceByMock('model', 'eb2ccore/config_registry', $configRegistryMock);

        $api = Mage::getModel('eb2ccore/api');
        $result = EcomDev_Utils_Reflection::invokeRestrictedMethod($api, '_getMergedHandlerConfig', array($config));
        $this->assertSame(array('alert_level' => 'loud', 'nested' => array('foo' => 'fie', 'gee' => 'wiz')), $result);
    }
    /**
     * set the status handler path
     */
    public function testSetStatusHandlerPath()
    {
        $api = Mage::getModel('eb2ccore/api');
        $this->assertSame($api, $api->setStatusHandlerPath('some/path'));
        $this->assertSame(
            'some/path',
            EcomDev_Utils_Reflection::getRestrictedPropertyValue($api, '_statusHandlerPath')
        );
    }
}
