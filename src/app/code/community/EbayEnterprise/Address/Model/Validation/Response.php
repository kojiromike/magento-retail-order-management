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
	protected $_logger;
	/** @var EbayEnterprise_Address_Helper_Data */
	protected $_helper;
	/** @var EbayEnterprise_MageLog_Helper_Context */
	protected $_context;

	/**
	 * @param array
	 */
	public function __construct(array $args=[])
	{
		list($this->_helper, $this->_logger, $api, $this->_context) = $this->_checkTypes(
			$this->_nullCoalesce($args, 'helper', Mage::helper('ebayenterprise_address')),
			$this->_nullCoalesce($args, 'logger', Mage::helper('ebayenterprise_magelog')),
			$args['api'],
			$this->_nullCoalesce($args, 'context', Mage::helper('ebayenterprise_magelog/context'))
		);
		$this->_extractResponseData($api);
		$this->_logResultCode();
	}

	/**
	 * Type checks for constructor args array.
	 *
	 * @param EbayEnterprise_Address_Helper_Data
	 * @param EbayEnterprise_MageLog_Helper_Data
	 * @param IBidirectionalApi
	 */
	protected function _checkTypes(
		EbayEnterprise_Address_Helper_Data $helper,
		EbayEnterprise_MageLog_Helper_Data $logger,
		IBidirectionalApi $api,
		EbayEnterprise_MageLog_Helper_Context $context
	) {
		return [$helper, $logger, $api, $context];
	}

	/**
	 * Return the value at field in array if it exists. Otherwise, use the
	 * default value.
	 * @param array      $arr
	 * @param string|int $field Valid array key
	 * @param mixed      $default
	 * @return mixed
	 */
	protected function _nullCoalesce(array $arr, $field, $default)
	{
		return isset($arr[$field]) ? $arr[$field] : $default;
	}

	protected function _extractResponseData(IBidirectionalApi $api)
	{
		$response = $api->getResponseBody();
		return $this->_extractResult($response)->_extractResponseAddresses($response);
	}

	/**
	 * Copy result data from the response payload to data on this object. Needed
	 * to allow object to be stored in the session for use in later requests.
	 *
	 * @return self
	 */
	protected function _extractResult(IValidationReply $response)
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
	protected function _extractResponseAddresses(IValidationReply $response)
	{
		return $this
			->_extractOriginalAddress($response)
			->_extractAddressSuggestions($response)
			->_extractValidAddress($response);
	}

	/**
	 * Use the response to select the address to be used as the valid address.
	 * If there is not a single, valid address, will set the value to null.
	 * This method must be used after extracting all other address data from
	 * the response in order to be able to properly select an address.
	 *
	 * @return self
	 */
	public function _extractValidAddress(IValidationReply $response)
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
	public function _extractOriginalAddress(IValidationReply $response)
	{
		$address = Mage::getModel('customer/address', ['has_been_validated' => true]);
		$this->_helper->transferPhysicalAddressPayloadToAddress(
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
	public function _extractAddressSuggestions(IValidationReply $response)
	{
		$suggestionAddresses = [];
		foreach ($response->getSuggestedAddresses() as $physicalAddress) {
			$address = Mage::getModel('customer/address', ['has_been_validated' => true]);
			$this->_helper->transferPhysicalAddressPayloadToAddress(
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
	protected function _logResultCode()
	{
		$resultCode = $this->getResultCode();
		switch ($resultCode) {
			case IValidationReply::RESULT_VALID:
			case IValidationReply::RESULT_CORRECTED_WITH_SUGGESTIONS:
			case IValidationReply::RESULT_FAILED:
			case IValidationReply::RESULT_NOT_SUPPORTED:
				// Expected result codes for when things are working normally.
				// Nothing of interest to log here.
				break;
			case IValidationReply::RESULT_UNABLE_TO_CONTACT_PROVIDER:
				$logMessage = 'Unable to contact provider';
				$this->_logger->warning($logMessage, $this->_context->getMetaData(__CLASS__));
				break;
			case IValidationReply::RESULT_TIMEOUT:
				$logMessage = 'Provider timed out';
				$this->_logger->warning($logMessage, $this->_context->getMetaData(__CLASS__));
				break;
			case IValidationReply::RESULT_PROVIDER_ERROR:
				$logData = ['provider_error' => $this->_lookupPath('provider_error')];
				$logMessage = 'Provider returned a system error: {provider_error}';
				$this->_logger->warning($logMessage, $this->_context->getMetaData(__CLASS__, $logData));
				break;
			case IValidationReply::RESULT_MALFORMED:
				$logMessage = 'The request message was malformed or contained invalid data';
				$this->_logger->warning($logMessage, $this->_context->getMetaData(__CLASS__));
				break;
			default:
				$logData = ['result_code' => $resultCode];
				$logMessage = 'Response message did not contain a known result code. Result Code: {result_code}';
				$this->_logger->warning($logMessage, $this->_context->getMetaData(__CLASS__, $logData));
				break;
		}
		$logData = ['result_code' => $resultCode, 'validation' => $this->isAddressValid() ? 'valid' : 'invalid'];
		$logMessage = 'Response with status code "{result_code}" is {validation}.';
		$this->_logger->debug($logMessage, $this->_context->getMetaData(__CLASS__, $logData));
		return $this;
	}
}
