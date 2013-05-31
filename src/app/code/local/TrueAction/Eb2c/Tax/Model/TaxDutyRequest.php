<?php
class TrueAction_Eb2c_Tax_Model_TaxDutyRequest extends Mage_Core_Model_Abstract
{
	protected static $_apiUrlFormat = 'https://%s.%s.gsipartners.com/%s/stores/%s/%s/%s.%s';
	protected $_env                 = 'developer';
	protected $_region              = 'na';
	protected $_version             = 'v1.10';
	protected $_service             = 'taxes';
	protected $_operation           = 'quote';
	protected $_responseFormat      = 'xml';

	protected $_xml                 = null;
	protected $_shipGroups          = null;

	protected function _construct()
	{
		$doc = new TrueAction_Dom_Model_Document('1.0', 'UTF-8');
		$tdRequest = $doc->appendChild(
			$doc->createElement('TaxDutyRequest')
		);
		$tdRequest->createChild(
			'Currency',
			$this->getShippingAddress()->getQuote()->getCurrencyCode()
		);
		$tdRequest->createChild(
			'BillingInformation',
			null,
			array('ref'=>$this->getBillingAddress()->getId())
		)->setIdAttribute('ref', true);
		$shipping = $tdRequest->createChild('Shipping');
		$this->_shipGroups   = $shipping->createChild('ShipGroups');
		$this->_destinations = $shipping->createChild('Destinations');
		$this->_doc = $doc;
	}

	protected function _createApiUrl()
	{
		$this->setApiUrl(sprintf(
			self::$_apiUrlFormat,
			$this->_env,
			$this->_region,
			$this->_version,
			$this->getStoreId(),
			$this->_service,
			$this->_operation,
			$this->_responseFormat
		));
	}

	protected function _getShipGroups()
	{
		$shippingAddresses = $this->getShippingAddress()->getQuote()
			->getAllShippingAddresses();
		$shipGroups = $this->_shipGroups;
		foreach ($shippingAddresses as $address) {
			$groupedRates = $address->getGroupedAllShippingRates();
			$shipGroup = $shipGroups->createChild('ShipGroup');
			$shipGroup->addIdAttribute('id', $address->getId());
		}

	}

	protected function _getDestinations()
	{
		$shippingAddresses = $this->getShippingAddress()->getQuote()
			->getAllShippingAddresses();
		$destinations = $this->_destinations;
		foreach ($shippingAddresses as $address) {
			$groupedRates = $address->getGroupedAllShippingRates();
			$shipGroup = $destinations->createChild('ShipGroup');
			$shipGroup->setNode('ShipGroup')
				->getNode('ShipGroup')
				->addAttribute('ref', $address->getId());
		}
	}
}