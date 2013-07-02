<?php
/**
o* Generates an OrderCreate
 * @package Eb2c\Order
 * @author westm@trueaction.com
 *
 * Some events I *may* need to care about. Not necessarily that I must *listen* for all of these, but that I should
 * 	be aware of any side effects of these.
 * 
 * adminhtml_sales_order_create_process_data_before 
 * adminhtml_sales_order_create_process_data
 * sales_convert_quote_to_order
 * sales_convert_quote_item_to_order_item
 * sales_order_place_before
 * checkout_type_onepage_save_order_after
 * checkout_type_multishipping_create_orders_single
 * 
 */
class TrueAction_Eb2c_Order_Model_Create extends Mage_Core_Model_Abstract
{
	private $_o = null;					// Magento Order Object
	private $_xmlRequest = null;		// Human readable XML
	private $_xmlResponse = null;		// Human readable XML
	private $_domRequest = null;		// DOM Object
	private $_domResponse = null;		// DOM Object
	private $_orderItemRef = array();	// Saves an array of item_id's for use in shipping node

	private $_helper;
	private $_coreHelper;
	private $_config;

	protected function _construct()
	{
		$this->_helper = $this->_getHelper();
		$this->_config = Mage::getModel('eb2ccore/config_registry')
							->addConfigModel(Mage::getSingleton('eb2corder/config'))
							->addConfigModel(Mage::getSingleton('eb2ccore/config'));
	}

	/**
	 * Get helper instantiated object.
	 *
	 * @return TrueAction_Eb2c_Inventory_Helper_Data
	 */
	protected function _getHelper()
	{
		if (!$this->_helper) {
			$this->_helper = Mage::helper('eb2corder');
		}
		return $this->_helper;
	}

	/**
	 * An observer to create an eb2c order.
	 *
	 * @param orderId
	 */
	public function observerCreate($orderId)
	{
		$this->_buildRequest($orderId);
		return $this->_transmit();
	}

	/**
	 * Function to create an eb2c order.
	 *
	 * @param orderId
	 */
	public function create($orderId)
	{
		$this->_buildRequest($orderId);
		return $this->_transmit();
	}

	/**
	 * The Request as human-readable/ POST-able XML
	 *
	 * @returns string formatted XML
	 */
	public function toXml()
	{
		if( !$this->_domRequest ) {
			$this->_domRequest = new TrueAction_Dom_Document('1.0', 'UTF-8');
			$this->_domRequest->formatOutput = true;
			$this->_xmlRequest = $this->_domRequest->saveXML();
		}
		return $this->_xmlRequest;
	}

	/**
	 * Transmit Order
	 *
	 */
	private function _transmit()
	{
		if( $this->_config->developerMode ) {
			return true;
		}
		$response = $this->_getHelper()->getCoreHelper()->callApi(
						$this->_domRequest,
						$this->_config->createUri,
						$this->_config->serviceOrderTimeout
						);

		$this->_domResponse = new TrueAction_Dom_Document(); 
		$this->_domResponse->loadXML($response);
		$elementSet = $this->_domResponse->getElementsByTagName('ResponseStatus');
		$status='';
		foreach( $elementSet as $element ) {
			$status = $element->nodeValue;
		}
		return strcmp($status,'Success') ? false : true;
	}


