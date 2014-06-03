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


class EbayEnterprise_Eb2cCustomerService_Helper_Data
	extends Mage_Core_Helper_Abstract
	implements EbayEnterprise_Eb2cCore_Helper_Interface
{
	/**
	 * @see EbayEnterprise_Eb2cCore_Helper_Interface::getConfigModel
	 * Get a config registry instance with the eb2ccsr config loaded.
	 * @param mixed $store
	 * @return EbayEnterprise_Eb2cCore_Model_Config_Registry
	 */
	public function getConfigModel($store=null)
	{
		return Mage::getModel('eb2ccore/config_registry')
			->setStore($store)
			->addConfigModel(Mage::getSingleton('eb2ccsr/config'));
	}
	/**
	 * Validate the token via the token service.
	 * @param  string $token
	 * @return self
	 * @throws EbayEnterprise_Eb2cCustomerService_Exception_Authentication if the token auth fails
	 */
	public function validateToken($token)
	{
		$request = Mage::getModel('eb2ccsr/token_request', array('token' => $token));
		$response = Mage::getModel('eb2ccsr/token_response');
		// if the CSR login is disabled, don't make a request
		if ($this->getConfigModel()->isCsrLoginEnabled) {
			// Make the token request and pass the response message into the
			// response model.
			$response->setMessage($request->makeRequest());
		}
		// Check the response for the token to be valid. When no request was made,
		// the response will have no message and be considered invalid.
		if ($response->isTokenValid()) {
			Mage::getSingleton('admin/session')->setCustomerServiceRep(
				Mage::getModel('eb2ccsr/representative', $response->getCSRData())
			);
			return $this;
		} else {
			throw new EbayEnterprise_Eb2cCustomerService_Exception_Authentication(
				'Unable to authenticate.'
			);
		}
	}
}
