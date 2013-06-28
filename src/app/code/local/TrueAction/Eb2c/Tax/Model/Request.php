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
	protected $_shipGroupIds       = array();

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
			$this->_buildSkuMaps();
			$this->_processQuote();
			$this->_buildTaxDutyRequest();
		}
	}

	public function checkAddresses($quote)
	{
		if ($this->getIsMultiShipping() != $quote->getIsMultiShipping()) {
			$this->_hasChanges = true;
		}
		$quoteBillingAddress = $quote->getBillingAddress();
		$this->_hasChanges = $this->_billingInfoRef !== $quoteBillingAddress->getId();
		// first check the billing address
		$billingDestination = isset($this->_destinations[$quoteBillingAddress->getId()]) ?
			$this->_destinations[$quoteBillingAddress->getId()] : !($this->_hasChanges = true);
		if (!$this->_hasChanges) {
			$billAddressData = $this->_extractDestData($quoteBillingAddress);
			$this->_hasChanges = (bool)array_diff_assoc($billingDestination, $billAddressData);
			if (!$this->getIsMultiShipping() && $quote->hasVirtualItems()) {
				$virtualDestination = isset($this->_destinations[$quoteBillingAddress->getEmail()]) ?
					$this->_destinations[$quoteBillingAddress->getEmail()] : !($this->_hasChanges = true);
				$billAddressData = _extractDestData($this->getBillingAddress(), true);
			}
			// if everything was good so far then check the shipping addresses for
			// changes
			if (!$this->hasChanges) {
				// check shipping addresses
				foreach ($quote->getAllShippingAddresses() as $address) {
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

	protected function _processQuote()
	{
		$this->_destinationsChecked = array();
		$quote = $this->getQuote();
		// create the billing address destination node(s)
		$billAddress = $quote->getBillingAddress();
		$this->_billingInfoRef = $billAddress->getId();
		$this->_destinations[$this->_billingInfoRef] = $this->_extractDestData(
			$billAddress
		);
		if ($quote->hasVirtualItems()) {
			$this->_destinations[$billAddress->getEmail()] = $this->_extractDestData(
				$billAddress
			);
		}
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
			foreach ($items as $item) {
				if ($item->getHasChildren() && $item->isChildrenCalculated()) {
					foreach ($item->getChildren() as $child) {
						$isVirtual = $item->getProduct()->getIsVirtual();
						$this->_addToDestination($item, $address, $isVirtual);
					}
				} else {
					$isVirtual = $item->getProduct()->getIsVirtual();
					$this->_addToDestination($item, $address, $isVirtual);
				}
			}
		}
	}

	protected function _processSingleShipQuote($quote)
	{
		$shipAddress = $quote->getShippingAddress();
		$shipAddressRef = $shipAddress->getId();
		$destData = $this->_extractDestData($shipAddress);
		$this->_destinations[$shipAddressRef] = $this->_extractDestData(
			$shipAddress
		);
		$items = $quote->getAllVisibleItems();
		foreach($items as $item) {
			$isVirtual = $item->getProduct()->isVirtual();
			$address   = $isVirtual ? $this->getBillingAddress() : $shipAddress; 			
			if ($item->getHasChildren() && $item->isChildrenCalculated()) {
				foreach ($item->getChildren() as $child) {
					$this->_addToDestination($item, $address, $isVirtual);
				}
			} else {
				$this->_addToDestination($item, $address, $isVirtual);
			}
		}
	}

	protected function _addToDestination($item, $address, $isVirtual = false)
	{
		$addressEmail = $this->_getEmailFromAddress($address);
		$id = $this->_addShipGroup($address, $isVirtual);
		if (!isset($this->shipGroups[$id])) {
			$this->_shipGroups[$id][] = $item->getSku(); 
		}
		$this->_orderItems[$item->getSku()] = $this->_extractItemData($item);
	}

	/**
	 * generate a shipgroup id and map it to a destination id
	 */
	protected function _addShipGroup($address, $isVirtual)
	{
		$rateKey = 'NONE';
		$addressKey = $address->getId();
		if ($address->getAddressType() === 'billing' || $isVirtual) {
			$addressKey = strtoupper($this->_getEmailFromAddress($address));
		} else {
			$groupedRates = $address->getGroupedAllShippingRates();
			if ($groupedRates) {
				foreach ($groupedRates as $rateKey => $shippingRate) {
					$shippingRate = (is_array($shippingRate)) ? $shippingRate[0] : $shippingRate;
					$addressKey = $address->getId();
					if ($address->getShippingMethod() === $shippingRate->getCode()) {
						$rateKey = strtoupper($shippingRate->getMethod());
					}
				}
			}
		}
		$id = "shipGroup_{$addressKey}_{$rateKey}";
		$this->_shipGroups[$addressKey] = $id;
		return $id;
	}

	protected function _getEmailFromAddress($address)
	{
		if ($address->getSameAsBilling() and !$address->getQuote()->getIsMultiShipping()) {
			$email = $address->getQuote()->getBillingAddress()->getEmail();
		} else {
			$email = $address->getEmail();
		}
		return $email;
	}

	protected function _extractDestData($address, $isVirtual = false)
	{
		$id = $address->getId();
		if ($address->getSameAsBilling() && !$this->getIsMultiShipping()) {
			$address = $this->getBillingAddress();
		}
		$data = array(
			'id'         => $id,
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
			$data['city'] = $address->getCity();
			$data['main_division'] = $address->getRegionModel()->getCode();
			$data['country_code'] = $address->getCountryId();
			$data['postal_code'] = $address->getPostcode();
			$data['street'] = $address->getStreet();
		}
		return $data;
	}

	protected function _extractItemData($item)
	{
		$data = array(
			'id' => $item->getId(),
			'line_number' => $this->_getLineNumber($item),
			'item_id' => $item->getSku(),
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
		$taxCode = '';
		if ($item->getProduct()->hasTaxCode()) {
			$taxCode = $item->getProduct()->getTaxCode();
		}
		return $this->_checkLength($taxCode ,1, 40);
	}

	protected function _checkSku($item)
	{
		$newSku      = $this->_checkLength($item['item_id'], 1, 20);
		if (is_null($newSku)){
			Mage::throwException(sprintf(
				'Mage_Sales_Model_Quote_Item id:%s has an invalid SKU:%s',
				$item['id'],
				$item['item_id']
			));
		}
		return $newSku;
	}

	protected function _getShippingTaxClass()
	{
		return $this->_checkLength(
			Mage::getStoreConfig(
				Mage_Tax_Model_Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS,
				$this->getQuote()->getStore()
			),
			1, 40
		);
	}

	protected function _buildTaxDutyRequest()
	{
		$this->_doc->addElement('TaxDutyQuoteRequest', null, $this->_namespaceUri);
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
		$shipGroups   = $shipping->createChild('ShipGroups');
		$destinations = $shipping->createChild('Destinations');
		$this->_processAddresses($destinations, $shipGroups);
		$billingInformation->setAttribute(
			'ref',
			$this->_billingInfoRef
		);
	}

	/**getIsMultiShipping
	 * generate the nodes for the shipgroups and destinations subtrees.
	 */
	protected function _processAddresses($destinationsNode, $shipGroupsNode)
	{
		foreach ($this->_destinations as $destination) {
			if ($destination['is_virtual']) {
				$this->_buildEmailNode($destinationsNode, $destination);
			} else {
				$this->_buildMailingAddressNode($destinationsNode, $destination);
			}
		}
		$orderItemsFragment = $this->_doc->createDocumentFragment();
		$orderItems = $orderItemsFragment->appendChild(
			$this->_doc->createElement('Items')
		);
		// foreach ($this->_shipGroups as $addressKey => $shipGroup) {
		// 		$this->_addOrderItem($orderItem, $orderItems);
		// }
		// $shipGroup->addAttribute('id', "shipGroup_{$addressKey}_{$rateKey}", true)
		// 	->addAttribute('chargeType', strtoupper($shippingRate->getMethod()));
		// $destinationTarget = $shipGroup->createChild('DestinationTarget');
		// $desintationTarget->setAttribute('ref', $this->_shipAddressRef);
		// $items = $shipGroup->appendChild($orderItems->cloneNode(true));
	}

	/**
	 * Populate $parent with nodes using data extracted from the specified address.
	 */
	protected function _buildAddressNode(TrueAction_Dom_Element $parent, $address)
	{
		// loop through to get all of the street lines.
		$streetLines = $address['street'];
		foreach ($streetLines as $streetIndex => $street) {
			$parent->createChild('Line' . ($streetIndex + 1), $street);
		}
		$parent->createChild('City', $address['city']);
		$parent->createChild('MainDivision', $address['main_division']);
		$parent->createChild('CountryCode', $address['country_code']);
		$parent->createChild('PostalCode', $address['postal_code']);
	}

	/**
	 * Populate $parent with the nodes for a person's name extracted from the specified address.
	 */
	protected function _buildPersonName(TrueAction_Dom_Element $parent, $address)
	{
		$honorific  = isset($address['honorific']) ? $address['honorific'] : null;
		$middleName = isset($address['middle_name']) ? $address['middle_name'] : null;
		if ($honorific) {
			$parent->createChild('Honorific', $honorific);
		}
		$parent->createChild('LastName', $address['last_name']);
		if ($middleName) {
			$parent->createChild('MiddleName', $middleName);
		}
		$parent->createChild('FirstName', $address['first_name']);
	}

	/**
	 * build the MailingAddress node
	 * @return TrueAction_Dom_Element
	 */
	protected function _buildMailingAddressNode(
		TrueAction_Dom_Element $parent,
		array $address
	) {
		$this->_shipAddressRef = $address['id'];
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
	 * @param  array $address
	 */
	protected function _buildEmailNode(TrueAction_Dom_Element $parent, array $address)
	{
		$this->_emailAddressId = $address->getEmail();
		// do nothing if the email address doesn't meet size requirements.
		$emailStr = $this->_checkLength($this->_emailAddressId, 1, self::EMAIL_MAX_LENGTH);
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
	protected function _addOrderItem(array $item, TrueAction_Dom_Element $parent) {
		$sku      = $this->_checkSku($item);
		$orderItem = $parent->createChild('OrderItem')
			->addAttribute('lineNumber', $this->_getLineNumber($item))
			->addChild('ItemId', $this->_checkSku($item))
			->addChild('ItemDesc', $this->_checkLength($item['name'], 0, 12))
			->addChild('HTSCode', $this->_checkLength($item['hts_code'], 0, 12))
			->addChild('Quantity', $item['quantity'])
			->addChild('Pricing');
		$merchandise = $orderItem->setNode('Pricing/Merchandise')
			->addChild('Amount', $item['merchandise_amount'])
			->addChild('UnitPrice', $item['merchandise_unit_price']);
		// taxClass will be gotten from ItemMaster feed field "TaxCode"
		$taxClass = $this->_checkLength($item['merchandise_tax_class'], 1, 40);
		if ($taxClass) {
			$shipping->createChild('TaxClass', $taxClass);
		}

		$shipping = $orderItem->setNode('Pricing/Shipping')
			->addChild('Amount', $item['shipping_amount']);
		$taxClass = $this->_checkLength($this->_getShippingTaxClass(), 1, 40);
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
			$this->_skuLineMap[$quoteItem->getSku()] = $quoteItem->getId();
			$this->_skuItemMap[$quoteItem->getSku()] = $quoteItem;
		}
	}

	/**
	 * get an item's position in the order
	 * @param Mage_Sales_Model_Quote_Item $item
	 * @return int
	 */
	protected function _getLineNumber($item)
	{
		return $item['id'];
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
