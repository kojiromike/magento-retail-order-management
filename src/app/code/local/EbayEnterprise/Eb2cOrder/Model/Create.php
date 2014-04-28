<?php
/**
 * Generates an OrderCreate
 * Some events I *may* need to care about. Not necessarily that I must *listen* for all of these, but that I should be aware of any side effects of these.
 * adminhtml_sales_order_create_process_data_before
 * adminhtml_sales_order_create_process_data
 * sales_convert_quote_to_order
 * sales_convert_quote_item_to_order_item
 * sales_order_place_before
 * checkout_type_onepage_save_order_after
 * checkout_type_multishipping_create_orders_single
 */
class EbayEnterprise_Eb2cOrder_Model_Create
{
	const GENDER_MALE = 1;
	const ESTIMATED_DELIVERY_DATE_MODE = 'LEGACY';
	const ESTIMATED_DELIVERY_DATE_MESSAGETYPE = 'NONE';
	/**
	 * The Shipping Charge Type recognized by the Exchange Platform for flatrate/order level shipping costs
	 */
	const SHIPPING_CHARGE_TYPE_FLATRATE = 'FLATRATE';
	const PAYPAL_TENDER_TYPE = 'PY';
	const BACKEND_ORDER_SOURCE = 'phone';
	const FRONTEND_ORDER_SOURCE = 'web';
	const COOKIES_DELIMITER = ';';

	const GIFT_MESSAGE_PRINTED_CARD_NODE = 'GiftCard';
	const GIFT_MESSAGE_PACKSLIP_NODE = 'Packslip';
	const RETRY_BEGIN_MESSAGE = '[ %s ]: Begin order retry now: %s. Found %s new order to be retried';
	const RETRY_END_MESSAGE = '[ %s ]: Order retried finish at: %s';
	// Response status reported by OrderCreateResponse message orders successfully created
	const RESPONSE_SUCCESS_STATUS = 'SUCCESS';
	// Response status reported by OrderCreateResponse message orders that filed to be created
	const RESPONSE_FAILURE_STATUS = 'FAIL';
	// Translation key for message to show to user when the order create request fails
	const ORDER_CREATE_FAIL_MESSAGE = 'EbayEnterprise_Eb2cOrder_Order_Create_Fail_Message';
	/**
	 * @var Mage_Sales_Model_Order, Magento Order Object
	 */
	protected $_o;
	/**
	 * @var boolean TaxHeaderError - if set on any tax node at all, this is set to true
	 */
	protected $_taxHeaderError = false;

