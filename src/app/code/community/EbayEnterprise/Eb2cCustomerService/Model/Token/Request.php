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


class EbayEnterprise_Eb2cCustomerService_Model_Token_Request
	extends Varien_Object
{
	// API service name
	const SERVICE = 'token';
	// API service operation
	const OPERATION = 'validate';
	// root XML node of the request message
	const ROOT_NODE = 'TokenValidateRequest';
	// config path to special API status handling for this service
	const API_STATUS_HANDLER = 'eb2ccore/customer_service/api/status_handlers';
	/**
	 * Make a request to the token validation service using the token set in
	 * "magic" data. Should return the response message from the service.
	 * @return string
	 */
	public function makeRequest()
	{
		// if there's no token, don't attempt to validate it
		if (!$this->getToken()) {
			Mage::log('[%s] No token to make request for', __CLASS__, Zend_Log::INFO);
			return '';
		}
		$response = Mage::getModel('eb2ccore/api')
			// Use a completely silent handler for this service as we may receive
			// legitimate 400's if we send an invalid token and we don't want CRIT
			// warnings going off for that.
			->setStatusHandlerPath(static::API_STATUS_HANDLER)
			->request(
				$this->_buildRequest(),
				Mage::helper('eb2ccsr')->getConfigModel()->xsdFileTokenValidation,
				$this->_getApiUri()
			);
		return $response;
	}
	/**
	 * Create the XML request message and return it loaded into
	 * a EbayEnterprise_Dom_Document
	 * @return EbayEnterprise_Dom_Document
	 */
	protected function _buildRequest()
	{
		$dom = Mage::helper('eb2ccore')->getNewDomDocument();
		$dom->addElement(
			static::ROOT_NODE, null, Mage::helper('eb2ccsr')->getConfigModel()->apiXmlNs
		)->documentElement->addChild('Token', $this->getToken());
		return $dom;
	}
	/**
	 * Unlike all of the other API endpoints, this one doesn't appear to be
	 * specific to a store id and client it so the endpoint is simply the
	 * domain, version, service and operation.
	 * @return string
	 */
	protected function _getApiUri()
	{
		$config = Mage::helper('eb2ccsr')->getConfigModel();
		return sprintf(
			'https://%s/v%s.%s/%s/%s.xml',
			$config->apiHostname,
			$config->apiMajorVersion,
			$config->apiMinorVersion,
			static::SERVICE,
			static::OPERATION
		);
	}
}
