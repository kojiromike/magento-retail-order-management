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
	protected $_tdRequest          = null;
	protected $_namespaceUri       = '';
	protected $_billingInfoRef     = '';
	protected $_billingEmailRef    = '';
	protected $_shipAddressRef     = '';
	protected $_emailAddressId     = '';
	protected $_hasChanges         = '';
	protected $_emailAddresses     = array();
	protected $_skuLineMap         = array();
	protected $_destinations       = array();
	protected $_orderItems         = array();
	protected $_shipGroups         = array();
	protected $_discounts          = array();

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
		$this->_namespaceUri = Mage::helper('tax')->getNamespaceUri();
		$doc                 = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$this->_doc          = $doc;
		$quote               = $this->getQuote();
		if ($quote) {
			$this->setBillingAddress($quote->getBillingAddress());
			$this->setShippingAddress($quote->getShippingAddress());
		}
		if ($this->isValid()) {
			$this->_buildTaxDutyRequest();
		}
	}

	public function checkAddresses($quote)
	{
		if ($this->getIsMultiShipping() != $quote->getIsMultiShipping()) {
			$this->_hasChanges = true;
		}
		// first check the billing address
		$billingDestination = isset($this->_destinations[$this->_billingInfoRef]) ?
			$this->_destinations[$this->_billingInfoRef] : !($this->_hasChanges = true);
		if (!$this->_hasChanges) {
			$billAddressData = $this->_extractDestData($this->getBillingAddress());
			$this->_hasChanges = (bool)array_diff_assoc($billingDestination, $billAddressData);
			if (!$this->getIsMultiShipping() && $quote->hasVirtualItems()) {

			}
			if (!$this->hasChanges) {
				// check shipping addresses
				foreach ($this->getQuote()->getAllShippingAddresses() as $address) {
					$addressData = $this->_extractDestData($address);
					$destination = isset($this->_destinations[$address->getId()]) ?
						$this->_destinations[$address->getId()] : !($this->_hasChanges = true);
				}
			}
		}
		if ($this->_hasChanges) {
			$this->invalidate();
		}
	}
	/**
	 * determine if the request object has enough data to work with.
	 * @return boolean
	 */
	public function isValid()
	{
		return $this->getQuote() && $this->getQuote()->getId() &&
			$this->getBillingAddress() && $this->getBillingAddress()->getId() &&
			$this->getQuote()->getItemsCount();
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
	 * make this request invalid, which will force a new request to
	 * be generated and sent.
	 */
	public function invalidate()
	{
		$this->unsQuote();
	}

	protected function _buildTaxDutyRequest()
	{
		$this->_doc->addElement('TaxDutyQuoteRequest', null, $this->namespaceUri);
		$tdRequest          = $this->_doc->documentElement;
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
		$this->_buildSkuMaps();
		$this->_processQuote();
		$billingInformation->setAttribute(
			'ref',
			$this->_billingInfoRef
		);
	}

	protected function _processQuote()
	{
		$this->_destinationsChecked = array();
		$quote = $this->getQuote();
		// create the billing address destination node(s)
		$billAddress = $quote->getBillingAddress();
		$this->_billingInfoRef = $billAddress->getId();
		$this->_destinations[$this->_billingInfoRef] = $this->_buildMailingAddressFragment(
			$billAddress
		);
		if ($quote->getIsMultiShipping()) {
			$this->_processMultiShippingQuote($quote);
		} else {
			$this->_processSingleShipQuote($quote);
		}
	}

	protected function _processMultiShippingQuote($quote)
	{
		foreach ($quote->getAllShippingAddresses() as $address) {
			$items = $address->getAllVisibleItems();
			if ($item->getHasChildren() && $item->isChildrenCalculated()) {
				foreach ($item->getChildren() as $child) {
					$itemData = $this->_extractItemData($item);
					$isVirtual = $item->getProduct()->getIsVirtual();
					$this->_addToShipGroup($address, $item, $isVirtual);
				}
			} else {
				$itemData = $this->_extractItemData($item);
				$isVirtual = $item->getProduct()->getIsVirtual();
				$this->_addToShipGroup($address, $item, $isVirtual);
			}
		}
	}

	protected function _processSingleShipQuote($quote)
	{
		$shipAddress = $quote->getShippingAddress();
		$this->_shipAddressRef = $shipAddress->getId();
		$destData = $this->_extractDestData($billAddress);
		$this->_destinations[$this->_shipAddressRef] = $this->_extractDestData(
			$shipAddress
		);

		foreach($items as $item) {
			if ($item->getHasChildren() && $item->isChildrenCalculated()) {
				foreach ($item->getChildren() as $child) {
					$itemData = $this->_extractItemData($item);
					$isVirtual = $item->getProduct()->getIsVirtual();
					$this->_addToShipGroup($address, $item, $isVirtual);
				}
			} else {
				$itemData = $this->_extractItemData($item);
				$isVirtual = $item->getProduct()->getIsVirtual();
				$this->_addToShipGroup($address, $item, $isVirtual);
			}
		}
	}

	protected function _addToShipGroup($address, $item, $isVirtual = false)
	{
		$shipGroupId = $address->getId()
		$groupedRates = $address->getGroupedAllShippingRates();
		foreach ($groupedRates as $rateKey => $shippingRate) {
			$shippingRate = (is_array($shippingRate)) ? $shippingRate[0] : $shippingRate;
			// FIXME: === always returns false in the following if statement
			var_dump($address->getShippingMethod());
			var_dump($shippingRate->getCode());
			if ($address->getShippingMethod() == $shippingRate->getCode()) {
				$id = ($isVirtual) ? $address->getEmail() | $address->getId() .
					'_' . $shippingRate->getMethod();
			}
		}
	}

	protected function _getEmailFromAddress($address)
	{

	}

	protected function _extractDestData($address, $isVirtual = false)
	{
		$data = array(
			'is_virtual' => $isVirtual,
			'last_name'  => $address->getLastname(),
			'first_name' => $address->getFirstname()
		);
		$honorific  = $address->getPrefix();
		if ($honorific) {
			$data['honorific'] = $honorific;
		}
		$middleName = $address->getMiddlename();
		if ($middleName) {
			$parent->createChild('middle_name', $middleName);
		}
		if ($isVirtual) {
			$data['email_address'] = $address->getEmail();
		} else {
			$data['city'] = $address->getCity($item);
			$data['main_division'] = $address->getRegionModel()->getCode();
			$data['country_code'] = $address->getCountryId();
			$data['postal_code'] = $address->getPostcode();
			foreach ($streetLines as $streetIndex => $street) {
				$data['line' . $streetIndex + 1] = $street;
			}
		}
		return $data;
	}

	protected function _extractItemData($item)
	{
		$data = array(
			'line_number' => $this->_getLineNumber($item),
			'item_id' => $this->_getItemSku($item),
			'item_desc' => $item->getName(),
			'hts_code' => $item->getHtsCode(),
			'quantity' => $item->getQtyOrdered(),
			'merchandise_amount' => $item->getRowTotal(),
			'merchandise_unit_price' => $item->getBasePrice(),
			'merchandise_tax_class' => $this->_getItemTaxClass($item),
			'shipping_amount' => $item->getRowTotal(),
			'shipping_tax_class' => $this->_getShippingTaxClass(),
		);
		return $data;
	}

	protected function _getItemTaxClass($item)
	{
		return $this->_checkLength($item->getProduct()->getTaxCode(),1, 40);
	}

	protected function _getItemSku($item)
	{
		$sku      = $this->_checkLength($item->getSku(), 1, 20);
		if (is_null($sku)){
			Mage::throwException(sprintf(
				'Mage_Sales_Model_Quote_Item id:%s has an invalid SKU:%s',
				$item->getId(),
				$item->getSku()
			));
		}
		return $sku;
	}

	protected function _getShippingTaxClass()
	{
		return $this->_checkLength(
			// TODO: create a helper function for this value.
			Mage::getStoreConfig(
				Mage_Tax_Model_Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS,
				$this->getStore()
			),
			1, 40
		);
	}

	/**getIsMultiShipping
	 * generate the nodes for the shipgroups and destinations subtrees.
	 */
	protected function _processAddresses()
	{
		$quote = $this->getQuote();
		$destinations = array();
		$addresses = $this->getQuote()->getAllAddresses();
		foreach ($addresses as $address) {
			if ($address->getAddressType() === 'billing') {
				$this->_billingInfoRef = $address->getId();
			}
			$this->_processAddress($address);
		}
	}

	protected function _processAddress($address)
	{
		$addressKey = $address->getId();
		$this->_cacheKey .= '|' . $address->getId();
		$this->_buildMailingAddressNode($this->_destinations, $address);
		$this->_buildEmailNode($this->_destinations, $address);
		$orderItemsFragment = $this->_doc->createDocumentFragment();
		$orderItems = $orderItemsFragment->appendChild(
			$this->_doc->createElement('Items')
		);
		var_dump($this->_getAllAddressItems($address));
		foreach ($this->_getAllAddressItems($address) as $addressItem) {
			var_dump($addressItem->getSku());
			$this->_addOrderItem($item, $orderItems, $address);
		}
		$groupedRates   = $address->getGroupedAllShippingRates();
		foreach ($groupedRates as $rateKey => $shippingRate) {
			$shippingRate = (is_array($shippingRate)) ? $shippingRate[0] : $shippingRate;
			// FIXME: === always returns false in the following if statement
			var_dump($address->getShippingMethod());
			var_dump($shippingRate->getCode());
			if ($address->getShippingMethod() == $shippingRate->getCode()) {
				$shipGroup = $this->_shipGroups->createChild(
					'ShipGroup',
					null,
					null,
					$this->_namespaceUri
				);
				$shipGroup->addAttribute('id', "shipGroup_{$addressKey}_{$rateKey}", true)
					->addAttribute('chargeType', strtoupper($shippingRate->getMethod()));
				$destinationTarget = $shipGroup->createChild('DestinationTarget');
				$desintationTarget->setAttribute('ref', $this->_shipAddressRef);
				$items = $shipGroup->appendChild($orderItems->cloneNode(true));
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
	 * build the MailingAddress node
	 * @return TrueAction_Dom_Element
	 */
	protected function _buildMailingAddressNode(
		TrueAction_Dom_Element $parent,
		Mage_Sales_Model_Quote_Address $address
	) {
		$this->_shipAddressRef = $address->getId();
		if ($address->getSameAsBilling()) {
			$address = $this->getBillingAddress();
		}
		$mailingAddress = $parent->createChild('MailingAddress');
		$mailingAddress->setAttribute('id', $this->_shipAddressRef, true);
		$personName = $mailingAddress->createChild('PersonName');
		$this->_buildPersonName($personName, $address);
		$addressNode = $mailingAddress->createChild('Address');
		$this->_buildAddressNode($addressNode, $address);
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
		}
		$this->_emailAddressId = $address->getEmail();
		// make sure we don't add the an email address more than once
		if (array_search($this->_emailAddressId, $this->_emailAddresses, true) === false) {
			// do nothing if the email address doesn't meet size requirements.
			$emailStr = $this->_checkLength($this->_emailAddressId, 1, self::EMAIL_MAX_LENGTH);
			if ($emailStr) {
				$email = $parent->createChild('Email')
					->addAttribute('id', $this->_emailAddressId, true);
				$this->_buildPersonName($email->createChild('Customer'), $address);
				$email->createChild('EmailAddress', $emailStr);
			}
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
		Mage_Sales_Model_Quote_Item $item,
		TrueAction_Dom_Element $parent,
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
			// TODO: create a helper function for this value.
			Mage::getStoreConfig(
				Mage_Tax_Model_Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS,
				$this->getStore()
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