	/**
	 * Build DOM for a complete order
	 *
	 * @param $orderId sting increment_id for the order we're building
	 */
	private function _buildRequest($orderId)
	{
		$this->_o = Mage::getModel('sales/order')->loadByIncrementId($orderId);
		if( !$this->_o->getId() ) {
			Mage::throwException('Order ' . $orderId . ' not found.' );
		}

		$consts = $this->_getHelper()->getConstHelper();

		$this->_domRequest = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$this->_domRequest->formatOutput = true;
		$orderCreateRequest = $this->_domRequest->addElement($consts::CREATE_DOM_ROOT_NODE_NAME, null, $consts::DOM_ROOT_NS)->firstChild;
		$orderCreateRequest->setAttribute('orderType', $consts::ORDER_TYPE);
		$orderCreateRequest->setAttribute('requestId', $this->_getRequestId());

		$order = $orderCreateRequest->createChild('Order');
		$order->setAttribute('levelOfService', $consts::LEVEL_OF_SERVICE);
		$order->setAttribute('customerOrderId', $this->_o->getIncrementId());

		$this->_buildCustomer( $order->createChild('Customer') );

		$order->createChild('CreateTime', str_replace(' ', 'T', $this->_o->getCreatedAt()));

		$webLineId = 1;
		$orderItems = $order->createChild('OrderItems');
		foreach( $this->_o->getAllItems() as $item ) {
			$this->_buildOrderItem($orderItems->createChild('OrderItem'), $item, $webLineId++);
		}

		// Magento only ever has 1 ship-to per order, so we're building directly into a singular ShipGroup
		$this->_buildShipping($order->createChild('Shipping')->createChild('ShipGroups')->createChild('ShipGroup'));

		$this->_buildPayment($order->createChild('Payment'));

		$order->createChild('Currency', $this->_o->getOrderCurrencyCode());

		$taxHeader = $order->createChild('TaxHeader')->createChild('Error', 'false');	// TODO: Tax Details needed here.

		$order->createChild('Locale', 'en_US');	// TODO: Is this region?

		$order->CreateChild('OrderSource'); // TODO: Not sure what this means.

		$order->createChild('OrderHistoryUrl', 
			Mage::app()->getStore($this->_o->getStoreId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) .
			$consts::ORDER_HISTORY_PATH . 
			$this->_o->getEntityId());

		$order->createChild('OrderTotal', sprintf('%.02f', $this->_o->getGrandTotal()));

		$this->_buildContext($orderCreateRequest->createChild('Context'));

		$this->_xmlRequest = $this->_domRequest->saveXML();
		return;
	}

	/**
	 * Build customer information node
	 *
	 * @param DomElement customer	where to place customer info
	 */
	private function _buildCustomer(DomElement $customer)
	{
		$customer->setAttribute('customerId', $this->_o->getCustomerId());

		$name = $customer->createChild('Name');
		$name->createChild('Honorific', $this->_o->getCustomerPrefix() );
		$name->createChild('LastName', trim($this->_o->getCustomerLastname() . ' ' . $this->_o->getCustomerSuffix()) );
		$name->createChild('MiddleName', $this->_o->getCustomerMiddlename());
		$name->createChild('FirstName', $this->_o->getCustomerFirstname());

		if( $this->_o->getCustomerGender() ) {
			$genderOpts = Mage::getResourceSingleton('customer/customer')->getAttribute('gender')->getSource()->getAllOptions();
			$eb2cGender = '';
			foreach( $genderOpts as $genderOpt ) {
				if( $genderOpt['value'] == $this->_o->getCustomerGender() ) {
					$eb2cGender = substr(ucfirst($genderOpt['label']),0,1);
				}
			}
			$customer->createChild('Gender', $eb2cGender);
		}
		if( $this->_o->getCustomerDob() ) {
			$customer->createChild('DateOfBirth', $this->_o->getCustomerDob());
		}
		$customer->createChild('EmailAddress', $this->_o->getCustomerEmail());
		$customer->createChild('CustomerTaxId', $this->_o->getCustomerTaxvat());
		return;
	}


