<?php
/**
 * generate the xml for an EB2C tax and duty quote request.
 * @author mphang
 */
class TrueAction_Eb2c_Tax_Model_Request extends Mage_Core_Model_Abstract
{
	const EMAIL_MAX_LENGTH         = 70;

	protected $_xml                = '';
	protected $_doc                = null;
	protected $_destinations       = null;
	protected $_shipGroups         = null;
	protected $_tdRequest          = null;
	protected $_billingInfoRef     = '';
	protected $_billingEmailRef    = '';
	protected $_mailingAddressId   = '';
	protected $_emailAddressId     = '';
	protected $_cacheKey           = '';
	protected $_skuLineMap         = array();

	/**
	 * map skus to a quote item
	 * @var array('string' => Mage_Sales_Model_Quote_Item)
	 */
	protected $_skuItemMap         = array();

	/**
	 * generate the request DOMDocument on construction.
	 */
	protected function _construct()
	{
		$this->_setupQuote();
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		if ($this->isUsable()) {
			// TODO: generate the cacheKey as we go along gathering the data for the request and remove this line. this is not adequate.
			$this->_cacheKey   = $this->getQuote()->getId() . '|';
			$tdRequest         = $doc->addElement('TaxDutyQuoteRequest')->firstChild;
			$billingInformation = $tdRequest->addChild(
				'Currency',
				$this->getQuote()->getQuoteCurrencyCode()
			)
				->addChild('VATInclusivePricing', $this->_isVatIncludedInPrice())
				->addChild(
					'CustomerTaxId',
					$this->_checkLength($this->getBillingAddress()->getTaxId(), 0, 40)
				)
				->createChild('BillingInformation');
			$shipping = $tdRequest->createChild('Shipping');
			$this->_tdRequest    = $tdRequest;
			$this->_shipGroups   = $shipping->createChild('ShipGroups');
			$this->_destinations = $shipping->createChild('Destinations');
			$this->_doc          = $doc;
			$this->_buildSkuMaps();
			$this->_processAddresses();
			$billingInformation->setAttribute(
				'ref',
				$this->_billingInfoRef
			);
		}
	}

