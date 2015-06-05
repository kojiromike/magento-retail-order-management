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


class EbayEnterprise_Eb2cCore_Test_Helper_ValidatorTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /**
     * The Mage::app instance when starting the tests, stored so that when it is
     * swapped out during a test it can be put back in place after tests have run.
     * @var Mage_Core_Model_App
     */
    protected $_origApp;
    /**
     * Store the Mage::app before tests have run.
     */
    public function setUp()
    {
        parent::setUp();
        $this->_origApp = EcomDev_Utils_Reflection::getRestrictedPropertyValue('Mage', '_app');

        // suppressing the real session from starting
        $session = $this->getModelMockBuilder('core/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->replaceByMock('singleton', 'core/session', $session);
    }
    /**
     * Restore Mage::app to the initial value
     */
    public function tearDown()
    {
        EcomDev_Utils_Reflection::setRestrictedPropertyValue('Mage', '_app', $this->_origApp);
        parent::tearDown();
    }
    /**
     * Test doign simple validations on API settings. When all are valid, should
     * simply return self
     */
    public function testValidateSettings()
    {
        // Use a mock of the helper being tested to make sure a new instance
        // is generated, preventing (un)mocked dependencies from being cached
        // in the constructor.
        $helper = $this->getHelperMock('eb2ccore/validator', null);
        $this->assertSame(
            $helper,
            EcomDev_Utils_Reflection::invokeRestrictedMethod(
                $helper,
                '_validateApiSettings',
                ['STORE_ID', 'API_KEY', 'example.com']
            )
        );
    }
    public function provideSettingsAndExceptions()
    {
        return [
            ['', '', '', 'Store Id, API Key, API Hostname'],
            ['', '', 'example.com', 'Store Id, API Key'],
            ['', 'apikey-123', 'example.com', 'Store Id'],
        ];
    }
    /**
     * Test doing simple validations on the settings - basically ensure that none
     * are empty. If any are, an exception should be thrown which includes the
     * settings that are invalid.
     * @param  string $storeId
     * @param  string $apiKey
     * @param  string $hostname
     * @param  string $exceptionMessage
     * @dataProvider provideSettingsAndExceptions
     */
    public function testValidateInvalidSettings($storeId, $apiKey, $hostname, $exceptionMessage)
    {
        $this->setExpectedException(
            'EbayEnterprise_Eb2cCore_Exception_Api_Configuration',
            $exceptionMessage
        );
        $translationHelper = $this->getHelperMock('eb2ccore/data', ['__']);
        $translationHelper->expects($this->once())
            ->method('__')
            ->will($this->returnArgument(1));
        $this->replaceByMock('helper', 'eb2ccore', $translationHelper);
        // Use a mock of the helper being tested to make sure a new instance
        // is generated, preventing (un)mocked dependencies from being cached
        // in the constructor.
        $helper = $this->getHelperMock('eb2ccore/validator', null);
        EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $helper,
            '_validateApiSettings',
            [$storeId, $apiKey, $hostname]
        );
    }
    /**
     * Test validating SFTP settings when settings are all potentially valid - not
     * empty or easily detectable errors.
     */
    public function testValidateSftpSettings()
    {
        // Use a mock of the helper being tested to make sure a new instance
        // is generated, preventing (un)mocked dependencies from being cached
        // in the constructor.
        $helper = $this->getHelperMock('eb2ccore/validator', null);
        $this->assertSame(
            $helper,
            EcomDev_Utils_Reflection::invokeRestrictedMethod(
                $helper,
                '_validateSftpSettings',
                ['host.example.com', 'test_user', '--- Private Key ---', '22']
            )
        );
    }
    /**
     * Provide invalid SFTP configurations - missing/empty values or easily
     * detectable errors.
     * @return array
     */
    public function provideSftpSettingsAndExceptions()
    {
        return [
            ['', '', '', '', 'Remote Host, SFTP User Name, Private Key, Remote Port'],
            ['', '', '', '0', 'Remote Host, SFTP User Name, Private Key, Remote Port'],
            ['', '', '', '22', 'Remote Host, SFTP User Name, Private Key'],
            ['', '', '---- PRIVATE KEY ----', '22', 'Remote Host, SFTP User Name'],
            ['', 'test_user', '---- PRIVATE KEY ----', '22', 'Remote Host'],
        ];
    }
    /**
     * Test doing simple validations on the settings - basically ensure that none
     * are empty or obviously wrong. If any are, an exception should be thrown
     * which includes the settings that are invalid.
     * @param string $host
     * @param string $username
     * @param string $privateKey
     * @param string $port
     * @param string $exceptionMessage
     * @dataProvider provideSftpSettingsAndExceptions
     */
    public function testValidateInvalidSftpSettings($host, $username, $privateKey, $port, $exceptionMessage)
    {
        $this->setExpectedException(
            'EbayEnterprise_Eb2cCore_Exception_Sftp_Configuration',
            $exceptionMessage
        );
        $translationHelper = $this->getHelperMock('eb2ccore/data', ['__']);
        $translationHelper->expects($this->once())
            ->method('__')
            ->will($this->returnArgument(1));
        $this->replaceByMock('helper', 'eb2ccore', $translationHelper);
        // Use a mock of the helper being tested to make sure a new instance
        // is generated, preventing (un)mocked dependencies from being cached
        // in the constructor.
        $helper = $this->getHelperMock('eb2ccore/validator', null);
        EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $helper,
            '_validateSftpSettings',
            [$host, $username, $privateKey, $port]
        );
    }
    /**
     * Provide the request params, whether the "default" value is expected and
     * the type of the config source expected.
     * @return array
     */
    public function provideRequestParams()
    {
        return [
            [['website' => 'default'], true, 'Mage_Core_Model_Store'],
            [['website' => 'default'], false, 'Mage_Core_Model_Website'],
            [['store' => 'main'], true, 'Mage_Core_Model_Website'],
            [['store' => 'main'], false, 'Mage_Core_Model_Store'],
            [[], true, 'Mage_Core_Model_Store'],
            [[], false, 'Mage_Core_Model_Store'],
        ];
    }
    /**
     * Test getting the configuration source based on request params. Should result
     * in either a Mage_Core_Model_Store or Mage_Core_Model_Website depending
     * on the request params.
     * @param  array $requestParams
     * @param  string $sourceType Model class that should provide the config data
     * @dataProvider provideRequestParams
     */
    public function testGetConfigSource($requestParams, $useDefault, $sourceType)
    {
        $requestMock = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
        $requestMock->setParams($requestParams);
        // Use a mock of the helper being tested to make sure a new instance
        // is generated, preventing (un)mocked dependencies from being cached
        // in the constructor.
        $helper = $this->getHelperMock('eb2ccore/validator', null);

        $store = $this->getModelMockBuilder('core/store')
            ->setMethods(['getWebsite'])
            ->getMock();
        $website = $this->getModelMockBuilder('core/website')
            ->getMock();

        $store->expects($this->any())
            ->method('getWebsite')
            ->will($this->returnValue($website));
        $app = $this->getModelMockBuilder('core/app')
            ->setMethods(['getWebsite', 'getStore'])
            ->getMock();
        $app->expects($this->any())
            ->method('getStore')
            ->will($this->returnValue($store));
        $app->expects($this->any())
            ->method('getWebsite')
            ->will($this->returnValue($website));
        EcomDev_Utils_Reflection::setRestrictedPropertyValue('Mage', '_app', $app);

        $this->assertInstanceOf(
            $sourceType,
            $helper->getConfigSource($requestMock, $useDefault)
        );
    }
    public function provideParamsForFallbackTest()
    {
        $paramValue = 'param value';
        $configValue = 'config value';

        return [
            [['param' => $paramValue, 'param_use_default' => '0'], $configValue, $paramValue],
            [['param' => $paramValue, 'param_use_default' => '1'], $configValue, $configValue],
            [['param' => '', 'param_use_default' => '0'], $configValue, ''],
            [['not_the_param' => $paramValue, 'not_the_use_default' => '0'], $configValue, $configValue],
        ];
    }
    /**
     * Test getting the value from the param or config depending on the params
     * available in the request.
     * @param array $params Request params
     * @param string $configValue Value stored in config
     * @param string $expectedValue Expected output based on given request params
     * @dataProvider provideParamsForFallbackTest
     */
    public function testGetParamOrFallbackValue($params, $configValue, $expectedValue)
    {
        $configPath = 'path/to/config/value';
        $paramName = 'param';
        $useDefaultName = 'param_use_default';

        $requestMock = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
        $requestMock->setParams($params);

        $configSource = $this->getModelMockBuilder('core/store')
            ->setMethods(['getConfig'])
            ->getMock();

        // Use a mock of the helper being tested to make sure a new instance
            // is generated, preventing (un)mocked dependencies from being cached
            // in the constructor.
        $helper = $this->getHelperMock('eb2ccore/validator', ['getConfigSource']);
        $helper->expects($this->any())
            ->method('getConfigSource')
            ->will($this->returnValue($configSource));
        $configSource->expects($this->any())
            ->method('getConfig')
            ->with($this->identicalTo($configPath))
            ->will($this->returnValue($configValue));

        $this->assertSame(
            $expectedValue,
            $helper->getParamOrFallbackValue($requestMock, $paramName, $useDefaultName, $configPath)
        );
    }
    public function provideEncryptedKeyParams()
    {
        return [
            [['api_key' => 'abcd1234', 'api_key_use_default' => ''], null, 'abcd1234'],
            [['api_key' => 'abcd1234', 'api_key_use_default' => '1'], 'core/store', '4321dcba'],
            [['api_key' => '******', 'api_key_use_default' => '0'], 'core/website', '4321dcba'],
            [['api_key' => '******', 'api_key_use_default' => '1'], 'core/store', '4321dcba'],
            [['api_key' => '', 'api_key_use_default' => '1'], 'core/website', ''],
            [['api_key' => '', 'api_key_use_default' => '0'], '', ''],
            [['api_key_use_default' => '1'], 'core/website', '4321dcba'],
        ];
    }
    /**
     * Test getting the API key to use for the request. It is expected to either
     * come from the request or configuration. When included in the request, it
     * should only be used if the use default param is not true and the value is
     * not the obscured value used by Magento for the encrypted config. When the
     * key is not in the request, is in the request as the obscured value or the
     * use default flag is true, the api key should come from config. Additionally
     * when the config source is a website, should also decrypt the value before
     * returning it.
     * @param  array $requestParams
     * @param  string|null $sourceType Factory alias for the source of config values, either core/store or core/website
     * @param  string $apiKey Expected API key to be returned
     * @dataProvider provideEncryptedKeyParams
     */
    public function testGetEncryptedParamOrFallbackValue($requestParams, $sourceType, $apiKey)
    {
        $configPath = 'eb2ccore/api/key';

        $requestMock = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
        $requestMock->setParams($requestParams);

        // Use a mock of the helper being tested to make sure a new instance
        // is generated, preventing (un)mocked dependencies from being cached
        // in the constructor.
        $helper = $this->getHelperMock('eb2ccore/validator', ['getConfigSource']);

        // if there is a source type, expect the value to come from config, so
        // need to set up the model it will be coming from
        if ($sourceType) {
            $configSource = $this->getModelMockBuilder($sourceType)
                ->setMethods(['getConfig'])
                ->getMock();
            $configSource->expects($this->any())
                ->method('getConfig')
                ->with($this->identicalTo($configPath))
                ->will($this->returnValue($apiKey));
            $helper->expects($this->once())
                ->method('getConfigSource')
                ->will($this->returnValue($configSource));
        }
        // when coming from a website, the config value needs to be decrypted,
        // this is handled automatically by the store model but not the website
        if ($sourceType === 'core/website') {
            $coreHelper = $this->getHelperMock('core/data', ['decrypt']);
            $coreHelper->expects($this->once())
                ->method('decrypt')
                ->with($this->identicalTo($apiKey))
                ->will($this->returnArgument(0));
            $this->replaceByMock('helper', 'core', $coreHelper);
        }

        $this->assertSame(
            $apiKey,
            $helper->getEncryptedParamOrFallbackValue($requestMock, 'api_key', 'api_key_use_default', $configPath)
        );
    }
    public function provideSftpPrivateKeyParams()
    {
        return [
            [['ssh_key' => 'abcd1234', 'ssh_key_use_default' => ''], null, 'abcd1234'],
            [['ssh_key' => 'abcd1234', 'ssh_key_use_default' => '1'], 'core/store', '4321dcba'],
            [['ssh_key' => '', 'ssh_key_use_default' => '1'], 'core/website', '4321dcba'],
            [['ssh_key' => '', 'ssh_key_use_default' => '0'], 'core/store', '4321dcba'],
            [['ssh_key_use_default' => '1'], 'core/website', '4321dcba'],
        ];
    }
    /**
     * Test getting the Sftp Private key to use for the request. It is expected
     * to either come from the request or configuration. When included in the
     * request, it should only be used if the use default param is not true and
     * the value is not the obscured value used by Magento for the encrypted
     * config. When the key is not in the request, is in the request as the
     * obscured value or the use default flag is true, the api key should come
     * from config. Additionally when the config source is a website, should also
     * decrypt the value before returning it.
     * @param  array $requestParams
     * @param  string $sourceType Factory alias for source of the config value when used
     * @param  string $apiKey Expected API key to be returned
     * @dataProvider provideSftpPrivateKeyParams
     */
    public function testGetSftpPrivateKey($requestParams, $sourceType, $apiKey)
    {
        $requestMock = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
        $requestMock->setParams($requestParams);

        $configPath = 'eb2ccore/feed/filetransfer_sftp_ssh_prv_key';

        // Use a mock of the helper being tested to make sure a new instance
        // is generated, preventing (un)mocked dependencies from being cached
        // in the constructor.
        $helper = $this->getHelperMock('eb2ccore/validator', ['getConfigSource']);

        // if there is a source type, expect the value to come from config, so
        // need to set up the model it will be coming from
        if ($sourceType) {
            $configSource = $this->getModelMockBuilder($sourceType)
                ->setMethods(['getConfig'])
                ->getMock();
            $configSource->expects($this->any())
                ->method('getConfig')
                ->with($this->identicalTo($configPath))
                ->will($this->returnValue($apiKey));
            $helper->expects($this->once())
                ->method('getConfigSource')
                ->will($this->returnValue($configSource));
        }
        // If the key comes from any config source, the key needs to be decrypted
        // before being returned.
        if ($sourceType) {
            $coreHelper = $this->getHelperMock('core/data', ['decrypt']);
            $coreHelper->expects($this->once())
                ->method('decrypt')
                ->with($this->identicalTo($apiKey))
                ->will($this->returnArgument(0));
            $this->replaceByMock('helper', 'core', $coreHelper);
        }
        $this->assertSame(
            $apiKey,
            $helper->getSftpPrivateKey($requestMock, 'ssh_key', 'ssh_key_use_default', $configPath)
        );
    }
}