	/**
	 * @var EbayEnterprise_Dom_Document, DOM Object
	 */
	protected $_domRequest;
	/**
	 * @var array, Saves an array of item_id's for use in shipping node
	 */
	protected $_orderItemRef;
	/**
	 * @var EbayEnterprise_Eb2cCore_Model_Config_Registry, config Object
	 */
	protected $_config;
	/**
	 * @var array, hold magento payment map to eb2c
	 */
	protected $_ebcPaymentMethodMap = array(
			'Pbridge_eb2cpayment_cc' => 'CreditCard',
			'Paypal_express' => 'PayPal',
			'PrepaidCreditCard' => 'PrepaidCreditCard', // Not use
			'StoredValueCard' => 'StoredValueCard', // Not use
			'Points' => 'Points', // Not use
			'PrepaidCashOnDelivery' => 'PrepaidCashOnDelivery', // Not use
			'Free' => 'StoredValueCard',
		);
	public function __construct()
	{
		$this->_config = Mage::helper('eb2corder')->getConfig();
		// initiaze these class properties in the constructor.
		$this->_o = null;
		$this->_domRequest = null;
		$this->_orderItemRef = array();
	}
	/**
	 * The event observer version of transmit order
	 * @param Varien_Event_Observer $event, the observer event
	 * @return void
	 */
	public function observerCreate($event)
	{
		$this->buildRequest($event->getEvent()->getOrder())
			->sendRequest();
	}
	/**
	 * Transmit Order
	 * @return self
	 */
	public function sendRequest()
	{
		$uri = Mage::helper('eb2corder')->getOperationUri($this->_config->apiCreateOperation);
		$response = '';
		if ($this->_domRequest instanceof DOMDocument) {
			$response = Mage::getModel('eb2ccore/api')
				->request($this->_domRequest, $this->_config->xsdFileCreate, $uri, $this->_config->serviceOrderTimeout);
		}
		return $this->_processResponse($response);
	}
	/**
	 * Extract the response status from the response xml string. When the service
	 * indicates a success, assume the order has been received by the OMS and
	 * is being processed - place the order in "STATE_PROCESSING". When the
	 * service explicitly indicates a failure, assume the order cannot be placed
	 * into the OMS and cannot be created - throw exception to prevent order
	 * creation. Under any other circumstances, assume some transient error has
	 * prevented the OMS from receiving the order so keep the order to be retried
	 * later - place the order in "STATE_NEW".
	 * @param string $response, the response string xml from eb2c request
	 * @return string, Mage_Sales_Model_Order::STATE_PROCESSING | Mage_Sales_Model_Order::STATE_NEW
	 * @throws EbayEnterprise_Eb2cOrder_Exception_Order_Create_Fail if the response indicates a failure
	 */
	protected function _extractResponseState($response)
	{
		if (trim($response) !== '') {
			$doc = Mage::helper('eb2ccore')->getNewDomDocument();
			$doc->loadXML($response);
			$statusEle = $doc->getElementsByTagName('ResponseStatus')->item(0);
			$status = $statusEle ? strtoupper(trim($statusEle->nodeValue)) : '';
			if ($status === static::RESPONSE_SUCCESS_STATUS) {
				return Mage_Sales_Model_Order::STATE_PROCESSING;
			} elseif ($status === static::RESPONSE_FAILURE_STATUS) {
				throw new EbayEnterprise_Eb2cOrder_Exception_Order_Create_Fail(
					Mage::helper('eb2corder')->__(static::ORDER_CREATE_FAIL_MESSAGE)
				);
			}
		}
		return Mage_Sales_Model_Order::STATE_NEW;
	}
	/**
	 * processing the request response from eb2c by extracting the response message if the current state of the order
	 * doesn't match the extracted state simply set the order state to the extracted state otherwise don't set the state
	 * of the order, then proceed to set the new order custom field 'eb2c_order_create_request' to the xml
	 * OrderCreateRequest message in the EbayEnterprise_Dom_Element object in the class property self::_domRequest,
	 * and then save the order
	 * @param string $response the response string xml from eb2c request
	 * @return self
	 */
	protected function _processResponse($response)
	{
		$state = $this->_extractResponseState($response);
		$this->_o->setState($state, true);
		Mage::helper('ebayenterprise_magelog')->logDebug(
			'[%s] setting order (%s) state to %s',
			array(__METHOD__, $this->_o->getIncrementId(), $state)
		);
		Mage::dispatchEvent('eb2c_order_create_succeeded', array('order' => $this->_o));
		$this->_o->setEb2cOrderCreateRequest($this->_domRequest->saveXML());
		return $this;
	}
	/**
	 * to be implented in the future, if we have gms extension that can provide the url source and type
	 * As this is just a placeholder method for future implementation, no need to cover it
	 * @codeCoverageIgnore
	 * @return array, source data
	 */
	protected function _getSourceData()
	{
		// return empty array since we don't know yet
		return array();
	}
	/**
	 * Build DOM OrderCreateRequest node
	 * @return EbayEnterprise_Dom_Element
	 */
	protected function _buildOrderCreateRequest()
	{
		$orderCreateRequest = $this->_domRequest
			->addElement($this->_config->apiCreateDomRootNodeName, null, $this->_config->apiXmlNs)
			->firstChild;
		$orderCreateRequest->setAttribute('orderType', $this->_config->apiOrderType);
		$orderCreateRequest->setAttribute('requestId', $this->_getRequestId());
		return $orderCreateRequest;
	}
	/**
	 * Build DOM Order node
	 * @param EbayEnterprise_Dom_Element $orderCreateRequest
	 * @return EbayEnterprise_Dom_Element
	 */
	protected function _buildOrder(EbayEnterprise_Dom_Element $orderCreateRequest)
	{
		$order = $orderCreateRequest->createChild('Order');
		$order->setAttribute('levelOfService', $this->_config->apiLevelOfService);
		$order->setAttribute('customerOrderId', $this->_o->getIncrementId());
		$this->_buildCustomer($order->createChild('Customer'));
		$order->createChild('CreateTime', str_replace(' ', 'T', $this->_o->getCreatedAt()));
		return $order;
	}
	/**
	 * Build DOM Order Item node
	 * @param EbayEnterprise_Dom_Element $order
	 * @return self
	 */
	protected function _buildItems(EbayEnterprise_Dom_Element $order)
	{
		$webLineId = 1;
		$orderItems = $order->createChild('OrderItems');
		foreach ($this->_o->getAllVisibleItems() as $item) {
			$this->_buildOrderItem($orderItems->createChild('OrderItem'), $item, $webLineId++);
		}
		return $this;
	}
	/**
	 * Build DOM Order ship node
	 * @param EbayEnterprise_Dom_Element $order
	 * @return self
	 */
	protected function _buildShip(EbayEnterprise_Dom_Element $order)
	{
		// Magento only ever has 1 ship-to per order, so we're building directly into a singular ShipGroup
		$shipping = $order->createChild('Shipping');
		// building shipGroup node
		$this->_buildShipGroup($shipping->createChild('ShipGroups')->createChild('ShipGroup'));
		$this->_buildShipping($shipping);
		return $this;
	}
	/**
	 * Build the "Gifting" nodes for the order or an order item. The $item
	 * Varien_Object will typically be a Mage_Sales_Model_Order or
	 * Mage_Sales_Model_Order_Item, but any Varien_Object could suffice - really
	 * just needs a `getGiftMessageId` method. When applicable, the Gifting
	 * node will be appended to the given DOMElement.
	 * @param  DOMElement    $node
	 * @param  Varien_Object $item
	 * @return self
	 */
	protected function _buildGifting(DOMElement $node, Varien_Object $item)
	{
		$messageId = $item->getGiftMessageId();
		if (!$messageId) {
			return $this;
		}
		$giftMessage = Mage::getModel('giftmessage/message')->load($messageId);
		$type = $this->_o->getGwAddCard() ? self::GIFT_MESSAGE_PRINTED_CARD_NODE : self::GIFT_MESSAGE_PACKSLIP_NODE;

		$gifting = $node->createChild('Gifting');
		$messageNode = $gifting->createChild($type);
		$messageNode->createChild('Message')
			->addChild('To', strip_tags($giftMessage->getSender()))
			->addChild('From', strip_tags($giftMessage->getRecipient()))
			->addChild('Message', strip_tags($giftMessage->getMessage()));
		return $this;
	}
	/**
	 * Build DOM additonal node
	 * @param EbayEnterprise_Dom_Element $order
	 * @return self
	 */
	protected function _buildAdditionalOrderNodes(EbayEnterprise_Dom_Element $order)
	{
		$order->createChild('Currency', $this->_o->getOrderCurrencyCode());
		$order->createChild('TaxHeader')->createChild('Error', ($this->_taxHeaderError == true) ? 'true':'false');
		$order->createChild('Locale', 'en_US');
		if (Mage::app()->getStore()->isAdmin()) {
			$adminSession = Mage::getSingleton('admin/session');
			$adminUser = $adminSession->getUser();
			if ($adminUser && $adminUser->getId()) {
				$csr = $adminSession->getCustomerServiceRep() ?: Mage::getModel('eb2ccsr/representative');
				$repId = $csr->getRepId() ?: $adminUser->getUsername();
				$order->createChild('DashboardRepId', $repId);
			}
		}
		$orderSource = $this->_getSourceData();
		if (!empty($orderSource)) {
			$orderSourceNode = $order->createChild('OrderSource', $orderSource['source']);
			$orderSourceNode->setAttribute('type', $orderSource['type']);
		}
		$order->createChild('OrderHistoryUrl', Mage::helper('eb2corder')->getOrderHistoryUrl($this->_o));
		$order->createChild('OrderTotal', sprintf('%.02f', $this->_o->getGrandTotal()));
		return $this;
	}
	/**
	 * Build DOM for a complete order
	 * @param $orderObject a Mage_Sales_Model_Order
	 * @return self
	 */
	public function buildRequest(Mage_Sales_Model_Order $orderObject)
	{
		$this->_o = $orderObject;
		$this->_domRequest = Mage::helper('eb2ccore')->getNewDomDocument();
		$orderCreateRequest = $this->_buildOrderCreateRequest();
		$order = $this->_buildOrder($orderCreateRequest);
		$this->_buildItems($order)
			->_buildShip($order)
			->_buildPayment($order->createChild('Payment'))
			->_buildAdditionalOrderNodes($order)
			->_buildContext($orderCreateRequest->createChild('Context'));
		return $this;
	}
	/**
	 * Build customer information node
	 * @param DomElement customer	where to place customer info
	 * @return self
	 */
	protected function _buildCustomer(DomElement $customer)
	{
		$cfg = Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getSingleton('eb2ccore/config'));
		$customer->setAttribute('customerId', sprintf('%s%s', $cfg->clientCustomerIdPrefix, $this->_o->getCustomerId()));
		$name = $customer->createChild('Name');
		$name->createChild('Honorific', $this->_o->getCustomerPrefix());
		$name->createChild('LastName', trim($this->_o->getCustomerLastname() . ' ' . $this->_o->getCustomerSuffix()));
		$name->createChild('MiddleName', $this->_o->getCustomerMiddlename());
		$name->createChild('FirstName', $this->_o->getCustomerFirstname());
		if ($this->_o->getCustomerGender()) {
			// Previously tried to pull out the gender text, but that's probably worse, since one could change
			// 	'Male' to 'Boys' (or 'Woman', for that matter) and an invalid or flat-out wrong value would be sent to GSI.
			//	Let's just check the gender value/ option id. If it's 1, male, otherwise, female.
			$genderToSend = ($this->_o->getCustomerGender() == self::GENDER_MALE) ?  'M' : 'F';
			$customer->createChild('Gender', $genderToSend);
		}
		if ($this->_o->getCustomerDob()) {
			$customer->createChild('DateOfBirth', date_format(date_create($this->_o->getCustomerDob()), 'Y-m-d'));
		}
		$customer->createChild('EmailAddress', $this->_o->getCustomerEmail());
		$customer->createChild('CustomerTaxId', $this->_o->getCustomerTaxvat());

