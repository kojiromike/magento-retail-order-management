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

use eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi;
use eBayEnterprise\RetailOrderManagement\Payload\Address\IValidationReply;

class EbayEnterprise_Address_Model_Validation_Response extends Varien_Object
{
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $logger;
    /** @var EbayEnterprise_Address_Helper_Data */
    protected $helper;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $context;
    /** @var array map of result code to warning message */
    protected $resultCodeWarningMap = [
        // Expected result codes for when things are working normally.
        // Nothing of interest to log here.
        IValidationReply::RESULT_VALID => '',
        IValidationReply::RESULT_CORRECTED_WITH_SUGGESTIONS => '',
        IValidationReply::RESULT_FAILED => '',
        IValidationReply::RESULT_NOT_SUPPORTED => '',
        // Result codes that should emit a warning
        IValidationReply::RESULT_UNABLE_TO_CONTACT_PROVIDER => 'Unable to contact provider',
        IValidationReply::RESULT_TIMEOUT => 'Provider timed out',
        IValidationReply::RESULT_PROVIDER_ERROR => 'Provider had a sytem error',
        IValidationReply::RESULT_MALFORMED => 'The request message was malformed or contained invalid data',
    ];

    /**
     * @param array
     */
    public function __construct(array $args = [])
    {
        list($this->helper, $this->logger, $api, $this->context) = $this->checkTypes(
            $this->nullCoalesce($args, 'helper', Mage::helper('ebayenterprise_address')),
            $this->nullCoalesce($args, 'logger', Mage::helper('ebayenterprise_magelog')),
            $args['api'],
            $this->nullCoalesce($args, 'context', Mage::helper('ebayenterprise_magelog/context'))
        );
        $this->extractResponseData($api);
        $this->logResultCode();
    }

    /**
     * Type checks for constructor args array.
     *
     * @param EbayEnterprise_Address_Helper_Data
     * @param EbayEnterprise_MageLog_Helper_Data
     * @param IBidirectionalApi
     * @param EbayEnterprise_MageLog_Helper_Context
     */
    protected function checkTypes(
        EbayEnterprise_Address_Helper_Data $helper,
        EbayEnterprise_MageLog_Helper_Data $logger,
        IBidirectionalApi $api,
        EbayEnterprise_MageLog_Helper_Context $context
    ) {
        return func_get_args();
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the
     * default value.
     * @param array
     * @param string|int $field Valid array key
     * @param mixed
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }

    protected function extractResponseData(IBidirectionalApi $api)
    {
        $response = $api->getResponseBody();
        return $this->extractResult($response)->extractResponseAddresses($response);
    }

    /**
     * Copy result data from the response payload to data on this object. Needed
     * to allow object to be stored in the session for use in later requests.
     *
     * @return self
     */
    protected function extractResult(IValidationReply $response)
    {
        return $this->addData([
            'result_code' => $response->getResultCode(),
            'is_acceptable' => $response->isAcceptable(),
            'is_valid' => $response->isValid(),
            'result_suggestion_count' => $response->getResultSuggestionCount(),
            'has_suggestions' => $response->hasSuggestions(),
        ]);
    }

    /**
     * Copy address data from the response payload to data on this object. Needed
     * to allow object to be stored in the session for use in later requests.
     *
     * @return self
     */
    protected function extractResponseAddresses(IValidationReply $response)
    {
        return $this
            ->extractOriginalAddress($response)
            ->extractAddressSuggestions($response)
            ->extractValidAddress($response);
    }

    /**
     * Use the response to select the address to be used as the valid address.
     * If there is not a single, valid address, will set the value to null.
     * This method must be used after extracting all other address data from
     * the response in order to be able to properly select an address.
     *
     * @return self
     */
    protected function extractValidAddress(IValidationReply $response)
    {
        $validAddress = null;
        if ($response->isAcceptable()) {
            $validAddress = ($response->getResultSuggestionCount() === 1)
                ? $this->getAddressSuggestions()[0]
                : $this->getOriginalAddress();
        }
        return $this->setData('valid_address', $validAddress);
    }

    /**
     * Use the address validation response to extract the, possibly corrected,
     * address sent to the address validation service, storing it as a Magento
     * address model.
     *
     * @return self
     */
    protected function extractOriginalAddress(IValidationReply $response)
    {
        $address = Mage::getModel('customer/address', ['has_been_validated' => true]);
        $this->helper->transferPhysicalAddressPayloadToAddress(
            $response,
            $address
        );
        return $this->setData('original_address', $address);
    }

    /**
     * Extract any address suggestions from the address validation response,
     * storing them as an array of Magento address models.
     *
     * @return self
     */
    protected function extractAddressSuggestions(IValidationReply $response)
    {
        $suggestionAddresses = [];
        foreach ($response->getSuggestedAddresses() as $physicalAddress) {
            $address = Mage::getModel('customer/address', ['has_been_validated' => true]);
            $this->helper->transferPhysicalAddressPayloadToAddress(
                $physicalAddress,
                $address
            );
            $suggestionAddresses[] = $address;
        }
        return $this->setData('address_suggestions', $suggestionAddresses);
    }

    /**
     * Indicates if the address should be considered valid. In this case,
     * "valid" simply means we should accept the address, address is valid or
     * cannot be validated (provider errors/time out, etc).
     *
     * @return bool
     */
    public function isAddressValid()
    {
        return $this->getIsAcceptable();
    }

    /**
     * Log any unexpected behavior that may indicate issues in the request
     * or the address validation provider.
     *
     * @return self
     */
    protected function logResultCode()
    {
        $resultCode = $this->getResultCode();
        $message = $this->nullCoalesce(
            $this->resultCodeWarningMap,
            $resultCode,
            // message used when the result code is unrecognized
            'Response message did not contain a known result code. Result Code: {result_code}'
        );
        if ($message) {
            $this->logger->warning($message, $this->getMetaData($resultCode));
        }
        $logData = ['result_code' => $resultCode, 'validation' => $this->isAddressValid() ? 'valid' : 'invalid'];
        $logMessage = 'Response with status code "{result_code}" is {validation}.';
        $this->logger->debug($logMessage, $this->context->getMetaData(__CLASS__, $logData));
        return $this;
    }

    /**
     * get meta data used when emitting a warning for the result code
     * @param  string
     * @return array
     */
    protected function getMetaData($resultCode)
    {
        if ($resultCode === IValidationReply::RESULT_UNABLE_TO_CONTACT_PROVIDER ||
            $resultCode === IValidationReply::RESULT_TIMEOUT ||
            $resultCode === IValidationReply::RESULT_MALFORMED ||
            $resultCode === IValidationReply::RESULT_PROVIDER_ERROR
        ) {
            $logData = [];
        } else {
            $logData = ['result_code' => $this->getResultCode()];
        }
        return $this->context->getMetaData(__CLASS__, $logData);
    }
}
