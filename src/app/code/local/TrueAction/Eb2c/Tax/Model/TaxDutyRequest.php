<?php
/**
 * generates the xml for an EB2C taxdutyrequest.
 * @author mphang
 */
class TrueAction_Eb2c_Tax_Model_TaxDutyRequest extends Mage_Core_Model_Abstract
{
	protected $_xml                = null;
	protected $_doc                = null;
	protected $_destinations       = null;
	protected $_shipGroups         = null;
	protected $_tdRequest          = null;
	protected $_shipGroupIdCounter = 0;
	protected $_destinationId      = 0;
	protected $_billingInfoRef     = '';
	protected $_mailingAddressId   = '';
	protected $_cacheKey           = '';


	protected function _construct()
	{
		$doc               = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$tdRequest         = $doc->addElement('TaxDutyRequest')->firstChild;
		$tdRequest->addChild(
			'Currency',
			$this->_getQuote()->getQuoteCurrencyCode()
		)->addChild(
			'BillingInformation'
		);
		$shipping = $tdRequest->createChild('Shipping');
		$this->_tdRequest    = $tdRequest;
		$this->_shipGroups   = $shipping->createChild('ShipGroups');
		$this->_destinations = $shipping->createChild('Destinations');
		$this->_doc          = $doc;
		$this->_processAddresses();
		$tdRequest->firstChild->nextSibling->setAttribute(
			'ref',
			$this->_billingInfoRef
		);
	}

	/**
	 * determine if the request object has enough data to work with.
	 * @return boolean
	 */
	public function isUsable()
	{
		return (bool)$this->getBillingAddress()->getId() &&
			(bool)$this->getBillingAddress()->getQuote()->getId();
	}

	/**
	 * generate a key to uniquely identify a request.
	 * @return string
	 */
	public function getCacheKey()
	{
		return $this->_cacheKey;
	}

	/**
	 * generates the nodes for the shipgroups and destinations subtrees.
	 */
	protected function _processAddresses()
	{
		$shippingAddresses = $this->_getQuote()->getAllShippingAddresses();
		$shipGroups   = $this->_shipGroups;
		$destinations = $this->_destinations;
		foreach ($shippingAddresses as $addressKey => $address) {
			$mailingAddress = $this->_buildMailingAddressNode($destinations, $address);
			$groupedRates   = $address->getGroupedAllShippingRates();
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
		$streetLines = $address->getStreet();
		foreach ($streetLines as $streetIndex => $street) {
			$parent->createChild('Line' . ($streetIndex + 1), $street);
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
		$this->_mailingAddressId = 'dest_' . $address->getId();
		if ($address->getSameAsBilling()) {
			$address = $this->getBillingAddress();
			$this->_billingInfoRef   = 'dest_' . $address->getId();
			$this->_mailingAddressId = $this->_billingInfoRef;
		}
		$mailingAddress = $parent->createChild('MailingAddress');
		$mailingAddress->setAttribute('id', $this->_mailingAddressId, true);
		$personName = $mailingAddress->createChild('PersonName');
		$this->_buildPersonName($personName, $address);
		$addressNode = $mailingAddress->createChild('Address');
		$this->_buildAddressNode($addressNode, $address);
		return $mailingAddress;
	}
	/**
	 * check $string to see if it conforms to length requirements.
	 * if $truncate is true, truncate the string so that it is never longer than
	 * $maxLength characters.
	 * null is returned if $string does not meet the minimum length requirement
	 * or if $string does not meet the max length requirement and truncate is false.
	 * @param  string  $string
	 * @param  int  $minLength
	 * @param  int  $maxLength
	 * @param  boolean $truncate
	 * @return null|string
	 */
	protected function _checkLength($string, $minLength = null, $maxLength = null, $truncate = true)
	{
		$result = null;
		$len = mb_strlen($string);
		if (is_null($minLength) || $len >= $minLength) {
			$result = $string;
		}
		if ($result && !is_null($maxLength)) {
			if (($len > $maxLength)) {
				$result = ($truncate) ? mb_substr($string, 0, $maxLength) : null;
			}
		}
		return $result;
	}
}