	/**
	 * Builds a single Order Item node inside the Order Items array
	 * 
	 * @param DomElement orderItem
	 * @param Mage_Sales_Model_Order_Item item
	 * @param integer webLineId	identifier to indicate the line item's sequence within the order
	 */
	private function _buildOrderItem(DomElement $orderItem, Mage_Sales_Model_Order_Item $item, $webLineId)
	{
		$itemId = 'item_'.$item->getId();
		$this->_orderItemRef[] = $itemId;
		$orderItem->setAttribute('id', $itemId );
		$orderItem->setAttribute('webLineId', $webLineId++);
		$orderItem->createChild('ItemId', $item->getSku());
		$orderItem->createChild('Quantity', $item->getQtyOrdered());
		$orderItem->createChild('Description')->createChild('Description', $item->getName());

		$pricing = $orderItem->createChild('Pricing');
		$merchandise = $pricing->createChild('Merchandise');
		$merchandise->createChild('Amount', sprintf('%.02f', $item->getQtyOrdered() * $item->getPrice()));

		$discount = $merchandise
			->createChild('PromotionalDiscounts')
			->createChild('Discount');
		$discount->createChild('Id', 'CHANNEL_IDENTIFIER');	// Spec says this *may* be required, schema validation says it *is* required
		$discount->createChild('Amount', sprintf('%.02f', $item->getDiscountAmount())); // Magento has only 1 discount per line item

		$shippingMethod = $orderItem->createChild('ShippingMethod', '???');
		$estDeliveryDate = $orderItem->createChild('EstimatedDeliveryDate');

		$deliveryWindow = $estDeliveryDate->createChild('DeliveryWindow');
		$deliveryWindow->createChild('From', $item->getEb2cDeliveryWindowFrom());
		$deliveryWindow->createChild('To', $item->getEb2cDeliveryWindowTo());

		$shippingWindow = $estDeliveryDate->createChild('ShippingWindow');
		$shippingWindow->createChild('From', $item->getEb2cShippingWindowFrom());
		$shippingWindow->createChild('To', $item->getEb2cShippingWindowTo());

		$orderItem->createChild('ReservationId', '???PLACEHOLDER-'.$item->getEb2cReservationId()); // PLACEHOLDER so I can validate required field 

		// Tax on the Merchandise:
		$taxData = $merchandise->createChild('TaxData');
		$taxData->createChild('TaxClass', '????');
		$taxes = $taxData->createChild('Taxes');
		// TODO: More than 1 tax?
		$tax = $taxes->createChild('Tax');
		$tax->setAttribute('taxType', 'SELLER_USE');	// TODO: Fix where this comes from.
		$tax->setAttribute('taxability', 'TAXABLE');		// TODO: Fix where this comes from.
		$tax->createChild('Situs', 0);
		$jurisdiction = $tax->createChild('Jurisdiction', '??Jurisdiction Name??');
		$jurisdiction->setAttribute('jurisdictionLevel', '??State or County Level??');
		$jurisdiction->setAttribute('jurisdictionId', '??Jurisidiction Id??');
		$tax->createChild('EffectiveRate', $item->getTaxPercent());
		$tax->createChild('TaxableAmount', sprintf('%.02f', $item->getPrice() - $item->getTaxAmount()));
		$tax->createChild('CalculatedTax', sprintf('%.02f', $item->getTaxAmount()));
		$merchandise->createChild('UnitPrice', sprintf('%.02f', $item->getPrice()));
		// End Merchandise

		// Shipping on the orderItem:
		$shipping = $orderItem->createChild('Shipping');
		$shipping->createChild('Amount');
		// Tax on Shipping:
		$taxData = $shipping->createChild('TaxData');
		$taxData->createChild('TaxClass', '????');
		$taxes = $taxData->createChild('Taxes');
		// TODO: More than 1 tax?
		$tax = $taxes->createChild('Tax');
		$tax->setAttribute('taxType', 'SELLER_USE');	// TODO: Fix where this comes from.
		$tax->setAttribute('taxability', 'TAXABLE');		// TODO: Fix where this comes from.
		$tax->createChild('Situs', 0);
		$jurisdiction = $tax->createChild('Jurisdiction', '??Jurisdiction Name??');
		$jurisdiction->setAttribute('jurisdictionLevel', '??State or County Level??');
		$jurisdiction->setAttribute('jurisdictionId', '??Jurisidiction Id??');
		$tax->createChild('EffectiveRate', 0);
		$tax->createChild('TaxableAmount', sprintf('%.02f', 0));
		$tax->createChild('CalculatedTax', sprintf('%.02f', 0)); 
		// End Shipping
		

		// Duty on the orderItem:
		$duty = $orderItem->createChild('Duty');
		$duty->createChild('Amount');
		$taxData = $duty->createChild('TaxData');
		$taxData->createChild('TaxClass', 'DUTY'); // Is this a hardcoded value?
		$taxes = $taxData->createChild('Taxes');
		// TODO: More than 1 tax? 
		$tax = $taxes->createChild('Tax');
		$tax->setAttribute('taxType', 'SELLER_USE');	// TODO: Fix where this comes from.
		$tax->setAttribute('taxability', 'TAXABLE');		// TODO: Fix where this comes from.
		$tax->createChild('Situs', 0);
		$jurisdiction = $tax->createChild('Jurisdiction', '??Jurisdiction Name??');
		$jurisdiction->setAttribute('jurisdictionLevel', '??State or County Level??');
		$jurisdiction->setAttribute('jurisdictionId', '??Jurisidiction Id??');
		$tax->createChild('EffectiveRate', $item->getTaxPercent());
		$tax->createChild('TaxableAmount', sprintf('%.02f', $item->getPrice() - $item->getTaxAmount()));
		$tax->createChild('CalculatedTax', sprintf('%.02f', $item->getTaxAmount()));
		// End Duty
		return;
	}

