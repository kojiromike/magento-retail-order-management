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

class EbayEnterprise_Eb2cCore_Helper_Validator
{
    const API_HANDLER_PATH = 'eb2ccore/api/test_connection_status_handlers';
    const INVALID_API_SETTINGS_ERROR_MESSAGE = 'EbayEnterprise_Eb2cCore_Api_Validation_Invalid_Settings';

    // hard-coded API configuration for the test address validation request
    const AD_VAL_SERVICE = 'address';
    const AD_VAL_OPERATION = 'validate';
    const AD_VAL_XSD = 'Address-Validation-Service-1.0.xsd';

    const INVALID_SFTP_SETTINGS_ERROR_MESSAGE = 'EbayEnterprise_Eb2cCore_Sftp_Validation_Invalid_Settings';
    const INVALID_SFTP_CONNECTION = 'EbayEnterprise_Eb2cCore_Sftp_Invalid_Connection';
    const INVALID_SFTP_AUTHENTICATION_CONFIG = 'EbayEnterprise_Eb2cCore_Sftp_Invalid_Authentication_Configuration';
    const SFTP_CONNECTION_SUCCESS = 'EbayEnterprise_Eb2cCore_Sftp_Conection_Success';
    const SFTP_CONNECTION_FAILED = 'EbayEnterprise_Eb2cCore_Sftp_Connection_Failed';

    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $_logger;
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $_helper;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $_context;

    public function __construct()
    {
        $this->_logger = Mage::helper('ebayenterprise_magelog');
        $this->_helper = Mage::helper('eb2ccore');
        $this->_context = Mage::helper('ebayenterprise_magelog/context');
    }

    /**
     * Get the API test message by making a test request, using AdVal with a
     * hard-coded address, and using the response to determine if the configuration
     * is correct or not. Appropriate response messages for API response types
     * handled by custom API status handlers.
     * @param  string $storeId
     * @param  string $apiKey
     * @param  string $hostname
     * @return string
     */
    public function testApiConnection($storeId, $apiKey, $hostname)
    {
        try {
            $this->_validateApiSettings($storeId, $apiKey, $hostname);
        } catch (EbayEnterprise_Eb2cCore_Exception_Api_Configuration $e) {
            return [
                'message' => $e->getMessage(), 'success' => false
            ];
        }
        // need to manually build the URI as the eb2ccore/helper method doesn't
        // allow specifying the store id and doesn't really make sense for it to
        $coreConfig = $this->_helper->getConfigModel();
        $uri = $this->_getApiUri($hostname, $storeId, $coreConfig->apiMajorVersion, $coreConfig->apiMinorVersion);
        $xsd = self::AD_VAL_XSD;
        $adValRequest = $this->_buildTestRequest();

        /** @var EbayEnterprise_Eb2cCore_Model_Api $api */
        $api = Mage::getModel('eb2ccore/api');
        $api->setStatusHandlerPath(self::API_HANDLER_PATH);
        $response = $api->request($adValRequest, $xsd, $uri, $api::DEFAULT_TIMEOUT, $api::DEFAULT_ADAPTER, null, $apiKey);
        return $response;
    }

    /**
     * Get the URI of the API endpoint for the AdVal request using the provided
     * hostname, store id, and version.
     * @param  string $hostname
     * @param  string $storeId
     * @param  string $major
     * @param  string $minor
     * @return string
     */
    private function _getApiUri($hostname, $storeId, $major, $minor)
    {
        return sprintf(
            EbayEnterprise_Eb2cCore_Helper_Data::URI_FORMAT,
            $hostname,
            $major,
            $minor,
            $storeId,
            self::AD_VAL_SERVICE,
            self::AD_VAL_OPERATION,
            '',
            'xml'
        );
    }

    /**
     * Validate the store id, API key and hostname settings, ensuring that
     * none are empty.
     * @param  string $storeId
     * @param  string $apiKey
     * @param  string $hostname
     * @return self
     * @throws EbayEnterprise_Eb2cCore_Exception_Api_Configuration If any settings are empty
     */
    protected function _validateApiSettings($storeId, $apiKey, $hostname)
    {
        $invalidSettings = [];
        if ($storeId === '') {
            $invalidSettings[] = 'Store Id';
        }
        if ($apiKey === '') {
            $invalidSettings[] = 'API Key';
        }
        if ($hostname === '') {
            $invalidSettings[] = 'API Hostname';
        }
        if (!empty($invalidSettings)) {
            throw new EbayEnterprise_Eb2cCore_Exception_Api_Configuration(
                $this->_helper->__(self::INVALID_API_SETTINGS_ERROR_MESSAGE, implode(', ', $invalidSettings))
            );
        }
        return $this;
    }

