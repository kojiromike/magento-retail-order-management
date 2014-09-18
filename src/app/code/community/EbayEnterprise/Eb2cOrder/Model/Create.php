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
	const ESTIMATED_DELIVERY_DATE_MESSAGETYPE = 'DeliveryDate';
	/**
	 * The Shipping Charge Type recognized by the Exchange Platform for flatrate/order level shipping costs
	 */
	const SHIPPING_CHARGE_TYPE_FLATRATE = 'FLATRATE';
	const PAYPAL_TENDER_TYPE = 'PY';
	const PAYPAL_PAYMENT_METHOD = 'PayPal';
	const BACKEND_ORDER_SOURCE = 'phone';
	const FRONTEND_ORDER_SOURCE = 'web';
	const COOKIES_DELIMITER = ';';

	const GIFT_MESSAGE_PRINTED_CARD_NODE = 'GiftCard';
	const GIFT_MESSAGE_PACKSLIP_NODE = 'Packslip';
	const RETRY_BEGIN_MESSAGE = '[ %s ]: Begin order retry at: %s. Found %s order(s) to be retried';
	const RETRY_END_MESSAGE = '[ %s ]: Order retry finished at: %s';
	const RETRY_NOT_FOUND_MESSAGE = '[ %s ]: Original OrderCreateRequest not found: %s';
	// Response status reported by OrderCreateResponse message orders successfully created
	const RESPONSE_SUCCESS_STATUS = 'SUCCESS';
	// Response status reported by OrderCreateResponse message orders that filed to be created
	const RESPONSE_FAILURE_STATUS = 'FAIL';
	// Translation key for message to show to user when the order create request fails
	const ORDER_CREATE_FAIL_MESSAGE = 'EbayEnterprise_Eb2cOrder_Order_Create_Fail_Message';

	const CUSTOM_ATTRIBUTE_NODE = 'CustomAttributes';
	const ATTRIBUTE_NODE = 'Attribute';
	const VALUE_NODE = 'Value';
	const KEY_NODE = 'Key';

	const ORDER_LEVEL = 'order';
	const ITEM_LEVEL = 'item';
	const CONTEXT_LEVEL = 'context';

	const PROMOTIONAL_DISCOUNTS_NODE = 'PromotionalDiscounts';
	const DISCOUNT_NODE = 'Discount';
	const APPLIED_COUNT_ATTRIBUTE = 'appliedCount';
	const DISCOUNT_ID_NODE = 'Id';
	const DISCOUNT_CODE_NODE = 'Code';
	const DISCOUNT_AMOUNT_NODE = 'Amount';
	const DISCOUNT_DESCRIPTION_NODE = 'Description';
	const DISCOUNT_EFFECT_TYPE_NODE = 'EffectType';

	// The initial status of an order
	const STATUS_UNSUBMITTED = 'unsubmitted';
	// The status when ROM order create return success
	const STATUS_PENDING = 'pending';

	/**
	 * @var Mage_Sales_Model_Order, Magento Order Object
	 */
	protected $_o;
	/**
	 * @var bool TaxHeaderError - if set on any tax node at all, this is set to true
	 */
	protected $_taxHeaderError = false;

	/**
	 * @var EbayEnterprise_Dom_Document, DOM Object
	 */
	protected $_domRequest;
	/**
	 * @var array, Saves an array of item_id's for use in shipping node
	 */
	protected $_orderItemRef = array();
	/**
	 * @var array, hold magento payment map to eb2c
	 */
	protected $_ebcPaymentMethodMap = array(
		'Pbridge_eb2cpayment_cc' => 'CreditCard',
		'Paypal_express' => 'PayPal',
		'PrepaidCreditCard' => 'PrepaidCreditCard', // Not used
		'StoredValueCard' => 'StoredValueCard', // Not used
		'Points' => 'Points', // Not used
		'PrepaidCashOnDelivery' => 'PrepaidCashOnDelivery', // Not used
		'Free' => 'StoredValueCard',
	);
	/** @var EbayEnterprise_Eb2cOrder_Helper_Data $_helper **/
	protected $_helper;

	public function __construct()
	{
		$this->_helper = Mage::helper('eb2corder');
	}
	/**
	 * The event observer version of transmit order
	 * @param Varien_Event_Observer $event, the observer event
	 * @return void
	 */
	public function observerCreate($event)
	{
		$order = $event->getEvent()->getOrder();
		// Depending on the payment method use for this order
		// and the configured setting the initial state might not
		// be 'new'. Ensuring the state is 'new' if not reset it back to new.
		// @see Mage_Sales_Model_Order_Payment::place
		if ($order->getState() !== Mage_Sales_Model_Order::STATE_NEW) {
			$order->setState(Mage_Sales_Model_Order::STATE_NEW);
		}
		// always set the initial status of the order to 'unsubmitted'.
		$order->setStatus(static::STATUS_UNSUBMITTED);

		$this->buildRequest($order)
			->sendRequest();
	}
	/**
	 * Transmit Order
	 * @return self
	 */
	public function sendRequest()
	{
		$cfg = $this->_helper->getConfigModel();
		$coreCfg = Mage::helper('eb2ccore')->getConfigModel();
		$uri = $this->_helper->getOperationUri($cfg->apiCreateOperation);
		$response = '';
		if ($this->_domRequest instanceof DOMDocument) {
			$response = Mage::getModel('eb2ccore/api')
				->request($this->_domRequest, $cfg->xsdFileCreate, $uri, $coreCfg->serviceOrderTimeout);
		}
		return $this->_processResponse($response);
	}
	/**
	 * Extract the response status from the response xml string. When the service
	 * indicates a success, assume the order has been received by the OMS and
	 * is being processed - place the order in "STATUS_PENDING". When the
	 * service explicitly indicates a failure, assume the order cannot be placed
	 * into the OMS and cannot be created - throw exception to prevent order
	 * creation. Under any other circumstances, assume some transient error has
	 * prevented the OMS from receiving the order so keep the order to be retried
	 * later - place the order in "STATUS_UNSUBMITTED".
	 * @param string $response, the response string xml from eb2c request
	 * @return string, self::STATUS_PENDING | self::STATUS_UNSUBMITTED
	 * @throws EbayEnterprise_Eb2cOrder_Exception_Order_Create_Fail if the response indicates a failure
	 */
	protected function _extractResponseStatus($response)
	{
		if (trim($response) !== '') {
			$doc = Mage::helper('eb2ccore')->getNewDomDocument();
			$doc->loadXML($response);
			$statusEle = $doc->getElementsByTagName('ResponseStatus')->item(0);
			$status = $statusEle ? strtoupper(trim($statusEle->nodeValue)) : '';
			if ($status === static::RESPONSE_SUCCESS_STATUS) {
				return static::STATUS_PENDING;
			} elseif ($status === static::RESPONSE_FAILURE_STATUS) {
				throw new EbayEnterprise_Eb2cOrder_Exception_Order_Create_Fail(
					$this->_helper->__(static::ORDER_CREATE_FAIL_MESSAGE)
				);
			}
		}
		return static::STATUS_UNSUBMITTED;
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
		$status = $this->_extractResponseStatus($response);
		$this->_o->setStatus($status, true);
		Mage::helper('ebayenterprise_magelog')->logDebug(
			'[%s] setting order (%s) status to %s',
			array(__METHOD__, $this->_o->getIncrementId(), $status)
		);
		Mage::dispatchEvent('eb2c_order_create_succeeded', array('order' => $this->_o));
		$this->_o->setEb2cOrderCreateRequest($this->_domRequest->saveXML());
		return $this;
	}
	/**
	 * to be implemented in the future, if we have GMS extension that can provide the URL source and type
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
		$cfg = $this->_helper->getConfigModel();
		$orderCreateRequest = $this->_domRequest
			->addElement($cfg->apiCreateDomRootNodeName, null, $cfg->apiXmlNs)
			->firstChild;
		$orderCreateRequest->setAttribute('orderType', $cfg->apiOrderType);
		$orderCreateRequest->setAttribute('requestId', Mage::helper('eb2ccore')->generateRequestId('OCR-'));
		return $orderCreateRequest;
	}
	/**
	 * Build DOM Order node
	 * @param EbayEnterprise_Dom_Element $orderCreateRequest
	 * @return EbayEnterprise_Dom_Element
	 */
	protected function _buildOrder(EbayEnterprise_Dom_Element $orderCreateRequest)
	{
		$cfg = $this->_helper->getConfigModel();
		$order = $orderCreateRequest->createChild('Order');
		$order->setAttribute('levelOfService', $cfg->apiLevelOfService);
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
	 * just needs `getGiftMessageId` and `getGwId` methods. When applicable, the Gifting
	 * node will be appended to the given DOMElement.
	 * @param  DOMElement    $node
	 * @param  Varien_Object $item
	 * @param  string $level can either be 'order' or 'item' use to determine at what level the gifting node is being built.
	 * @return self
	 */
	protected function _buildGifting(DOMElement $node, Varien_Object $item, $level='order')
	{
		$messageId = $item->getGiftMessageId();
		$wrapId = $item->getGwId();
		// if the item has neither a message nor gift wrapping, do nothing
		if (!$messageId && !$wrapId) {
			return $this;
		}

		$gifting = $node->createChild('Gifting');
		// building the Gifting/Gift node if there's a valid giftwrapping for this item
		$this->_buildGift($gifting, $item, $wrapId, $level);
		// type of card to add, printed gift card or on the packslip
		$type = $this->_o->getGwAddCard() ? self::GIFT_MESSAGE_PRINTED_CARD_NODE : self::GIFT_MESSAGE_PACKSLIP_NODE;

		// To signal gift wrapping, the 'GiftCard' node needs to be included. When
		// the message type is a gift card, this node will be added as part of the
		// gift message xml, so no need to add it separately. When the type is
		// not gift card or there is no message, the node needs to be added
		// in separately.
		if ($wrapId && !($messageId && $type === self::GIFT_MESSAGE_PRINTED_CARD_NODE)) {
			$gifting->addChild('GiftCard');
		}

		$this->_buildMessage($gifting, $messageId, $type);

		return $this;
	}
	/**
	 * Build the "Gifting/GiftCard/Message" or "Gifting/Gift/Message" or "Gifting/Packslip/Message" nodes.
	 * Any time a message node to be create simply pass the context node, the message id and the type.
	 * @param  DOMElement    $node
	 * @param  int $messageId
	 * @param  string $type optional
	 * @return self
	 */
	protected function _buildMessage(DOMElement $node, $messageId, $type=null)
	{
		if ($messageId) {
			$giftMessage = Mage::getModel('giftmessage/message')->load($messageId);
			if ($giftMessage->getGiftMessageId()) {
				$messageNode = $type? $node->createChild($type) : $node;
				$messageNode->createChild('Message')
					->addChild('To', strip_tags($giftMessage->getSender()))
					->addChild('From', strip_tags($giftMessage->getRecipient()))
					->addChild('Message', strip_tags($giftMessage->getMessage()));
			}
		}
		return $this;
	}
	/**
	 * Build DOM additional node
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
		// according to the XSD the 'CustomAttributes' node at the order level
		// must be after the 'DashboardRepId'
		$this->_buildCustomAttributesByLevel(static::ORDER_LEVEL, $order, $this->_o);

		$orderSource = $this->_getSourceData();
		if (!empty($orderSource)) {
			$orderSourceNode = $order->createChild('OrderSource', $orderSource['source']);
			$orderSourceNode->setAttribute('type', $orderSource['type']);
		}
		$order->createChild('OrderHistoryUrl', $this->_helper->getOrderHistoryUrl($this->_o));
		$order->createChild('OrderTotal', sprintf('%.02f', $this->_o->getGrandTotal()));
		return $this;
	}

	/**
	 * Build DOM for a complete order
	 *
	 * @param Mage_Sales_Model_Order $orderObject a Mage_Sales_Model_Order
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
	 * @param DomElement $customer	where to place customer info
	 * @return self
	 */
	protected function _buildCustomer(DomElement $customer)
	{
		$cfg = Mage::helper('eb2ccore')->getConfigModel();
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
	 *
	 * @param EbayEnterprise_Dom_Element $orderItem
	 * @param Mage_Sales_Model_Order_Item $item
	 * @param integer $webLineId identifier to indicate the line item's sequence within the order
	 * @return self
	 */
	protected function _buildOrderItem(EbayEnterprise_Dom_Element $orderItem, Mage_Sales_Model_Order_Item $item, $webLineId)
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

		// build 'Merchandise/PromotionalDiscounts/Discount' nodes
		$this->_buildDiscount(
			$merchandise, $item, $item->getDiscountAmount(),
			EbayEnterprise_Eb2cTax_Model_Response_Quote::MERCHANDISE_PROMOTION
		);

		// Tax on the Merchandise:
		$merchTaxFragment = $this->_buildTaxDataNodes(
			$this->getItemTaxQuotes($item, EbayEnterprise_Eb2cTax_Model_Response_Quote::MERCHANDISE),
			$this->_getProductTaxCode($item)
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
			// build 'Shipping/PromotionalDiscounts/Discount' nodes
			$this->_buildDiscount(
				$shipping, $item, $this->_o->getShippingDiscountAmount(),
				EbayEnterprise_Eb2cTax_Model_Response_Quote::SHIPPING_PROMOTION
			);

			$shippingTaxFragment = $this->_buildTaxDataNodes(
				$this->getItemTaxQuotes($item, EbayEnterprise_Eb2cTax_Model_Response_Quote::SHIPPING),
				$this->_helper->getConfigModel()->shippingTaxClass
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

		$this->_buildGifting($orderItem, $item, 'item');

		// According to the XSD the 'CustomAttributes' node at the order item level
		// must be inserted before the 'ReservationId' node.
		$this->_buildCustomAttributesByLevel(static::ITEM_LEVEL, $orderItem, $item);

		$orderItem->createChild('ReservationId', $reservationId);
		return $this;
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
	 * Return tax_code attribute for the given sales order item.
	 *
	 * @param Mage_Sales_Model_Order_Item $item the item whose tax code you want
	 * @return string the product attribute value
	 */
	protected function _getProductTaxCode(Mage_Sales_Model_Order_Item $item)
	{
		return Mage::getResourceModel('catalog/product')->getAttributeRawValue(
			$item->getProductId(),
			'tax_code',
			Mage::helper('core')->getStoreId()
		);
	}

	/**
	 * Build TaxData nodes for the collection of quotes we're given
	 * @see  EbayEnterprise_Eb2cTax_Model_Response_Quote for tax types.
	 * @param  EbayEnterprise_Eb2cTax_Model_Resource_Response_Quote_Collection $taxQuotes Collection of tax quotes to build tax nodes
	 * @param  string $taxClass What tax class is used
	 * @return DOMDocumentFragment A DOM fragment of the nodes
	 */
	protected function _buildTaxDataNodes(EbayEnterprise_Eb2cTax_Model_Resource_Response_Quote_Collection $taxQuotes, $taxClass)
	{
		$taxFragment = $this->_domRequest->createDocumentFragment();
		if ($taxQuotes->count()) {
			$taxData = $taxFragment->appendChild(
				$this->_domRequest->createElement('TaxData', null, $this->_helper->getConfigModel()->apiXmlNs)
			);
			$taxData->createChild('TaxClass', $taxClass);
			$taxes = $taxData->createChild('Taxes');
			$calc = Mage::getModel('tax/calculation');
			foreach ($taxQuotes as $taxQuote) {
				if ($this->_taxHeaderError == false && $taxQuote->getTaxHeaderError() == true) {
					$this->_taxHeaderError = true;
				}
				$taxNode = $taxes->createChild('Tax');
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
	 *
	 * @param Mage_Sales_Model_Order_Item $item the order item object
	 * @return DomDocumentFragment
	 */
	protected function _buildDuty(Mage_Sales_Model_Order_Item $item)
	{
		$dutyFragment = $this->_domRequest->createDocumentFragment();
		$dutyQuotes = $this->getItemTaxQuotes($item, EbayEnterprise_Eb2cTax_Model_Response_Quote::DUTY);
		if ($dutyQuotes->count()) {
			$cfg = $this->_helper->getConfigModel();
			$duty = $dutyFragment->appendChild(
				$this->_domRequest->createElement('Duty', null, $cfg->apiXmlNs)
			);
			$dutyTotal = 0;
			foreach ($dutyQuotes as $dutyQuote) {
				$dutyTotal += $dutyQuote->getCalculatedTax();
			}
			if ($dutyTotal > 0) {
				$duty->createChild('Amount', $dutyTotal);
				$dutyTax = $this->_buildTaxDataNodes($dutyQuotes, $this->_getProductTaxCode($item));
				if ($dutyTax->hasChildNodes()) {
					$duty->appendChild($dutyTax);
				}
			}
		}
		return $dutyFragment;
	}
	/**
	 * Builds the ShipGroup Node for order
	 * @param DomElement $shipGroup Node
	 * @return void
	 */
	protected function _buildShipGroup(DomElement $shipGroup)
	{
		$cfg = $this->_helper->getConfigModel();
		$shipGroup->setAttribute('id', 'shipGroup_1');
		$shipGroup->setAttribute('chargeType', $this->_getShippingChargeType($this->_o));
		$shipGroup->createChild('DestinationTarget')->setAttribute('ref', $cfg->apiShipGroupDestinationId);
		$orderItems = $shipGroup->createChild('OrderItems');
		foreach ($this->_orderItemRef as $orderItemRef) {
			$shipItem = $orderItems->createChild('Item');
			$shipItem->setAttribute('ref', $orderItemRef);
		}
		$this->_buildGifting($shipGroup, $this->_o, 'order');
	}
	/**
	 * Builds the Shipping Node for order
	 * @param DomElement $shipping Node to contain shipping and billing info
	 * @return void
	 */
	protected function _buildShipping(DomElement $shipping)
	{
		$cfg = $this->_helper->getConfigModel();
		$destinations = $shipping->createChild('Destinations');
		// Ship-To
		$sa = $this->_o->getShippingAddress();
		$dest = $destinations->createChild('MailingAddress');
		$dest->setAttribute('id', $cfg->apiShipGroupDestinationId);
		$this->_buildPersonName($dest->createChild('PersonName'), $sa);
		$this->_buildAddress($dest->createChild('Address'), $sa);
		$dest->createChild('Phone', $sa->getTelephone());
		// Bill-To
		$ba = $this->_o->getBillingAddress();
		$billing = $destinations->createChild('MailingAddress');
		$billing->setAttribute('id', $cfg->apiShipGroupBillingId);
		$this->_buildPersonName($billing->createChild('PersonName'), $ba);
		$this->_buildAddress($billing->createChild('Address'), $ba);
		$billing->createChild('Phone', $ba->getTelephone());
	}
	/**
	 * Creates PersonName element details from an address
	 *
	 * @param DomElement $person
	 * @param Mage_Sales_Model_Order_Address $address
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
	 *
	 * @param \DomElement $addressElement addressElement
	 * @param Mage_Sales_Model_Order_Address $address
	 * @return void
	 */
	protected function _buildAddress(DomElement $addressElement, Mage_Sales_Model_Order_Address $address)
	{
		$line = 1;
		/** @var array $streetLines (the upstream docblock lies) */
		$streetLines = $address->getStreet();
		foreach ($streetLines as $streetLine) {
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
	 *
	 * @param DomElement $payment
	 * @return self
	 */
	protected function _buildPayment(DomElement $payment)
	{
		$payment->createChild('BillingAddress')->setAttribute(
			'ref', $this->_helper->getConfigModel()->apiShipGroupBillingId
		);
		$this->_buildPayments($payment->createChild('Payments'));
		return $this;
	}
	/**
	 * Get the paypal-specific value for the payment account unique id field.
	 * NOTE: The parameters are purposely left unused to reduce the chances of
	 * 			 needing to change the method signature in the future.
	 * @param  Mage_Sales_Model_Order_Payment $payment
	 * @param  string                         $paymentMethod
	 * @return string
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	protected function _getPaypalAccountUniqueId(Mage_Sales_Model_Order_Payment $payment, $paymentMethod)
	{
		return 'PAYPAL';
	}
	/**
	 * Creates the Tender entries within the Payments Element
	 * @param DomElement $payments node into which payment info is placed
	 * @return self
	 */
	protected function _buildPayments(DomElement $payments)
	{
		if (Mage::helper('eb2cpayment')->getConfigModel()->isPaymentEnabled) {
			foreach ($this->_o->getAllPayments() as $payment) {
				$payMethod = $payment->getMethod();
				$payMethodNode = $this->_ebcPaymentMethodMap[ucfirst($payMethod)];
				if ($payMethodNode === 'CreditCard') {
					$thisPayment = $payments->createChild($payMethodNode);
					$paymentContext = $thisPayment->createChild('PaymentContext');
					$paymentContext->createChild('PaymentSessionId', $this->_o->getIncrementId());
					$paymentContext->createChild('TenderType', $payment->getAdditionalInformation('tender_code'));
					$paymentContext->createChild('PaymentAccountUniqueId', $payment->getAdditionalInformation('gateway_transaction_id'))
						->setAttribute('isToken', 'true');
					$this->_addPaymentRequestId($thisPayment, $this->_getPaymentRequestId($payment));
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
					$paymentContext->createChild('PaymentSessionId', $this->_o->getIncrementId());
					$paymentContext->createChild('TenderType', self::PAYPAL_TENDER_TYPE);
					$paymentContext->createChild('PaymentAccountUniqueId', $this->_getPaypalAccountUniqueId($payment, $payMethod))
						->setAttribute('isToken', 'true');
					$thisPayment->createChild('CreateTimeStamp', str_replace(' ', 'T', $payment->getCreatedAt()));
					$this->_addPaymentRequestId($thisPayment, $this->_getPaymentRequestId($payment));
					$auth = $thisPayment->createChild('Authorization');
					$auth->createChild('ResponseCode', $this->_getResponseCode($payment, $payMethodNode));
				} elseif ($payMethodNode === 'StoredValueCard') {
					// the payment method is free and there is gift card for the order
					if ($this->_o->getGiftCardsAmount() > 0) {
						$thisPayment = $payments->createChild($payMethodNode);
						$paymentContext = $thisPayment->createChild('PaymentContext');
						$paymentContext->createChild('PaymentSessionId', $this->_o->getIncrementId());
						// this **must always** use the raw PAN to be able to look up the tender type
						$paymentContext->createChild(
							'TenderType',
							Mage::helper('eb2cpayment')->getTenderType($this->_getOrderGiftCardPan($this->_o))
						);
						// this **must always** be the PAN token for the OMS to be able to issue adjustments
						$paymentContext->createChild('PaymentAccountUniqueId', $this->_getOrderGiftCardPan($this->_o, true))
							->setAttribute('isToken', 'true');
						$this->_addPaymentRequestId($thisPayment, $this->_getOrderGiftCardRequestId($this->_o));
						$thisPayment->createChild('CreateTimeStamp', str_replace(' ', 'T', $payment->getCreatedAt()));
						$thisPayment->createChild('Pin', $this->_getOrderGiftCardPin($this->_o));
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
	 * Add non-null payment request ids to the payment node. Do nothing if no
	 * payment request id is provided.
	 * @param EbayEnterprise_Dom_Element $paymentNode
	 * @param string|null $paymentRequestId
	 * @return self
	 */
	protected function _addPaymentRequestId(EbayEnterprise_Dom_Element $paymentNode, $paymentRequestId=null)
	{
		if ($paymentRequestId) {
			$paymentNode->createChild('PaymentRequestId', $paymentRequestId);
		}
		return $this;
	}
	/**
	 * Get the request id of the request made to authorize the payment
	 * @param  Mage_Payment_Model_Info $payment
	 * @return string|null Will return null if no payment request id was captured
	 */
	protected function _getPaymentRequestId(Mage_Payment_Model_Info $payment)
	{
		return $payment->getAdditionalInformation('request_id');
	}
	/**
	 * Get order stored value pan. This can get either the raw PAN - the
	 * default behavior - or the tokenized PAN. If there are no gift cards or
	 * the gift card does not have the requested data, this method will return
	 * an empty string.
	 * @param Mage_Sales_Model_Order $order the order object
	 * @param bool $useToken true to get tokenized PAN, false to get raw PAN
	 * @return string
	 */
	protected function _getOrderGiftCardPan(Mage_Sales_Model_Order $order, $useToken=false)
	{
		return $this->_getOrderGiftCardData($order, $useToken ? 'panToken' : 'pan');
	}
	/**
	 * Get order stored value pin. If there is no gift card or no pin within the
	 * gift card data, return an empty string.
	 * @param Mage_Sales_Model_Order $order the order object
	 * @return string
	 */
	protected function _getOrderGiftCardPin(Mage_Sales_Model_Order $order)
	{
		return $this->_getOrderGiftCardData($order, 'pin');
	}
	/**
	 * Get the request id of the request that redeemed the SVC card. Request id
	 * stored in the gift card data's request_id key/value pair.
	 * @param Mage_Sales_Model_Order $order
	 * @return string
	 */
	protected function _getOrderGiftCardRequestId(Mage_Sales_Model_Order $order)
	{
		return $this->_getOrderGiftCardData($order, 'requestId');
	}
	/**
	 * Get the value of the gift card data key/value pair identified by a given
	 * key. Returns the first non-empty value found in all sets of gift card
	 * data on the order.
	 * @param  Mage_Sales_Model_Order $order
	 * @param  string $gcDataKey gift card data key
	 * @return string
	 */
	protected function _getOrderGiftCardData(Mage_Sales_Model_Order $order, $gcDataKey)
	{
		$giftCardData = unserialize($order->getGiftCards());
		if (!empty($giftCardData)) {
			foreach ($giftCardData as $gcData) {
				$val = isset($gcData[$gcDataKey]) ? $gcData[$gcDataKey] : null;
				if ($val) {
					return $val;
				}
			}
		}
		return '';
	}
	/**
	 * Populates the Context element
	 *
	 * @param EbayEnterprise_Dom_Element $context
	 * @return self
	 */
	protected function _buildContext(EbayEnterprise_Dom_Element $context)
	{
		$checkout = Mage::getSingleton('checkout/session');
		$this->_buildBrowserData($context->createChild('BrowserData'));
		$context->addChild('TdlOrderTimestamp', $this->_restrictText($checkout->getEb2cFraudTimestamp()));
		$this->_buildSessionInfo($checkout->getEb2cFraudSessionInfo(), $context)
			->_buildPayPalPayerInfo($context);

		// According to the XSD the 'CustomAttributes' node at the order context
		// level must be inserted as the last node just after the 'PayPalPayerInfo' node
		$this->_buildCustomAttributesByLevel(static::CONTEXT_LEVEL, $context, $this->_o);
		return $this;
	}

	/**
	 * Populates the Context/BrowserData element
	 *
	 * @param DomElement $browserData
	 * @return void
	 */
	protected function _buildBrowserData(DomElement $browserData)
	{
		$checkout = Mage::getSingleton('checkout/session');
		$browserData->addChild('HostName', $this->_restrictText($this->_o->getEb2cFraudHostName(), 50))
			->addChild('IPAddress', $this->_restrictText($this->_o->getEb2cFraudIpAddress(), 45))
			->addChild('SessionId', $this->_restrictText($this->_o->getEb2cFraudSessionId(), 255))
			->addChild('UserAgent', $this->_restrictText($this->_o->getEb2cFraudUserAgent(), 255))
			->addChild('Connection', $this->_restrictText($checkout->getEb2cFraudConnection(), 25));
		$cookieArr = (array) $checkout->getEb2cFraudCookies();
		$cookieStr = implode(self::COOKIES_DELIMITER, array_map(function($name) use ($cookieArr) {
				return "$name={$cookieArr[$name]}";
			}, array_keys($cookieArr)));
		$this->_addElementIfNotEmpty('Cookies', $cookieStr, $browserData, 50);
		$browserData->addChild('JavascriptData', $this->_o->getEb2cFraudJavascriptData())
			->addChild('Referrer', $this->_restrictText($this->_getOrderSource(), 1024));
		// start the HTTPAcceptData subtree
		$browserData->createChild('HTTPAcceptData')
			->addChild('ContentTypes', $this->_restrictText($this->_o->getEb2cFraudContentTypes(), 1024, null))
			->addChild('Encoding', $this->_restrictText($this->_o->getEb2cFraudEncoding(), 50, null))
			->addChild('Language', $this->_restrictText($this->_o->getEb2cFraudLanguage(), 255, null))
			->addChild('CharSet', $this->_restrictText($this->_o->getEb2cFraudCharSet(), 50, null));
	}
	/**
	 * getting the referrer value as self::BACKEND_ORDER_SOURCE when the order is placed via ADMIN
	 * otherwise this order is being placed in the FRONTEND return this constant value self::FRONTEND_ORDER_SOURCE
	 * @return string
	 */
	protected function _getOrderSource()
	{
		return (Mage::helper('eb2ccore')->getCurrentStore()->isAdmin()) ? self::BACKEND_ORDER_SOURCE:
			$this->_o->getEb2cFraudReferrer() ?: self::FRONTEND_ORDER_SOURCE;
	}

	/**
	 * Get the Exchange shipping charge type for the given shipping method.
	 * Currently, all shipping charges being sent as 'FLATRATE' for order level shipping charges.
	 * Full implementation supporting order level and item level shipping amounts
	 * will likely need to look up the shipping method for the order and
	 * make a better determination as to the charge type for the shipping.
	 *
	 * @return string Shipping charge type used by Exchange Platform
	 */
	protected function _getShippingChargeType()
	{
		return self::SHIPPING_CHARGE_TYPE_FLATRATE;
	}

	/**
	 * @param Mage_Sales_Model_Order_Item $item
	 * @return float
	 * @throws Mage_Core_Exception
	 */
	protected function _getItemShippingAmount(Mage_Sales_Model_Order_Item $item)
	{
		if ($this->_getShippingChargeType($item->getOrder()) === self::SHIPPING_CHARGE_TYPE_FLATRATE) {
			return (float) $item->getOrder()->getBaseShippingAmount();
		} else {
			throw Mage::exception('Mage_Core', 'Non-flatrate shipping calculations are not yet supported.');
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
			$xmlCreateRequest = $order->getEb2cOrderCreateRequest();
			if (empty($xmlCreateRequest)) {
				// Original request empty, log at Warn level and move on
				$logger->logWarn(self::RETRY_NOT_FOUND_MESSAGE, array(__METHOD__, $order->getIncrementId()));
			} else {
				// running same code to send request create eb2c orders
				$this->_o = $order;
				$this->_loadRequest($xmlCreateRequest)->sendRequest();
			}
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
	 * Fetch all orders with state 'new' and status 'unsubmitted'.
	 * @return Mage_Sales_Model_Order_Resource_Collection
	 */
	protected function _getNewOrders()
	{
		return Mage::getResourceModel('sales/order_collection')
			->addAttributeToSelect('*')
			->addFieldToFilter('state', array('eq' => Mage_Sales_Model_Order::STATE_NEW))
			->addFieldToFilter('status', array('eq' => static::STATUS_UNSUBMITTED));
	}
	/**
	 * Parse the credit card expiration date from a pbridge payment
	 *
	 * @param string $pBridgeData (serialized php data)
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
		$cfg = $this->_helper->getConfigModel();
		$doc = $context->ownerDocument;
		$frag = $doc->createDocumentFragment();
		foreach ($data as $element => $value) {
			if ($value) {
				$frag->appendChild($doc->createElement($element, $value, $cfg->apiXmlNs));
			}
		}
		if ($frag->hasChildNodes()) {
			$context->createChild('SessionInfo')
				->appendChild($doc->importNode($frag));
		}
		return $this;
	}
	/**
	 * Create new DOMText object with value of parameter $str or a truncated
	 * value of parameter $str to match XSD requirement which is defined base on
	 * the passed in $maxLength parameter where if the $str parameter is longer
	 * than the $maxLength parameter otherwise use the $default parameter.
	 * @param  string $str
	 * @param  int    $maxLength
	 * @param  string $default
	 * @return DOMText
	 */
	protected function _restrictText($str, $maxLength=0, $default='')
	{
		return Mage::helper('eb2ccore')->getNewDomText(
			($maxLength ? substr($str, 0, $maxLength) : $str) ?: $default
		);
	}

	/**
	 * create an child element of $parent if the value is not empty.
	 *
	 * @param string $element
	 * @param string $value
	 * @param DOMNode $parent
	 * @param int $maxLength
	 * @return self
	 */
	protected function _addElementIfNotEmpty($element, $value, DOMNode $parent, $maxLength=null)
	{
		if (!empty($value)) {
			$parent->createChild($element, $this->_restrictText($value, $maxLength, ''));
		}
		return $this;
	}

	/**
	 * Build custom attribute node for the pass in level.
	 * There are three order levels and they are defined in the class constants as
	 * (static::ORDER_LEVEL, static::ITEM_LEVEL, and static::CONTEXT_LEVEL), when
	 * a known level is given a concrete custom attribute class will be instantiate
	 * with an extractData method that know which custom attribute level to extract
	 * data from. The extracted Data will be used to construct the 'Key' and 'Value'
	 * node inside the 'CustomAttributes' outer node.
	 *
	 * @param string $level (static::ORDER_LEVEL, static::ITEM_LEVEL, or static::CONTEXT_LEVEL)
	 * @param EbayEnterprise_Dom_Element $element
	 * @param Varien_Object $item can in object that inherited the Varien_Object
	 * @return self
	 */
	protected function _buildCustomAttributesByLevel(
		$level, EbayEnterprise_Dom_Element $element, Varien_Object $item
	)
	{
		$levelObject = $this->_getCustomAttributeInstance($level);
		if ($levelObject instanceof EbayEnterprise_Eb2cOrder_Model_Custom_Attribute) {
			$data = $levelObject->extractData($item);
			if (!empty($data)) {
				$customAttributeNode = $element->createChild(static::CUSTOM_ATTRIBUTE_NODE);
				foreach ($data as $key => $value) {
					$attributeNode = $customAttributeNode->createChild(static::ATTRIBUTE_NODE);
					$attributeNode->addChild(static::KEY_NODE, $key)
						->addChild(static::VALUE_NODE, $value);
				}
			}
		}

		return $this;
	}
	/**
	 * Take a custom attribute level and return an instance of a class that's inherited
	 * from the EbayEnterprise_Eb2cOrder_Model_Custom_Attribute class or null
	 * when the level is not one of the expected ones
	 * @param string $level
	 * @return EbayEnterprise_Eb2cOrder_Model_Custom_Attribute | null
	 */
	protected function _getCustomAttributeInstance($level)
	{
		$class = null;
		switch ($level) {
			case static::ORDER_LEVEL:
				$class = 'eb2corder/custom_attribute_order';
				break;
			case static::ITEM_LEVEL:
				$class = 'eb2corder/custom_attribute_item';
				break;
			case static::CONTEXT_LEVEL:
				$class = 'eb2corder/custom_attribute_context';
				break;
		}
		return !is_null($class)?Mage::getModel($class):$class;
	}
	/**
	 * Build 'PromotionalDiscounts/Discount' node and its inner nested child nodes.
	 * @param  EbayEnterprise_Dom_Element $merchandise
	 * @param  Mage_Sales_Model_Order_Item $item
	 * @param  float $discountAmount (item discount or shipping discount)
	 * @param  string $type (merchandise, shipping)
	 * @return self
	 */
	protected function _buildDiscount(
		EbayEnterprise_Dom_Element $merchandise,
		Mage_Sales_Model_Order_Item $item,
		$discountAmount,
		$type
	)
	{
		if ($discountAmount > 0) {
			$appliedCount = ($type === EbayEnterprise_Eb2cTax_Model_Response_Quote::MERCHANDISE_PROMOTION)?
				array(static::APPLIED_COUNT_ATTRIBUTE => $item->getQtyOrdered()) : array();

			$discount = $merchandise->createChild(static::PROMOTIONAL_DISCOUNTS_NODE)
				->createChild(static::DISCOUNT_NODE, null, $appliedCount);

			$couponCode = $this->_o->getCouponCode();
			// Spec says this *may* be required, schema validation says it *is* required
			$discount->addChild(
				static::DISCOUNT_ID_NODE,
				Mage::helper('eb2ccore')->getDiscountId($item->getAppliedRuleIds())
			);
			// The actual promotion code if it exists
			if ($couponCode !== '') {
				$discount->addChild(static::DISCOUNT_CODE_NODE, $couponCode);
			}
			// Magento has only 1 discount per line item
			// The total discount amount for all items in that line
			// Discount/Amount = Base Promotional Amount * quantity
			$discount->addChild(static::DISCOUNT_AMOUNT_NODE, sprintf('%.02f', $discountAmount));

			$description = $this->_getDiscountDescription($item);
			if (!is_null($description)) {
				// Promotion label (store view specific if it exists).
				// If no label exists, then use the promotion description
				$discount->addChild(static::DISCOUNT_DESCRIPTION_NODE, $description);
			}

			$simpleAction = $this->_getSimpleAction($item);
			if (!is_null($simpleAction)) {
				$discount->addChild(static::DISCOUNT_EFFECT_TYPE_NODE, $simpleAction);
			}
			// Tax on the Discount
			$discountTaxFragment = $this->_buildTaxDataNodes(
				$this->getItemTaxQuotes($item, $type),
				$this->_getProductTaxCode($item)
			);
			if ($discountTaxFragment->hasChildNodes()) {
				$discount->appendChild($discountTaxFragment);
			}
		}

		return $this;
	}
	/**
	 * Return an instance of Mage_SalesRule_Model_Rule loaded with the first
	 * rule id in the order item or null when order item has no rule ids.
	 * @param Mage_Sales_Model_Order_Item $item
	 * @return Mage_SalesRule_Model_Rule | null
	 */
	protected function _loadSalesRule(Mage_Sales_Model_Order_Item $item)
	{
		$ruleIds = array_filter(explode(',', $item->getAppliedRuleIds()));
		return !empty($ruleIds)?
			Mage::getModel('salesrule/rule')->load($ruleIds[0]):null;
	}
	/**
	 * Retrieve the applied rule ids in an order item and only load the
	 * Mage_SalesRule_Model_Rule with the first rule id if there are multiple.
	 * Return the store label base on the store id in the order item, or the
	 * rule description or null when there are no rule ids.
	 * @param Mage_Sales_Model_Order_Item $item
	 * @return string | null the store label when exists or rule description
	 *         or null when there's no rule ids in the order item.
	 */
	protected function _getDiscountDescription(Mage_Sales_Model_Order_Item $item)
	{
		$rule = $this->_loadSalesRule($item);
		if ($rule) {
			return $rule->getStoreLabel($item->getStoreId()) ?: $rule->getDescription();
		}
		return null;
	}
	/**
	 * Get the rule simple actions from Mage_SalesRule_Model_Rule using the
	 * Mage_Sales_Model_Order_Item::getAppliedRuleIds to load the
	 * Mage_SalesRule_Model_Rule model.
	 * @param Mage_Sales_Model_Order_Item $item
	 * @return string | null
	 */
	protected function _getSimpleAction(Mage_Sales_Model_Order_Item $item)
	{
		$rule = $this->_loadSalesRule($item);
		return $rule?$rule->getSimpleAction():null;
	}

	/**
	 * @see self::_buildGifting
	 * Build the "Gifting/Gift" nodes for an order item.
	 * When an order item has a valid getGwId, we can use that to get
	 * the giftwrapping associated to it in order to build the
	 * Gift node under Gifting and attached it to a DOMElement object.
	 * @param  DOMElement $giftingNode
	 * @param  Varien_Object $item
	 * @param $wrapId
	 * @param  string $level possible values (item or order)
	 * @return self
	 */
	protected function _buildGift(DOMElement $giftingNode, Varien_Object $item, $wrapId, $level='order')
	{
		$giftwrapping = Mage::getModel('enterprise_giftwrapping/wrapping')->load($wrapId);
		if ($giftwrapping->getWrappingId()) {
			$giftNode = $giftingNode->createChild('Gift');
			$giftNode->addChild('ItemId', sprintf('%.20s', $giftwrapping->getEb2cSku()));
			$pricingNode = $giftNode->createChild('Pricing');
			$pricingNode->addChild('Amount', Mage::app()->getStore()->roundPrice($this->_helper->calculateGwItemRowTotal($item)));
			// Tax on the gift wrapping pricing
			$taxData = ($level === 'order')
				? $this->_getTaxOnQuote($this->_o, EbayEnterprise_Eb2cTax_Model_Response_Quote::SHIPGROUP_GIFTING)
				: $this->getItemTaxQuotes($item, EbayEnterprise_Eb2cTax_Model_Response_Quote::GIFTING);
			$pricingTaxFragment = $this->_buildTaxDataNodes($taxData, $giftwrapping->getEb2cTaxClass());
			if ($pricingTaxFragment->hasChildNodes()) {
				$pricingNode->appendChild($pricingTaxFragment);
			}
			$pricingNode->addChild('UnitPrice', Mage::app()->getStore()->roundPrice($giftwrapping->getBasePrice()));
			$this->_buildMessage($giftNode, $item->getGiftMessageId(), null);
		}
		return $this;
	}
	/**
	 * Get tax for the entire quote, however because the is specific to quote item
	 * try to query the quote response for all quote item that has a situs
	 * of 'DESTINATION'.
	 * @param  Mage_Sales_Model_Order $order The order to get tax quotes for
	 * @param  int $taxType   The type of tax quotes to load
	 * @return EbayEnterprise_Eb2cTax_Model_Resource_Response_Quote_Collection
	 */
	protected function _getTaxOnQuote(Mage_Sales_Model_Order $order, $taxType)
	{
		$taxQuotes = Mage::getModel('eb2ctax/response_quote')->getCollection();
		$taxQuotes->addFieldToFilter('quote_item_id', array('in' => $this->_getAllQuoteItemIds($order)))
			->addFieldToFilter('type', $taxType)
			->addFieldToFilter('situs', 'DESTINATION');
		return $taxQuotes;
	}
	/**
	 * Get all quote item ids
	 * @param  Mage_Sales_Model_Order $order
	 * @return array of quote ids
	 */
	protected function _getAllQuoteItemIds(Mage_Sales_Model_Order $order)
	{
		$ids = array();
		$quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
		foreach ($quote->getAllVisibleItems() as $item) {
			$ids[] = $item->getId();
		}
		return $ids;
	}
	/**
	 * Build the '<PayPalPayerInfo>' node under the passed in DOMDocument context node
	 * object when the ROM Payment Extension is enabled and the payment method of the
	 * order object in this class property is 'PayPal'.
	 * @param EbayEnterprise_Dom_Element $context
	 * @return self
	 */
	protected function _buildPayPalPayerInfo(EbayEnterprise_Dom_Element $context)
	{
		if (Mage::helper('eb2cpayment')->getConfigModel()->isPaymentEnabled) {
			$payment = $this->_getPaypalPayment($this->_o->getAllPayments());
			if ($payment) {
				$context->createChild('PayPalPayerInfo')
					->addChild('PayPalPayerID', $this->_restrictText($payment->getAdditionalInformation('paypal_payer_id'), 50))
					->addChild('PayPalPayerStatus', $this->_restrictText($payment->getAdditionalInformation('paypal_payer_status'), 50))
					->addChild('PayPalAddressStatus', $this->_restrictText($payment->getAdditionalInformation('paypal_address_status'), 50));
			}
		}
		return $this;
	}
	/**
	 * Return an instance of the ‘sales/order_payment’ class when it has a ‘PayPal’
	 * payment method, otherwise return false.
	 * @param  array $payments An array of sales/order_payment instances in an order
	 * @return Mage_Sales_Model_Order_Payment | false The payment instance with Payment
	 *         method 'PayPal', false when no payment has the 'PayPal' payment method.
	 */
	protected function _getPaypalPayment(array $payments)
	{
		foreach ($payments as $payment) {
			if ($this->_hasPaypalPaymentMethod($payment)) {
				return $payment;
			}
		}
		return false;
	}
	/**
	 * Check if the passed in ‘sales/order_payment’ object has a PayPal payment method.
	 * @param  Mage_Sales_Model_Order_Payment $payment
	 * @return bool true the passed in payment object has a PayPal payment method otherwise false
	 */
	protected function _hasPaypalPaymentMethod(Mage_Sales_Model_Order_Payment $payment)
	{
		return ($this->_ebcPaymentMethodMap[ucfirst($payment->getMethod())] === static::PAYPAL_PAYMENT_METHOD);
	}
	/**
	 * Get the payment response code from a passed in 'sales/order_payment' instance
	 * parameter and a passed in payment method string.
	 * @param  Mage_Sales_Model_Order_Payment $payment Not yet implemented, however, in the future we hope to use
	 *         this parameter to determine the real response code
	 * @param  string $paymentMethod Not yet implemented
	 * @return string
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	protected function _getResponseCode(Mage_Sales_Model_Order_Payment $payment, $paymentMethod)
	{
		return 'APPROVED';
	}
}
