<?php

use eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * performs gift card tender type lookups using the Retail Order Management
 * API
 *
 */
class EbayEnterprise_GiftCard_Helper_Tendertype
{
    const INVALID_CARD_NUMBER_MESSAGE = 'EbayEnterprise_GiftCard_Invalid_Card_Number';

    /** @var EbayEnterprise_GiftCard_Helper_Data */
    protected $helper;
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $coreHelper;
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $logContext;
    /** @var LoggerInterface */
    protected $apiLogger;
    /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
    protected $config;

    public function __construct(array $init = [])
    {
        list(
            $this->helper,
            $this->coreHelper,
            $this->logger,
            $this->logContext,
            $this->apiLogger,
            $this->config,
        ) = $this->checkTypes(
            $this->nullCoalesce($init, 'helper', Mage::helper('ebayenterprise_giftcard')),
            $this->nullCoalesce($init, 'core_helper', Mage::helper('eb2ccore')),
            $this->nullCoalesce($init, 'logger', Mage::helper('ebayenterprise_magelog')),
            $this->nullCoalesce($init, 'log_context', Mage::helper('ebayenterprise_magelog/context')),
            $this->nullCoalesce($init, 'api_logger', new Psr\Log\NullLogger()),
            $this->nullCoalesce($init, 'config', null)
        );
    }

    protected function checkTypes(
        EbayEnterprise_GiftCard_Helper_Data $helper,
        EbayEnterprise_Eb2cCore_Helper_Data $coreHelper,
        EbayEnterprise_MageLog_Helper_Data $logger,
        EbayEnterprise_MageLog_Helper_Context $logContext,
        LoggerInterface $apiLogger,
        EbayEnterprise_Eb2cCore_Model_Config_Registry $config = null
    ) {
        return func_get_args();
    }

    protected function nullCoalesce(array $arr, $key, $default)
    {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }

    /**
     * Lookup the tender type for given gift card.
     * @param string
     * @param string
     * @param bool
     * @return string
     * @throws EbayEnterprise_GiftCard_Exception_InvalidCardNumber_Exception If card number cannot be retrieved.
     */
    public function lookupTenderType($cardNumber, $currencyCode, $panIsToken=false)
    {
        try {
            $api = $this->getTenderTypeLookupApi();
            return $this->createTenderTypeLookup(
                $cardNumber,
                $currencyCode,
                $api,
                $panIsToken
            )
                ->getTenderType();
        } catch (EbayEnterprise_GiftCard_Exception_TenderTypeLookupFailed_Exception $e) {
            $this->logger->error(
                'Unable to lookup tender type',
                $this->logContext->getMetaData(__CLASS__, [], $e)
            );
            throw Mage::exception(
                'EbayEnterprise_GiftCard_Exception_InvalidCardNumber',
                $this->helper->__(self::INVALID_CARD_NUMBER_MESSAGE, $cardNumber)
            );
        }
    }

    /**
     * get an api object configured to perform a tender type lookup
     * operation.
     *
     * @return IBidirectionalApi
     */
    protected function getTenderTypeLookupApi()
    {
        $config = $this->getConfig();
        return $this->coreHelper->getSdkApi(
            $config->apiService,
            $config->apiOperationTenderTypeLookup,
            [],
            $this->apiLogger
        );
    }

    /**
     * get the giftcard config registry
     *
     * @return EbayEnterprise_Eb2cCore_Model_Config_Registry
     */
    protected function getConfig()
    {
        if (!$this->config) {
            $this->config = $this->helper->getConfigModel();
        }
        return $this->config;
    }

    /**
     * create an object used to fetch the tender type of a giftcard
     *
     * @param string
     * @param string
     * @param IBidirectionalApi
     * @param string
     * @return EbayEnterprise_GiftCard_Model_Tendertype_Lookup
     */
    public function createTenderTypeLookup(
        $cardNumber,
        $currencyCode,
        IBidirectionalApi $api,
        $panIsToken = false
    ) {
        return Mage::getModel(
            'ebayenterprise_giftcard/tendertype_lookup',
            [
                'card_number' => $cardNumber,
                'currency_code' => $currencyCode,
                'api' => $api,
                'pan_is_token' => $panIsToken,
            ]
        );
    }
}