    /**
     * Create an address validation request message using hardcoded address and MaxSuggestions.
     * @return EbayEnterprise_Dom_Document
     */
    protected function _buildTestRequest()
    {
        $coreHelper =  $this->_helper;
        $request = <<<REQUEST
<AddressValidationRequest xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
	<Header><MaxAddressSuggestions>3</MaxAddressSuggestions></Header>
	<Address>
		<Line1>935 1st Ave</Line1>
		<City>King of Prussia</City>
		<MainDivision>PA</MainDivision>
		<CountryCode>US</CountryCode>
		<PostalCode>19406</PostalCode>
	</Address>
</AddressValidationRequest>
REQUEST;
        $dom = $coreHelper->getNewDomDocument();
        $dom->loadXml($request);
        return $dom;
    }

    /**
     * Validate the store id, API key and hostname settings, ensuring that
     * none are empty.
     *
     * @param  string $host
     * @param  string $username
     * @param  string $key
     * @param  int $port
     * @throws EbayEnterprise_Eb2cCore_Exception_Sftp_Configuration
     * @return self
     */
    protected function _validateSftpSettings($host, $username, $key, $port)
    {
        $invalidSettings = [];
        if ($host === '') {
            $invalidSettings[] = 'Remote Host';
        }
        if ($username === '') {
            $invalidSettings[] = 'SFTP User Name';
        }
        if ($key === '') {
            $invalidSettings[] = 'Private Key';
        }
        // valid port would be int between 1 and 65535
        if ((int) $port < 1 || (int) $port > 65535) {
            $invalidSettings[] = 'Remote Port';
        }
        if (!empty($invalidSettings)) {
            throw new EbayEnterprise_Eb2cCore_Exception_Sftp_Configuration(
                $this->_helper->__(self::INVALID_SFTP_SETTINGS_ERROR_MESSAGE, implode(', ', $invalidSettings))
            );
        }
        return $this;
    }