		return $this;
	}
	/**
	 * Builds a single Order Item node inside the Order Items array
	 * @param DomElement orderItem
	 * @param Mage_Sales_Model_Order_Item item
	 * @param integer webLineId	identifier to indicate the line item's sequence within the order
	 * @return void
	 */
	protected function _buildOrderItem(DomElement $orderItem, Mage_Sales_Model_Order_Item $item, $webLineId)
	{
		$order = $item->getOrder();
		$quoteId = $order->getQuoteId();
		$itemId = 'item_' . $item->getId();
		$reservationId = (trim($item->getEb2cReservationId()) !== '')? $item->getEb2cReservationId() : Mage::helper('eb2cinventory')->getRequestId($quoteId);
		$this->_orderItemRef[] = $itemId;
		$orderItem->setAttribute('id', $itemId);
		$orderItem->setAttribute('webLineId', $webLineId);
		$orderItem->createChild('ItemId', $item->getSku());
		$orderItem->createChild('Quantity', $item->getQtyOrdered());
		$orderItem->createChild('Description')->createChild('Description', $item->getName());
		$pricing = $orderItem->createChild('Pricing');
		$merchandise = $pricing->createChild('Merchandise');
		$merchandise->createChild('Amount', sprintf('%.02f', $item->getQtyOrdered() * $item->getPrice()));
		if ($item->getDiscountAmount() > 0) {
			$discount = $merchandise
				->createChild('PromotionalDiscounts')
				->createChild('Discount');
			$discount->createChild('Id', 'CHANNEL_IDENTIFIER');	// Spec says this *may* be required, schema validation says it *is* required
			$discount->createChild('Amount', sprintf('%.02f', $item->getDiscountAmount())); // Magento has only 1 discount per line item
		}
		// Tax on the Merchandise:
		$merchTaxFragment = $this->_buildTaxDataNodes(
			$this->getItemTaxQuotes($item, EbayEnterprise_Eb2cTax_Model_Response_Quote::MERCHANDISE), $item
		);
		if ($merchTaxFragment->hasChildNodes()) {
			$merchandise->appendChild($merchTaxFragment);
		}
		$merchandise->createChild('UnitPrice', sprintf('%.02f', $item->getPrice()));
		// End Merchandise
		// Shipping on the orderItem: when flatrate shipping, only the first item should have shipping prices
		// otherwise all items should have it for the shipping price for that item
		if ($this->_getShippingChargeType($this->_o) !== self::SHIPPING_CHARGE_TYPE_FLATRATE || $webLineId === 1) {
			$shipping = $pricing->createChild('Shipping');
			$shipping->createChild('Amount', $this->_getItemShippingAmount($item));
			$shippingTaxFragment = $this->_buildTaxDataNodes(
				$this->getItemTaxQuotes($item, EbayEnterprise_Eb2cTax_Model_Response_Quote::SHIPPING), $item, EbayEnterprise_Eb2cTax_Model_Response_Quote::SHIPPING
			);
			if ($shippingTaxFragment->hasChildNodes()) {
				$shipping->appendChild($shippingTaxFragment);
			}
		}
		// End Shipping
		// Duty on the orderItem:
		$dutyFragment = $this->_buildDuty($item);
		if ($dutyFragment->hasChildNodes()) {
			$pricing->appendChild($dutyFragment);
		}
		// End Duty
		$orderItem->createChild('ShippingMethod', Mage::helper('eb2ccore')->lookupShipMethod($order->getShippingMethod()));
		$this->_buildEstimatedDeliveryDate($orderItem, $item);
		$this->_buildGifting($orderItem, $item);
		$orderItem->createChild('ReservationId', $reservationId);
	}
	/**
	 * build an EstimatedDeliveryDate node.
	 * @param  EbayEnterprise_Dom_Element      $orderItem
	 * @param  Mage_Sales_Model_Order_Item $item
	 * @return self
	 */
	protected function _buildEstimatedDeliveryDate(EbayEnterprise_Dom_Element $orderItem, Mage_Sales_Model_Order_Item $item)
	{
		$edd = $orderItem->createChild('EstimatedDeliveryDate');
		$edd->createChild('DeliveryWindow')
			->addChild('From',
				date_format(date_create($item->getEb2cDeliveryWindowFrom()), 'c')
			)
			->addChild('To',
				date_format(date_create($item->getEb2cDeliveryWindowTo()), 'c')
			);
		$edd->createChild('ShippingWindow')
			->addChild('From',
				date_format(date_create($item->getEb2cShippingWindowFrom()), 'c')
			)
			->addChild('To',
				date_format(date_create($item->getEb2cShippingWindowTo()), 'c')
			);

		$edd->addChild('Mode', self::ESTIMATED_DELIVERY_DATE_MODE)
			->addChild('MessageType', self::ESTIMATED_DELIVERY_DATE_MESSAGETYPE);
		return $this;
	}

	/**
	 * generic method that take product attribute and the product id and return the raw attribute value for the product
	 * @param string $attribute (tax_code, color, brand_name, etc)
	 * @param int $productId the entity_id value of a known product in magento
	 * @return string the product attribute value
	 */
	protected function _getAttributeValueByProductId($attribute, $productId)
	{
		return Mage::getResourceModel('catalog/product')->getAttributeRawValue(
			$productId,
			$attribute,
			Mage::helper('core')->getStoreId()
		);
	}

	/**
	 * Build TaxData nodes for the item
	 * @see  EbayEnterprise_Eb2cTax_Model_Response_Quote for tax types.
	 * @param  EbayEnterprise_Eb2cTax_Model_Resource_Response_Quote_Collection $taxQuotes Collection of tax quotes to build tax nodes for
	 * @param  Mage_Sales_Model_Order_Item $item, the quote item to get the product object from
	 * @return DOMDocumentFragment                  A DOM fragment of the nodes
	 */
	protected function _buildTaxDataNodes(EbayEnterprise_Eb2cTax_Model_Resource_Response_Quote_Collection $taxQuotes, Mage_Sales_Model_Order_Item $item, $taxType = null)
	{
		$taxFragment = $this->_domRequest->createDocumentFragment();
		if ($taxQuotes->count()) {
			$taxData = $taxFragment->appendChild(
				$this->_domRequest->createElement('TaxData', null, $this->_config->apiXmlNs)
			);
			// adding TaxClass node
			if ($taxType === EbayEnterprise_Eb2cTax_Model_Response_Quote::SHIPPING) {
				$taxData->createChild('TaxClass', $this->_config->shippingTaxClass);
			} else {
				$taxData->createChild('TaxClass', $this->_getAttributeValueByProductId('tax_code', $item->getProductId()));
			}

			$taxes = $taxData->createChild('Taxes');
			$calc = Mage::getModel('tax/calculation');
			foreach ($taxQuotes as $taxQuote) {
				if ($this->_taxHeaderError == false && $taxQuote->getTaxHeaderError() == true) {
					$this->_taxHeaderError = true;
				}
				$taxNode = $taxes->createChild('Tax');
				// need to actually get these value from somewhere
				$taxNode->setAttribute('taxType', $taxQuote->getTaxType());
				$taxNode->setAttribute('taxability', $taxQuote->getTaxability());
				$taxNode->createChild('Situs', $taxQuote->getSitus());
				$jurisdiction = $taxNode->createChild('Jurisdiction', $taxQuote->getJurisdiction());
				$jurisdiction->setAttribute('jurisdictionLevel', $taxQuote->getJurisdictionLevel());
				$jurisdiction->setAttribute('jurisdictionId', $taxQuote->getJurisdictionId());
				$imposition = $taxNode->createChild('Imposition', $taxQuote->getImposition());
				$imposition->setAttribute('impositionType', $taxQuote->getImpositionType());
				$taxNode->createChild('EffectiveRate', $calc->round($taxQuote->getEffectiveRate()));
				$taxNode->createChild('TaxableAmount', $calc->round($taxQuote->getTaxableAmount()));
				$taxNode->createChild('CalculatedTax', $calc->round($taxQuote->getCalculatedTax()));
			}
		}
		return $taxFragment;
	}
	/**
	 * Get tax quotes for an item.
	 * @see  EbayEnterprise_Eb2cTax_Model_Response_Quote for available tax types.
	 * @param  Mage_Sales_Model_Order_Item $orderItem The order item to get tax quotes for
	 * @param  int                         $taxType   The type of tax quotes to load
	 * @return EbayEnterprise_Eb2cTax_Model_Resource_Response_Quote_Collection
	 */
	public function getItemTaxQuotes(Mage_Sales_Model_Order_Item $orderItem, $taxType)
	{
		$taxQuotes = Mage::getModel('eb2ctax/response_quote')->getCollection();
		$taxQuotes->addFieldToFilter('quote_item_id', $orderItem->getQuoteItemId())
			->addFieldToFilter('type', $taxType);
		return $taxQuotes;
	}
	/**
	 * Builds the Duty Node for order
	 * @param Mage_Sales_Model_Order_Item $item, the order item object
	 * @return DOMFragment
	 */
	protected function _buildDuty(Mage_Sales_Model_Order_Item $item)
	{
		$dutyFragment = $this->_domRequest->createDocumentFragment();
		$dutyQuotes = $this->getItemTaxQuotes($item, EbayEnterprise_Eb2cTax_Model_Response_Quote::DUTY);
		if ($dutyQuotes->count()) {
			$duty = $dutyFragment->appendChild(
				$this->_domRequest->createElement('Duty', null, $this->_config->apiXmlNs)
			);
			$dutyTotal = 0;
			foreach ($dutyQuotes as $dutyQuote) {
				$dutyTotal += $dutyQuote->getCalculatedTax();
			}
			if ($dutyTotal > 0) {
				$duty->createChild('Amount', $dutyTotal);
				$dutyTax = $this->_buildTaxDataNodes($dutyQuotes, $item);
				if ($dutyTax->hasChildNodes()) {
					$duty->appendChild($dutyTax);
				}
			}
		}
		return $dutyFragment;
	}
	/**
	 * Builds the ShipGroup Node for order
	 * @param DomElement shipGroup Node
	 * @return void
	 */
	protected function _buildShipGroup(DomElement $shipGroup)
	{
		$shipGroup->setAttribute('id', 'shipGroup_1');
		$shipGroup->setAttribute('chargeType', $this->_getShippingChargeType($this->_o));
		$shipGroup->createChild('DestinationTarget')->setAttribute('ref', $this->_config->apiShipGroupDestinationId);
		$orderItems = $shipGroup->createChild('OrderItems');
		foreach ($this->_orderItemRef as $orderItemRef) {
			$shipItem = $orderItems->createChild('Item');
			$shipItem->setAttribute('ref', $orderItemRef);
		}
		$this->_buildGifting($shipGroup, $this->_o);
	}
	/**
	 * Builds the Shipping Node for order
	 * @param DomElement shipping Node to contain shipping and billing info
	 * @return void
	 */
	protected function _buildShipping(DomElement $shipping)
	{
		$destinations = $shipping->createChild('Destinations');
		// Ship-To
		$sa = $this->_o->getShippingAddress();
		$dest = $destinations->createChild('MailingAddress');
		$dest->setAttribute('id', $this->_config->apiShipGroupDestinationId);
		$this->_buildPersonName($dest->createChild('PersonName'), $sa);
		$this->_buildAddress($dest->createChild('Address'), $sa);
		$dest->createChild('Phone', $sa->getTelephone());
		// Bill-To
		$ba = $this->_o->getBillingAddress();
		$billing = $destinations->createChild('MailingAddress');
		$billing->setAttribute('id', $this->_config->apiShipGroupBillingId);
		$this->_buildPersonName($billing->createChild('PersonName'), $ba);
		$this->_buildAddress($billing->createChild('Address'), $ba);
		$billing->createChild('Phone', $ba->getTelephone());
	}
	/**
	 * Creates PersonName element details from an address
	 * @param DomElement personName
	 * @param Mage_Sales_Model_Order_Address address
	 * @return void
 	 */
	protected function _buildPersonName(DomElement $person, Mage_Sales_Model_Order_Address $address)
	{
		$person->createChild('Honorific', $address->getPrefix());
		$person->createChild('LastName', trim($address->getLastname() . ' ' . $address->getSuffix()));
		$person->createChild('MiddleName', $address->getMiddlename());
		$person->createChild('FirstName', $address->getFirstname());
	}
	/**
	 * Creates MailingAddress/Address element details from address
	 * @param DomElement addressElement
	 * @param Mage_Sales_Order_Address address
	 * @return void
	 */
	protected function _buildAddress(DomElement $addressElement, Mage_Sales_Model_Order_Address $address)
	{
		$line = 1;
		foreach ($address->getStreet() as $streetLine) {
			$addressElement->createChild('Line' . $line, $streetLine);
			$line++;
		}
		$addressElement->createChild('City', $address->getCity());
		$addressElement->createChild('MainDivision', $address->getRegionCode());
		$addressElement->createChild('CountryCode', $address->getCountryId());
		$addressElement->createChild('PostalCode', $address->getPostcode());
	}
	/**
	 * Populate the Payment Element of the request
	 * @param DomElement payment
	 * @return self
	 */
	protected function _buildPayment($payment)
	{
		$payment->createChild('BillingAddress')->setAttribute('ref', $this->_config->apiShipGroupBillingId);
		$this->_buildPayments($payment->createChild('Payments'));
		return $this;
	}
	/**
	 * Creates the Tender entries within the Payments Element
	 * @param DomElement payments node into which payment info is placed
	 * @return self
	 */
	protected function _buildPayments(DomElement $payments)
	{
		if (Mage::helper('eb2cpayment')->getConfigModel()->isPaymentEnabled) {
			foreach ($this->_o->getAllPayments() as $payment) {
				$payMethod = $payment->getMethod();
				$payMethodNode = $this->_ebcPaymentMethodMap[ucfirst($payMethod)];
				if ($payMethodNode === 'CreditCard') {
					$payId = $payment->getId();
					$thisPayment = $payments->createChild($payMethodNode);
					$paymentContext = $thisPayment->createChild('PaymentContext');
					$paymentContext->createChild('PaymentSessionId', sprintf('payment%s', $payId));
					$paymentContext->createChild('TenderType', $payment->getAdditionalInformation('tender_code'));
					$paymentContext->createChild('PaymentAccountUniqueId', $payment->getAdditionalInformation('gateway_transaction_id'))
						->setAttribute('isToken', 'true');
					$thisPayment->createChild('PaymentRequestId', sprintf('payment%s', $payId));
					$thisPayment->createChild('CreateTimeStamp', str_replace(' ', 'T', $payment->getCreatedAt()));
					$thisPayment->createChild('Amount', sprintf('%.02f', $this->_o->getGrandTotal()));
					$auth = $thisPayment->createChild('Authorization');
					$responseCode = ($payment->getAdditionalInformation('response_code') === 'AP01' ? 'APPROVED' : 'DECLINED');
					$auth->createChild('ResponseCode', $responseCode);
					$auth->createChild('BankAuthorizationCode', $payment->getAdditionalInformation('bank_authorization_code'));
					$auth->createChild('CVV2ResponseCode', $payment->getAdditionalInformation('cvv2_response_code'));
					$auth->createChild('AVSResponseCode', $payment->getAdditionalInformation('avs_response_code'));
					$auth->createChild('PhoneResponseCode', $payment->getAdditionalInformation('phone_response_code'));
					$auth->createChild('NameResponseCode', $payment->getAdditionalInformation('name_response_code'));
					$auth->createChild('EmailResponseCode', $payment->getAdditionalInformation('email_response_code'));
					$auth->createChild('AmountAuthorized', sprintf('%.02f', $payment->getAmountAuthorized()));
					$thisPayment->createChild('ExpirationDate', $payment->getAdditionalInformation('expiration_date'));
				} elseif ($payMethodNode === 'PayPal') {
					$thisPayment = $payments->createChild($payMethodNode);
					$thisPayment->createChild('Amount', sprintf('%.02f', $this->_o->getGrandTotal()));
					$thisPayment->createChild('AmountAuthorized', sprintf('%.02f', $payment->getAmountAuthorized()));
					$paymentContext = $thisPayment->createChild('PaymentContext');
					$paymentContext->createChild('PaymentSessionId', sprintf('payment%s', $payment->getId()));
					$paymentContext->createChild('TenderType', self::PAYPAL_TENDER_TYPE);
					$paymentContext->createChild('PaymentAccountUniqueId', $payment->getId())->setAttribute('isToken', 'true');
					$thisPayment->createChild('CreateTimeStamp', str_replace(' ', 'T', $payment->getCreatedAt()));
					$thisPayment->createChild('PaymentRequestId', sprintf('payment%s', $payment->getId()));
					$auth = $thisPayment->createChild('Authorization');
					$auth->createChild('ResponseCode', $payment->getCcStatus());
				} elseif ($payMethodNode === 'StoredValueCard') {
					// the payment method is free and there is gift card for the order
					if ($this->_o->getGiftCardsAmount() > 0) {
						$pan = self::getOrderGiftCardPan($this->_o);
						$thisPayment = $payments->createChild($payMethodNode);
						$paymentContext = $thisPayment->createChild('PaymentContext');
						$paymentContext->createChild('PaymentSessionId', sprintf('payment%s', $payment->getId()));
						$paymentContext->createChild('TenderType', Mage::helper('eb2cpayment')->getTenderType($pan));
						$paymentContext->createChild('PaymentAccountUniqueId', $pan)->setAttribute('isToken', 'true');
						$thisPayment->createChild('CreateTimeStamp', str_replace(' ', 'T', $payment->getCreatedAt()));
						$thisPayment->createChild('Amount', sprintf('%.02f', $this->_o->getGiftCardsAmount()));
					} else {
						// there is no gift card for the order and the payment method is free
						$thisPayment = $payments->createChild('PrepaidCreditCard');
						$thisPayment->createChild('Amount', sprintf('%.02f', $this->_o->getGrandTotal()));
					}
				}
			}
		} else {
			$thisPayment = $payments->createChild('PrepaidCreditCard');
			$thisPayment->createChild('Amount', sprintf('%.02f', $this->_o->getGrandTotal()));
		}
		return $this;
	}
	/**
	 * Get order stored value pan.
	 * (This is a public static because I ran into strange test
	 * behavior invoking it as a ReflectionMethod when it was private.)
	 *
	 * @param Mage_Sales_Model_Order $order the order object
	 * @return string the panToken | pan
	 */
	public static function getOrderGiftCardPan(Mage_Sales_Model_Order $order)
	{
		$giftCardData = unserialize($order->getGiftCards());
		if (!empty($giftCardData)) {
			foreach ($giftCardData as $gcData) {
				if (isset($gcData['panToken']) && trim($gcData['panToken']) !== '') {
					return $gcData['panToken'];
				} elseif (isset($gcData['pan']) && trim($gcData['pan']) !== '') {
					// the giftcard data in the admin order creation is expecting the pan key
					// from the unserialize data, not quite sure about the panToken key here
					// perhaps it's a front-end expectation
					return $gcData['pan'];
				}
			}
		}
		return '';
	}
	/**
	 * Populates the Context element
	 * @param DomElement context
	 * @return self
	 */
	protected function _buildContext(DomElement $context)
	{
		$checkout = Mage::getSingleton('checkout/session');
		$this->_buildBrowserData($context->createChild('BrowserData'));
		$context->addChild('TdlOrderTimestamp', $this->_xsdString($checkout->getEb2cFraudTimestamp()));
		$this->_buildSessionInfo($checkout->getEb2cFraudSessionInfo(), $context);
		return $this;
	}
	/**
	 * Populates the Context/BrowserData element
	 * @param DomElement context
	 * @return void
	 */
	protected function _buildBrowserData(DomElement $browserData)
	{
		$checkout = Mage::getSingleton('checkout/session');
		$browserData->addChild('HostName', $this->_xsdString($this->_o->getEb2cFraudHostName(), 50))
			->addChild('IPAddress', $this->_xsdString($this->_o->getEb2cFraudIpAddress()))
			->addChild('SessionId', $this->_xsdString($this->_o->getEb2cFraudSessionId(), 255))
			->addChild('UserAgent', $this->_xsdString($this->_o->getEb2cFraudUserAgent(), 255))
			->addChild('Connection', $this->_xsdString($checkout->getEb2cFraudConnection(), 25));
		$cookieArr = (array) $checkout->getEb2cFraudCookies();
		$cookieStr = implode(self::COOKIES_DELIMITER, array_map(function($name) use ($cookieArr) {
			return "$name={$cookieArr[$name]}";
		}, array_keys($cookieArr)));
		$this->_addElementIfNotEmpty('Cookies', $cookieStr, $browserData, 50);
		$browserData->addChild('JavascriptData', $this->_o->getEb2cFraudJavascriptData())
			->addChild('Referrer', $this->_xsdString($this->_getOrderSource(), 1024));
		// start the HTTPAcceptData subtree
		$browserData->createChild('HTTPAcceptData')
			->addChild('ContentTypes', $this->_xsdString($this->_o->getEb2cFraudContentTypes(), 1024))
			->addChild('Encoding', $this->_xsdString($this->_o->getEb2cFraudEncoding(), 50))
			->addChild('Language', $this->_xsdString($this->_o->getEb2cFraudLanguage(), 255))
			->addChild('CharSet', $this->_xsdString($this->_o->getEb2cFraudCharSet()));
	}
	/**
	 * getting the referrer value as self::BACKEND_ORDER_SOURCE when the order is placed via admin
	 * otherwise theis order is being placed in the frontend return this constant value self::FRONTEND_ORDER_SOURCE
	 * @return string
	 */
	protected function _getOrderSource()
	{
		return (Mage::helper('eb2ccore')->getCurrentStore()->isAdmin()) ? self::BACKEND_ORDER_SOURCE:
			$this->_o->getEb2cFraudReferrer() ?: self::FRONTEND_ORDER_SOURCE;
	}
	/**
	 * Get globally unique request identifier
	 * @return string
	 */
	protected function _getRequestId()
	{
		return uniqid('OCR-');
	}
	/**
	 * Get the Exchange shipping charge type for the given shipping method.
	 * Currently, all shipping charges being sent as 'FLATRATE' for order level shipping charges.
	 * Full implementation supporting order level and item level shippin gamounts
	 * will likely need to look up the shipping method for the order and
	 * make a better determination as to the charge type for the shipping.
	 * @param  Mage_Sales_Model_Order $order Order the shipping charge applies to
	 * @return string Shipping charge type used by Exchange Platform
	 */
	protected function _getShippingChargeType()
	{
		return self::SHIPPING_CHARGE_TYPE_FLATRATE;
	}
	protected function _getItemShippingAmount(Mage_Sales_Model_Order_Item $item)
	{
		if ($this->_getShippingChargeType($item->getOrder()) === self::SHIPPING_CHARGE_TYPE_FLATRATE) {
			return (float) $item->getOrder()->getBaseShippingAmount();
		} else {
			throw new Exception('Non-flatrate shipping calculations are not yet supported.');
		}
	}
	/**
	 * This method will be triggered via a CRONJOB and resend the stored OrderCreateRequest message
	 * for each orders with a state of 'new'
	 * @return void
	 */
	public function retryOrderCreate()
	{
		// first get all order with state equal to 'new'
		$orders = $this->_getNewOrders();

		$logger = Mage::helper('ebayenterprise_magelog');
		$currentDate = Mage::getModel('core/date')->date('m/d/Y H:i:s');
		$logger->logDebug(self::RETRY_BEGIN_MESSAGE, array(__METHOD__, $currentDate, $orders->count()));

		foreach ($orders as $order) {
			// running same code to send request create eb2c orders
			$this->_o = $order;
			$this->_loadRequest($order->getEb2cOrderCreateRequest())
				->sendRequest();
		}
		$orders->save();

		$newDate = Mage::getModel('core/date')->date('m/d/Y H:i:s');
		$logger->logDebug(self::RETRY_END_MESSAGE, array(__METHOD__, $newDate));
	}
	/**
	 * given a string of order create request xml message, assigned an EbayEnterprise_Dom_Document instantiated class
	 * object to the class property self::_domRequest and then loaded the order create request xml to the
	 * EbayEnterprise_Dom_Document object
	 * @param string $requestMessage the order create request xml message and a magento order object
	 * @return self
	 */
	protected function _loadRequest($requestMessage)
	{
		$this->_domRequest = Mage::helper('eb2ccore')->getNewDomDocument();
		$this->_domRequest->loadXML($requestMessage);
		return $this;
	}
	/**
	 * fetch all order with state new
	 * @return Mage_Sales_Model_Order_Resource_Collection
	 */
	protected function _getNewOrders()
	{
		return Mage::getResourceModel('sales/order_collection')
			->addAttributeToSelect('*')
			->addFieldToFilter('state', array('eq' => 'new'))
			->load();
	}
	/**
	 * Parse the credit card expiration date from a pbridge payment
	 * @param string of php-serialized pbridge_data
	 * @return array of pbridge_data
	 */
	protected function _getPbridgeData($pBridgeData)
	{
		$pBridgeArray = unserialize($pBridgeData);
		return $pBridgeArray['pbridge_data'];
	}
	/**
	 * build session info elements if data exists.
	 * @param  array      $data
	 * @param  DOMElement $context
	 * @return self
	 */
	protected function _buildSessionInfo(array $data, DOMElement $context)
	{
		$doc = $context->ownerDocument;
		$frag = $doc->createDocumentFragment();
		foreach ($data as $element => $value) {
			if ($value) {
				$frag->appendChild($doc->createElement($element, $value, $this->_config->apiXmlNs));
			}
		}
		if ($frag->hasChildNodes()) {
			$context->createChild('SessionInfo')
				->appendChild($doc->importNode($frag));
		}
	}
	/**
	 * truncate a $str if it is longer than $maxLength.
	 * if $str evaluates to false, return $default
	 * @param  string $str
	 * @param  int    $maxLength
	 * @param  string $default
	 * @return string
	 */
	protected function _xsdString($str, $maxLength=0, $default='null')
	{
		return ($maxLength ? substr($str, 0, $maxLength) : $str) ?: $default;
	}
	/**
	 * create an child element of $parent if the value is not empty.
	 * @param string  $element
	 * @param string  $value
	 * @param DOMNode $parent
	 * @param int     $maxLength
	 */
	protected function _addElementIfNotEmpty($element, $value, DOMNode $parent, $maxLength=null)
	{
		$val = $this->_xsdString($value, $maxLength, '');
		if ($val) {
			$parent->createChild($element, $val);
		}
		return $this;
	}
}
