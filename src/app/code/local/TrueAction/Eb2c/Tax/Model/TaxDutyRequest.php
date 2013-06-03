<?php
/**
 * generates the xml for an EB2C taxdutyrequest.
 * @author mphang
 */
class TrueAction_Eb2c_Tax_Model_TaxDutyRequest extends Mage_Core_Model_Abstract
{
	// TODO: PUT THESE IN THE XML AND LOAD USING A HELPER
	protected static $_apiUrlFormat = 'https://%s.%s.gsipartners.com/%s/stores/%s/%s/%s.%s';
	protected $_env                 = 'developer';
	protected $_region              = 'na';
	protected $_version             = 'v1.10';
	protected $_service             = 'taxes';
	protected $_operation           = 'quote';
	protected $_responseFormat      = 'xml';

	protected $_xml                 = null;
	protected $_shipGroups          = null;
	protected $_shipGroupIdCounter  = 0;

	protected $_destinationId       = 0;
	protected $_billingAddressId    = 0;

	protected function _construct()
	{
		$doc = new TrueAction_Dom_Model_Document('1.0', 'UTF-8');
		$tdRequest = $doc->appendChild($doc->createElement('TaxDutyRequest'));
		$tdRequest->createChild(
			'Currency',
			$this->getShippingAddress()->getQuote()->getCurrencyCode()
		);
		$tdRequest->createChild(
			'BillingInformation',
			null,
			array('ref'=>$this->getBillingAddress()->getId())
		);
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

	protected function _processAddresses($destinationId)
	{
		$shippingAddresses = $this->getShippingAddress()->getQuote()
			->getAllShippingAddresses();
		$shipGroups   = $this->_shipGroups;
		$destinations = $this->_destinations;
		foreach ($shippingAddresses as $addressKey => $address) {
			$mailingAddress = $destinations->createChild('MailingAddress')
				->addAttribute('id', 'dest_' . ++$this->_destinationId, true);
			$personName = $mailingAddress->createChild('PersonName');
			$this->_createPersonName($personName, $address);
			$addressNode = $parent->createChild('Address');
			$this->_buildAddressNode($addressNode, $address);

			$groupedRates = $address->getGroupedAllShippingRates();
			foreach ($groupedRates as $rateKey => $shippingRate) {
				$shipGroup = $shipGroups->createChild('ShipGroup');
				$shipGroup->addIdAttribute('id', "shipGroup_{$addressKey}_{$rateKey}", true);
				$shipGroup->createChild('DestinationTarget')
					->setAttribute('ref', $mailingAddress->getAttribute('id'));
				// TODO: REMOVE ME
				Mage::log('DestinationTarget ref = ' . $mailingAddress->getAttribute('id'));
			}
		}
	}

	/**
	 * assumes $parent is an Address node and populates it with the necessary
	 * child nodes/data from the address.
	 * @param TrueAction_Dom_Document $parent
	 * @param Mage_Sales_Model_Quote_Address $address
	 */
	protected function _buildAddressNode($parent, $address)
	{
		// loop through to get all of the street lines.
		$street = $address->getStreet1();
		$i = 1;
		while ((bool)$street) {
			$parent->createChild('Line' . $i, $street);
			++$i;
			$street = $address->getStreet($i);
		}
		$parent->createChild('City', $address->getCity())
		$parent->createChild('MainDivision', $address->getRegion()->getRegionCode());
		$parent->createChild('CountryCode', $address->getCountryCode());
		$parent->createChild('PostalCode', $address->getPostalCode());
	}

	/**
	 * populates $parent with the necessary nodes for a person's last name first name
	 * using the specified address.
	 * @param TrueAction_Dom_Document $parent
	 * @param Mage_Sales_Model_Quote_Address $address
	 */
	protected function _buildPersonName($parent, $address)
	{
		$parent->createChild('LastName', $address->getLastName());
		$parent->createChild('FirstName', $address->getFirstName());
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

	protected function _getQuote()
	{
		$quote = null;
		if ($this->getShippingAddress()) {
			$quote =  $this->getShippingAddress()->getQuote();
		} elseif ($this->getBillingAddress()) {
			$quote = $this->getBillingAddress()->getQuote();
		}
		return $quote;
	}

	protected function _getShipGroupId()
	{
		return $this->_shipGroupIdCounter++;
	}
}
