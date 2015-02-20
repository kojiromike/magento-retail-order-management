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

/**
 * Generate the xml request to the AddressValidation service
 * @method Mage_Customer_Model_Address_Abstract getQuote
 * @method EbayEnterprise_Address_Model_Validation_Request setADdress(Mage_Customer_Model_Address_Abstract $address)
 */
class EbayEnterprise_Address_Model_Validation_Request extends Varien_Object
{
	/** @var EbayEnterprise_Address_Helper_Data */
	protected $_helper;
	/** @var Mage_Customer_Model_Address_Abstract */
	protected $_address;
	/** @var IBidirectionalApi */
	protected $_api;
	/** @var \eBayEnterprise\RetailOrderManagement\Payload\Address\IValidationRequest */
	protected $_requestPayload;

	/**
	 * @param array
	 */
	public function __construct(array $args=[])
	{
		list($this->_helper, $this->_address, $this->_api) = $this->_checkTypes(
			$this->_nullCoalesce($args, 'helper', Mage::helper('ebayenterprise_address')),
			$args['address'],
			$args['api']
		);
		$this->_requestPayload = $this->_api->getRequestBody();
	}

	/**
	 * Type checks for constructor args array.
	 *
	 * @param EbayEnterprise_Address_Helper_Data
	 * @param Mage_Customer_Model_Address_Abstract
	 * @param IBidirectionalApi
	 */
	protected function _checkTypes(
		EbayEnterprise_Address_Helper_Data $helper,
		Mage_Customer_Model_Address_Abstract $address,
		IBidirectionalApi $api
	) {
		return [$helper, $address, $api];
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

	/**
	 * Prepare the request payload - inject address and validation header data.
	 *
	 * @return self
	 */
	public function prepareRequest()
	{
		$this->_helper->transferAddressToPhysicalAddressPayload(
			$this->_address,
			$this->_requestPayload->setMaxSuggestions(
				$this->_helper->getConfigModel()->maxAddressSuggestions
			)
		);
		return $this;
	}

	/**
	 * Get the request request payload to send as the request.
	 *
	 * @return \eBayEnterprise\RetailOrderManagement\Payload\Address\IValidationRequest
	 */
	public function getRequest()
	{
		return $this->_requestPayload;
	}
}