	/**
	 * Builds the Shipping Node for order
	 *
	 * @param DomElement shipGroup Node to contain shipping and billing info
	 *
	 */
	private function _buildShipping(DomElement $shipGroup)
	{
		$consts = $this->_getHelper()->getConstHelper();
		$shipGroup->setAttribute('id', 'shipGroup_1');
		$shipGroup->setAttribute('chargeType', '');
		$shipGroup->createChild('DestinationTarget')->setAttribute('ref', $consts::SHIPGROUP_DESTINATION_ID);
		$orderItems = $shipGroup->createChild('OrderItems');
		foreach( $this->_orderItemRef as $orderItemRef ) {
			$shipItem = $orderItems->createChild('Item');
			$shipItem->setAttribute('ref', $orderItemRef);
		}
		$destinations = $shipGroup->createChild('Destinations');

		// Ship-To
		$sa = $this->_o->getShippingAddress();
		$dest = $destinations->createChild('MailingAddress');
		$dest->setAttribute('id', $consts::SHIPGROUP_DESTINATION_ID);
		$this->_buildPersonName($dest->createChild('PersonName'), $sa);
		$this->_buildAddress($dest->createChild('Address'), $sa);
		$dest->createChild('Phone', $sa->getTelephone());


		// Bill-To
		// TODO: We may have to revisit billing details in case of multiple tenders for a single order? Don't know what magento allows.
		$ba = $this->_o->getBillingAddress();
		$billing = $destinations->createChild('MailingAddress');
		$billing->setAttribute('id', $consts::SHIPGROUP_BILLING_ID);
		$this->_buildPersonName($billing->createChild('PersonName'), $ba);
		$this->_buildAddress($billing->createChild('Address'), $ba);
		$billing->createChild('Phone', $ba->getTelephone());
		return;
	}


	/**
	 * Creates PersonName element details from an address
	 *
	 * @param personNamede
	 * @param Mage_Sales_Model_Order_Address address
 	 */
	private function _buildPersonName(DomElement $person, Mage_Sales_Model_Order_Address $address)
	{
		$person->createChild('Honorific', $address->getPrefix());
		$person->createChild('LastName', trim($address->getLastname() . ' ' . $address->getSuffix()));
		$person->createChild('MiddleName', $address->getMiddlename());
		$person->createChild('FirstName', $address->getFirstname());
		return;
	}


