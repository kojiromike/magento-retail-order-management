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
	protected $_doc                 = null;
	protected $_shipGroups          = null;
	protected $_shipGroupIdCounter  = 0;

	protected $_destinationId       = 0;
	protected $_billingAddressId    = 0;

	protected function _construct()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$tdRequest = $doc->addElement('TaxDutyRequest')->firstChild;
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

	/**
	 * generates the nodes for the shipgroups and destinations subtrees.
	 */
	protected function _processAddresses()
	{
		$shippingAddresses = $this->getShippingAddress()->getQuote()
			->getAllShippingAddresses();
		$shipGroups   = $this->_shipGroups;
		$destinations = $this->_destinations;
		foreach ($shippingAddresses as $addressKey => $address) {
			$mailingAddress = $this->_buildMailingAddressNode($destinations, $address);

			$groupedRates = $address->getGroupedAllShippingRates();
			foreach ($groupedRates as $rateKey => $shippingRate) {
				$shipGroup = $shipGroups->createChild('ShipGroup');
				$shipGroup->addAttribute('id', "shipGroup_{$addressKey}_{$rateKey}", true)
					->addAttribute('chargeType', strtoupper($shippingRate->getMethod()));
				$shipGroup->createChild('DestinationTarget')
					->setAttribute('ref', $mailingAddress->getAttribute('id'));
				// TODO: REMOVE ME
				Mage::log('DestinationTarget ref = ' . $mailingAddress->getAttribute('id'));
			}
		}
	}

	/**
	 * Populate $parent with nodes using data extracted from the specified address.
	 */
	protected function _buildAddressNode(TrueAction_Dom_Element $parent, Mage_Sales_Model_Quote_Address $address)
	{
		// loop through to get all of the street lines.
		$street = $address->getStreet1();
		$i = 1;
		while ((bool)$street) {
			$parent->createChild('Line' . $i, $street);
			++$i;
			$street = $address->getStreet($i);
		}
		$parent->createChild('City', $address->getCity());
		$parent->createChild('MainDivision', $address->getRegionModel()->getCode());
		$parent->createChild('CountryCode', $address->getCountryCode());
		$parent->createChild('PostalCode', $address->getPostalCode());
	}

	/**
	 * Populate $parent with the nodes for a person's name extracted from the specified address.
	 */
	protected function _buildPersonName(TrueAction_Dom_Element $parent, Mage_Sales_Model_Quote_Address $address)
	{
		$honorific  = $address->getPrefix();
		$middleName = $address->getMiddleName();
		if ($honorific) {
			$parent->createChild('Honorific', $honorific);
		}
		$parent->createChild('LastName', $address->getLastName());
		if ($middleName) {
			$parent->createChild('MiddleName', $middleName);
		}
		$parent->createChild('FirstName', $address->getFirstName());
	}

	/**
	 * shortcut function to get the quote.
	 * @return Mage_Sales_Model_Quote
	 */
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

	/**
	 * builds the MailingAddress node
	 * @return TrueAction_Dom_Element
	 */
	protected function _buildMailingAddressNode(TrueAction_Dom_Element $parent, Mage_Sales_Model_Quote_Address $address)
	{
		$parent->createChild('MailingAddress')
			->setAttribute('id', 'dest_' . ++$this->_destinationId, true);
		$personName = $parent->createChild('PersonName');
		$this->_createPersonName($personName, $address);
		$addressNode = $parent->createChild('Address');
		$this->_buildAddressNode($addressNode, $address);
		return $mailingAddress;
	}
}
