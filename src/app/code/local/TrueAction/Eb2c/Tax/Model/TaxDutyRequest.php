<?php
class TrueAction_Eb2c_Tax_Model_TaxDutyRequest
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
		$doc = new TrueAction_Eb2c_Core_Model_Dom_Document('1.0', 'UTF-8');
		$tdRequest = $doc->appendChild(
			$doc->createNode('TaxDutyRequest')
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
		$address = $this->getShippingAddress();
		$xml = new Varien_Simplexml_Element('<TaxDutyQuoteRequest/>');
		$xml->setNode(
			'Currency',
			$this->getShippingAddress()->getQuote()->getCurrencyCode()
		)
		$xml->setNode('BillingInformation',	'');
			->getNode('BillingInformation')
			->addAttribute(
				'ref',
				$this->getBillingAddress()->getId()
			);
		$this->_shipGroups = $xml->setNode('Shipping/ShipGroups', '')
			->getNode('Shipping/ShipGroups');
		$this->_xml = $xml;
	}

	public function getShipGroups()
	{
		$shippingAddresses = $this->getShippingAddress()->getQuote()
			->getAllShippingAddresses();
		$shipGroups = new Varien_Simplexml_Element();
		foreach ($shippingAddresses as $address) {
			$groupedRates = $address->getGroupedAllShippingRates();
			$shipGroup = new Varien_Simplexml_Element();
			$shipGroup->setNode('ShipGroup')
				->getNode('ShipGroup')
				->addAttribute('ref', $address->getId());
			$shipGroups->appendChild($shipGroup);
		}

	}
}