	/**
	 * determine if the request object has enough data to work with.
	 * @return boolean
	 */
	public function isUsable()
	{
		return $this->getBillingAddress() && $this->getBillingAddress()->getId() &&
			$this->getQuote() && $this->getQuote()->getId() &&
			$this->getQuote()->getItemsSummaryQty();
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
	 * get the DOMDocument for the request.
	 * @return TrueAction_Dom_Document
	 */
	public function getDocument()
	{
		return $this->_doc;
	}

	/**
	 * get the quote item for the sku.
	 * @param stirng $sku
	 * @return Mage_Sales_Model_Quote_Item
	 */
	public function getItemBySku($sku)
	{
		return $this->_skuItemMap[$sku];
	}

	/**
	 * return the skus in the request.
	 * @return array(string)
	 */
	public function getSkus()
	{
		return array_keys($this->_skuLineMap);
	}

	/**
	 * generate the nodes for the shipgroups and destinations subtrees.
	 */
	protected function _processAddresses()
	{
		$shippingAddresses = $this->getQuote()->getAllShippingAddresses();
		$shipGroups   = $this->_shipGroups;
		$destinations = $this->_destinations;
		foreach ($shippingAddresses as $addressKey => $address) {
			$this->_cacheKey .= '|' . $address->getId();
			$mailingAddress = $this->_buildMailingAddressNode($destinations, $address);
			$this->_buildEmailNode($destinations, $address);
			$orderItemsFragment = $this->_doc->createDocumentFragment();
			$orderItems = $orderItemsFragment->appendChild(
				$this->_doc->createElement('Items')
			);
			foreach ($address->getAllVisibleItems() as $addressItem) {
				$this->_addOrderItem($orderItems, $addressItem, $address);
			}
			$groupedRates   = $address->getGroupedAllShippingRates();
			foreach ($groupedRates as $rateKey => $shippingRate) {
				$shippingRate = (is_array($shippingRate)) ? $shippingRate[0] : $shippingRate;
				// FIXME: === always returns false in the following if statement
				if ($address->getShippingMethod() == $shippingRate->getCode()) {
					$shipGroup = $shipGroups->createChild('ShipGroup');
					$shipGroup->addAttribute('id', "shipGroup_{$addressKey}_{$rateKey}", true)
						->addAttribute('chargeType', strtoupper($shippingRate->getMethod()));
					$shipGroup->createChild('DestinationTarget')
						->setAttribute('ref', $this->_mailingAddressId);
					$items = $shipGroup->appendChild($orderItems->cloneNode(true));
				}
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
		$parent->createChild('CountryCode', $address->getCountryId());
		$parent->createChild('PostalCode', $address->getPostcode());
	}

	/**
	 * Populate $parent with the nodes for a person's name extracted from the specified address.
	 */
	protected function _buildPersonName(TrueAction_Dom_Element $parent, Mage_Sales_Model_Quote_Address $address)
	{
		$honorific  = $address->getPrefix();
		$middleName = $address->getMiddlename();
		if ($honorific) {
			$parent->createChild('Honorific', $honorific);
		}
		$parent->createChild('LastName', $address->getLastname());
		if ($middleName) {
			$parent->createChild('MiddleName', $middleName);
		}
		$parent->createChild('FirstName', $address->getFirstname());
	}

	/**
	 * set the quote so that it can be readily available.
	 * @return Mage_Sales_Model_Quote
	 */
	protected function _setupQuote()
	{
		$quote = $this->getQuote();
		if (!$quote) {
			if ($this->getShippingAddress()) {
				$quote =  $this->getShippingAddress()->getQuote();
			} elseif ($this->getBillingAddress()) {
				$quote = $this->getBillingAddress()->getQuote();
			}
		}
		$this->setQuote($quote);
		$this->setBillingAddress($quote->getBillingAddress());
		$this->setShippingAddress($quote->getShippingAddress());
	}

	/**
	 * build the MailingAddress node
	 * @return TrueAction_Dom_Element
	 */
	protected function _buildMailingAddressNode(
		TrueAction_Dom_Element $parent,
		Mage_Sales_Model_Quote_Address $address
	) {
		$this->_mailingAddressId = $address->getId();
		if ($address->getSameAsBilling()) {
			$address = $this->getBillingAddress();
			$this->_billingInfoRef   = $this->_mailingAddressId;
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
	 * build an email address node for the destinations node.
	 * @param  TrueAction_Dom_Element         $parent
	 * @param  Mage_Sales_Model_Quote_Address $address
	 */
	protected function _buildEmailNode(TrueAction_Dom_Element $parent, Mage_Sales_Model_Quote_Address $address)
	{
		if ($address->getSameAsBilling()) {
			$address = $this->getBillingAddress();
			$this->_billingEmailRef = $address->getEmail();
		}
		$this->_emailAddressId = $address->getEmail();
		// do nothing if the email address doesn't meet size requirements.
		$emailStr = $this->_checkLength($address->getEmail(), 1, self::EMAIL_MAX_LENGTH);
		if ($emailStr) {
			$email = $parent->createChild('Email')
				->addAttribute('id', $this->_emailAddressId, true);
			$this->_buildPersonName($email->createChild('Customer'), $address);
			$email->createChild('EmailAddress', $emailStr);
		}
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

	/**
	 * build and append an orderitem node to the parent node.
	 * @param TrueAction_Dom_Element         $parent
	 * @param Mage_Sales_Model_Quote_Item    $item
	 * @param Mage_Sales_Model_Quote_Address $address
	 */
	protected function _addOrderItem(
		TrueAction_Dom_Element $parent,
		Mage_Sales_Model_Quote_Item $item,
		Mage_Sales_Model_Quote_Address $address
	) {
		$sku      = $this->_checkLength($item->getSku(), 1, 20);
		if (is_null($sku)){
			Mage::throwException(sprintf(
				'Mage_Sales_Model_Quote_Item id:%s has an invalid SKU:%s',
				$item->getId(),
				$item->getSku()
			));
		}
		$this->_cacheKey = '|' . $item->getSku() . '|' . $item->getQtyOrdered();
		$orderItem = $parent->createChild('OrderItem')
			->addAttribute('lineNumber', $this->_getLineNumber($item))
			->addChild('ItemId', $item->getSku())
			->addChild('ItemDesc', $this->_checkLength($item->getName(), 0, 12))
			->addChild('HTSCode', $this->_checkLength($item->getHtsCode(), 0, 12))
			->addChild('Quantity', $item->getQtyOrdered())
			->addChild('Pricing');
		$merchandise = $orderItem->setNode('Pricing/Merchandise')
			->addChild('Amount', $item->getRowTotal())
			->addChild('UnitPrice', $item->getBasePrice());
		// taxClass will be gotten from ItemMaster feed field "TaxCode"
		$taxClass = $this->_checkLength(
			$item->getProduct()->getTaxCode(),
			1, 40
		);
		if ($taxClass) {
			$shipping->createChild('TaxClass', $taxClass);
		}

		$shipping = $orderItem->setNode('Pricing/Shipping')
			->addChild('Amount', $address->getShippingAmount());
		$taxClass = $this->_checkLength(
			Mage::getStoreConfig(
				Mage_Tax_Model_Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS,
				$this->getStore()->getId()
			),
			1, 40
		);
		if ($taxClass) {
			$shipping->createChild('TaxClass', $taxClass);
		}
	}

	/**
	 * generate mappings for easy item lookups.
	 * @param  Mage_Sales_Model_Quote_Item $item
	 */
	protected function _buildSkuMaps()
	{
		$quoteItems = $this->getQuote()->getAllVisibleItems();
		foreach ($quoteItems as $key => $quoteItem) {
			$this->_skuLineMap[$quoteItem->getSku()] = $key;
			$this->_skuItemMap[$quoteItem->getSku()] = $quoteItem;
		}
	}

	/**
	 * get an item's position in the order
	 * @param Mage_Sales_Model_Quote_Item $item
	 * @return int
	 */
	protected function _getLineNumber(Mage_Sales_Model_Quote_Item $item)
	{
		return $this->_skuLineMap[$item->getSku()];
	}

	/**
	 * get the taxCode for the item's product.
	 * NOTE: the taxCode should be set by the ItemMaster feed.
	 * @param  Mage_Sales_Model_Quote_Item $item
	 * @return string
	 */
	protected function _getProductTaxCode(Mage_Sales_Model_Quote_Item $item)
	{
		return Mage::getModel('catalog/product')
			->loadById($item->getProductId())
			->getTaxCode();
	}

	/**
	 * determine whether the prices already include VAT.
	 * @return boolean
	 */
	protected function _isVatIncludedInPrice()
	{
		return 0;
	}
}