    /**
     * Validate the SFTP configuration by first checking for any obviously invalid
     * settings - e.g. missing/empty values - then by making a test connection
     * using the given credential.
     *
     * @param string $host
     * @param string $username
     * @param string $privateKey
     * @param int $port
     * @return array
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function testSftpConnection($host, $username, $privateKey, $port)
    {
        try {
            $this->_validateSftpSettings($host, $username, $privateKey, $port);
        } catch (EbayEnterprise_Eb2cCore_Exception_Sftp_Configuration $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
        $configMap = Mage::getSingleton('eb2ccore/config');
        $sftp = Mage::helper('filetransfer')->getProtocolModel($configMap->getPathForKey('sftp_config'));
        $sftp->getConfigModel()->setHost($host)
            ->setUsername($username)
            ->setPrivateKey($privateKey)
            ->setPort($port);
        $helper = $this->_helper;
        $resp = [];
        // When the Net_SFTP instance failes to connect, it will trigger an error
        // instead of throwing an exception. Convert the error to an exception
        // so it can halt execution and be caught.
        set_error_handler(function ($errno, $errstr) {
            throw new EbayEnterprise_Eb2cCore_Exception_Sftp_Configuration($errstr);
        }, E_USER_NOTICE);
        try {
            $sftp->connect()->login();
        } catch (EbayEnterprise_Eb2cCore_Exception_Sftp_Configuration $e) {
            $this->_logger->critical($e->getMessage(), $this->_context->getMetaData(__CLASS__, [], $e));
            $resp = ['message' => $helper->__(self::INVALID_SFTP_CONNECTION), 'success' => false];
        } catch (EbayEnterprise_FileTransfer_Exception_Authentication $e) {
            $this->_logger->critical($e->getMessage(), $this->_context->getMetaData(__CLASS__, [], $e));
            $resp = ['message' => $helper->__(self::INVALID_SFTP_AUTHENTICATION_CONFIG), 'success' => false];
        }
        // Restore the error handler to get rid of the exception throwing one.
        restore_error_handler();
        // Final check, if the connection is logged in, everything went as planned
        // and the connection has been made. If not, and the reason for it has not
        // yet been dicovered by the exception handling above, just give a lame
        // indication that the connection could not be made.
        if ($sftp->isLoggedIn()) {
            $resp = ['message' => $helper->__(self::SFTP_CONNECTION_SUCCESS), 'success' => true];
        } elseif (empty($resp)) {
            $resp = ['message' => $helper->__(self::SFTP_CONNECTION_FAILED), 'success' => false];
        }
        return $resp;
    }
    /**
     * Get the param from the request if included in the request in the unencrypted
     * state (not replaced by ******). When not included, get the value from
     * config, decrypting the value if necessary.
     * @param Zend_Controller_Request_Abstract $request
     * @param string $param Name of the param that may contain the value
     * @param string $useDefaultParam  Name of the param indicating if the value should fallback
     * @param string $configPath
     * @return string
     */
    public function getEncryptedParamOrFallbackValue(Zend_Controller_Request_Abstract $request, $param, $useDefaultParam, $configPath)
    {
        $key = $request->getParam($param);
        $useDefault = $request->getParam($useDefaultParam);
        if (is_null($key) || preg_match('/^\*+$/', $key) || $useDefault) {
            $configSource = $this->getConfigSource($request, $useDefault);
            $key = $configSource->getConfig($configPath);
            // As there is a config/default node with a backend_model set for this
            // config value, Mage_Core_Model_Store->getConfig will auto-decrypt the
            // value when the config source is a Mage_Core_Model_Store. When the
            // source is a Mage_Core_Model_Website, this doesn't happen automatically
            // so the config value needs to be manually decrypted.
            if ($configSource instanceof Mage_Core_Model_Website) {
                $key = Mage::helper('core')->decrypt($key);
            }
        }
        return trim($key);
    }
    /**
     * Get the value from the request or via the config fallback.
     * @param Zend_Controller_Request_Abstract $request
     * @param string $param Name of the param that may contain the value
     * @param string $useDefaultParam  Name of the param indicating if the value should fallback
     * @param string $configPath Core config registry key to get a fallback value for
     * @return string
     */
    public function getParamOrFallbackValue(Zend_Controller_Request_Abstract $request, $param, $useDefaultParam, $configPath)
    {
        $paramValue = $request->getParam($param);
        $useFallback = $request->getParam($useDefaultParam);

        if (is_null($paramValue) || $useFallback) {
            return $this->getConfigSource($request, $useFallback)
                ->getConfig($configPath);
        }
        return trim($paramValue);
    }
    /**
     * Get the source of configuration for the request. Should use the store
     * or website specified in the request params. If neither is present, should
     * use the default store.
     * @param Zend_Controller_Request_Abstract $request
     * @param bool $useFallback Should the config value fallback to parent value
     * @return Mage_Core_Model_Store|Mage_Core_Model_Website
     */
    public function getConfigSource(Zend_Controller_Request_Abstract $request, $useFallback = false)
    {
        $store = $request->getParam('store');
        $website = $request->getParam('website');

        if ($store) {
            $storeObj = Mage::app()->getStore($store);
            // specific store view should fall back to website store view is in
            return $useFallback ? $storeObj->getWebsite() : $storeObj;
        }
        if ($website) {
            $websiteObj = Mage::app()->getWebsite($website);
            // website should fall back to default store
            return $useFallback ? Mage::app()->getStore(null) : $websiteObj;
        }
        // default to default store
        return Mage::app()->getStore(null);
    }
    /**
     * Get the private key value from config or the request. When from config,
     * should be decrypted before being returned. This is subtly different from
     * getEncryptedParamOrFallbackValue so doesn't use it. Required to fallback
     * if the param is empty and not just null, must always be decrypted, and
     * will never be replace by /*+/. Close enough to want them to be the same
     * but just different enough to no actually make them the same.
     * @param Zend_Controller_Request_Abstract $request
     * @param string $param
     * @param string $useDefaultParam
     * @param string $configPath
     * @return string
     */
    public function getSftpPrivateKey(Zend_Controller_Request_Abstract $request, $param, $useDefaultParam, $configPath)
    {
        $key = $request->getParam($param);
        $useFallback = $request->getParam($useDefaultParam);
        // As the private key isn't re-filled out in the admin forms, the empty
        // value needs to be treated as falling back to the configured value, else
        // there would be no way to test an already saved key.
        if (!$key || $useFallback) {
            $configSource = $this->getConfigSource($request, $useFallback);
            $key = $configSource->getConfig($configPath);
            // As there is not config/default node with a backend_model set for this
            // config node, it will never be auto-magically decrypted by stores.
            // Hence, it always needs to be decrypted here, no matter what the config
            // source is.
            return Mage::helper('core')->decrypt($key);
        }
        return $key;
    }
}