	/**
	 * Creates MailingAddress/Address element details from address
	 * 
	 * @param DomElement addressElement
	 * @param Mage_Sales_Order_Address address
	 */
	private function _buildAddress(DomElement $addressElement, Mage_Sales_Model_Order_Address $address)
	{
		$line = 1;
		foreach($address->getStreet() as $streetLine) {
			$addressElement->createChild('Line'.$line, $streetLine);
			$line++;
		}
		$addressElement->createChild('City', $address->getCity());
		$addressElement->createChild('MainDivision', $address->getRegion());
		$addressElement->createChild('CountryCode', $address->getCountryId());
		$addressElement->createChild('PostalCode', $address->getPostalCode());
		return;
	}


	/**
	 * Populate the Payment Element of the request
	 *
	 * @param DomElement payment
	 */
	private function _buildPayment($payment)
	{
		$consts = $this->_getHelper()->getConstHelper();
		$payment->createChild('BillingAddress')->setAttribute('ref', $consts::SHIPGROUP_BILLING_ID);
		$this->_buildPayments($payment->createChild('Payments'));
		return;
	}


	/**
	 * Creates the Tender entries within the Payments Element
	 *
	 * @param DomElement payments node into which payment info is placed
	 */
	private function _buildPayments(DomElement $payments)
	{
		foreach($this->_o->getAllPayments() as $payment) {
			$method = ucfirst($payment->getMethod());
			$thisPayment = $payments->createChild($method);

			$paymentContext = $thisPayment->createChild('PaymentContext');
			$paymentContext->createChild('PaymentSessionId', '???');
			$paymentContext->createChild('TenderType', '???');
			$paymentContext->createChild('PaymentAccountUniqueId', '???')->setAttribute('isToken', 'true');

			$thisPayment->createChild('PaymentRequestId', '???');
			$thisPayment->createChild('CreateTimeStamp', '???');

			$auth = $thisPayment->createChild('Authorization');
			$auth->createChild('ResponseCode', $payment->getCcStatus());
			$auth->createChild('BankAuthorizationCode', $payment->getCcApproval());
			$auth->createChild('CVV2ResponseCode', $payment->getCcCidStatus());
			$auth->createChild('AVSResponseCode', $payment->getCcAvsStatus());
			$auth->createChild('AmountAuthorized', sprintf('%.02f', $payment->getAmountAuthorized()));

			$thisPayment->createChild('ExpirationDate', $payment->getCcExpYear().'-'.$payment->getCcExpMonth());
			$thisPayment->createChild('StartDate', '???');
			$thisPayment->createChild('IssueNumber', '???');
		}
		return;
	}


	/**
	 * Populates the Context element 
	 *
	 * @param DomElement context
	 */
	private function _buildContext(DomElement $context)
	{
		$this->_buildBrowserData($context->createChild('BrowserData'));
		$context->createChild('TdlOrderTimestamp');
		$context->createChild('SessionInfo');
		$context->createChild('PayPalPayerInfo');
		$context->createChild('CustomerAttributes');
		return;
	}


	/**
	 * Populates the Context/BrowserData element  - TODO: I don't think this is well supported without Fraud stuff??
	 *
	 * @param DomElement context
	 */
	private function _buildBrowserData(DomElement $browserData)
	{
		$children = array(
			'HostName', 'IPAddress', 'SessionId', 'UserAgent', 'Connection', 'Cookies', 'UserCookie',
			'UserAgentOS', 'UserAgentCPU', 'HeaderFrom', 'EmbeddedWebBrowserFrom', 'JavascriptData',
			'Referrer', 'HTTPAcceptData' );

		foreach( $children as $child ) {
			$browserData->createChild($child);
		}

		return;
	}


	/**
	 * Get globally unique request identifier
	 */
	private function _getRequestId()
	{
		return uniqid('OCR-');
	}
}
