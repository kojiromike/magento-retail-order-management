<?php
/**
 * Generates an OrderCreateRequest
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
class TrueAction_Eb2c_Order_Model_CreateRequest extends Mage_Core_Model_Abstract
{
	const ORDER_CREATE_REQUEST_TAG = 'OrderCreateRequest';

	const ORDER_TYPE = 'SALES';
	const LEVEL_OF_SERVICE = 'REGULAR';

	const SHIPGROUP_DESTINATION_ID = 'dest_1';
	const SHIPGROUP_BILLING_ID = 'billing_1';
	const ORDER_HISTORY_PATH = 'sales/order/view/order_id/';

	private $_o = null;
	protected $_xml = null;
	protected $_doc = null;

	/**
	 * The Request as human-readable/ POST-able XML
	 *
	 * @returns string Well formatted XML Request
	 */
	public function toXml()
	{
		return $this->_xml;
	}

	/**
	 * An observer to create an eb2c order.
	 *
	 */
	public function observerCreateOrder($orderId)
	{
		return $this->_create($orderId);
	}

	/**
	 * Function to create an eb2c order.
	 *
	 */
	public function createOrder($orderId)
	{
		return $this->_create($orderId);
	}

	/**
	 * Get globally unique request identifier
	 */
	private function _getRequestId()
	{
		return uniqid('OCR-');
	}

	/**
	 * Build DOM for a complete order
	 *
	 * @param $orderId sting increment_id for the order we're building
	 */
	private function _create($orderId)
	{
		$this->_o = Mage::getModel('sales/order')->loadByIncrementId($orderId);
		if( !$this->_o->getId() ) {
			Mage::throwException('Order ' . $orderId . ' not found.' );
		}

		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$orderCreateRequest = $doc->addElement(self::ORDER_CREATE_REQUEST_TAG)->firstChild;
		$orderCreateRequest->setAttribute('orderType', self::ORDER_TYPE);
		$orderCreateRequest->setAttribute('requestId', $this->_getRequestId());


		$order = $orderCreateRequest->createChild('Order');
		$order->setAttribute('levelOfService',self::LEVEL_OF_SERVICE);
		$order->setAttribute('customerOrderId',$this->_o->getIncrementId());

		// <Customer id='id'>
		if ($customer = $order->createChild('Customer')) {
			$customer->setAttribute('customerId', $this->_o->getCustomerId());
			if( $name = $customer->createChild('Name') ) {
				$name->createChild('Honorific', $this->_o->getCustomerPrefix() );
				$name->createChild('LastName', trim($this->_o->getCustomerLastname() . ' ' . $this->_o->getCustomerSuffix()) );
				$name->createChild('FirstName', $this->_o->getCustomerFirstname());
				$name->createChild('MiddleName', $this->_o->getCustomerMiddlename());
			}
			$customer->createChild('Gender', $this->_o->getCustomerGender());
			$customer->createChild('DateOfBirth', $this->_o->getCustomerDob());
			$customer->createChild('EmailAddress', $this->_o->getCustomerEmail());
			$customer->createChild('CustomerTaxId', $this->_o->getCustomerTaxvat());
		}
		// </Customer>


		// <CreateTime>
		$order->createChild('CreateTime', $this->_o->getCreatedAt());
		// </CreateTime>


		// <OrderItems>
		$orderItems = $order->createChild('OrderItems');
		$webLineId = 1;
		foreach( $this->_o->getAllItems() as $i ) {
			$orderItem = $orderItems->createChild('OrderItem');
			$orderItem->setAttribute('id', $i->getId());
			$orderItem->setAttribute('webLineId', $webLineId++);
			$orderItem->createChild('ItemId', $i->getSku());
			$orderItem->createChild('Quantity', $i->getQtyOrdered());
			$orderItem->createChild('Description')->createChild('Description', $i->getName());

			$pricing = $orderItem->createChild('Pricing');
			$merchandise = $pricing->createChild('Merchandise');
			$merchandise->createChild('Amount', sprintf('%.02f',$i->getQtyOrdered()*$i->getPrice()));

			// Magento has only 1 discount per line item
			// <Discount>
			$discount = $merchandise
				->createChild('PromotionalDiscounts')
				->createChild('Discount');
			$discount->createChild('Amount', sprintf('%.02f',$i->getDiscountAmount()));

			// </Discount>

			// Tax on the Merchandise:
			$taxData = $merchandise->createChild('TaxData');
			$taxData->createChild('TaxClass','????');
			$taxes = $taxData->createChild('Taxes');
			// TODO: More than 1 tax?
			$tax = $taxes->createChild('Tax');
			$tax->setAttribute('taxType', 'SELLER_USE');	// TODO: Fix where this comes from.
			$tax->setAttribute('taxability','TAXABLE');		// TODO: Fix where this comes from.
			$tax->createChild('EffectiveRate', $i->getTaxPercent());
			$tax->createChild('TaxableAmount', sprintf('%.02f', $i->getPrice()-$i->getTaxAmount()));
			$tax->createChild('CalculatedTax', sprintf('%.02f', $i->getTaxAmount()));
			$merchandise->createChild('UnitPrice', sprintf('%.02f',$i->getPrice()));
			// End Merchandise

			// Shipping on the orderItem:
			$shipping = $orderItem->createChild('Shipping');
			$shipping->createChild('Amount');
			// Tax on Shipping:
			$taxData = $shipping->createChild('TaxData');
			$taxData->createChild('TaxClass','????');
			$taxes = $taxData->createChild('Taxes');
			// TODO: More than 1 tax?
			$tax = $taxes->createChild('Tax');
			$tax->setAttribute('taxType', 'SELLER_USE');	// TODO: Fix where this comes from.
			$tax->setAttribute('taxability','TAXABLE');		// TODO: Fix where this comes from.
			$tax->createChild('Situs', 0);
			$jurisdiction = $tax->createChild('Jurisdiction', '??Jurisdiction Name??');
			$jurisdiction->setAttribute('jurisdictionLevel','??State or County Level??');
			$jurisdiction->setAttribute('jurisdictionId', '??Jurisidiction Id??');
			$tax->createChild('EffectiveRate', 0);
			$tax->createChild('EffectiveRate', 0);
			$tax->createChild('TaxableAmount', sprintf('%.02f', 0));
			$tax->createChild('CalculatedTax', sprintf('%.02f', 0)); 
			// End Shipping
			

			// Duty on the orderItem:
			$duty = $orderItem->createChild('Duty');
			$duty->createChild('Amount');
			$taxData = $duty->createChild('TaxData');
			$taxData->createChild('TaxClass','DUTY'); // Is this a hardcoded value?
			$taxes = $taxData->createChild('Taxes');
			// TODO: More than 1 tax? 
			$tax->setAttribute('taxType', 'SELLER_USE');	// TODO: Fix where this comes from.
			$tax->setAttribute('taxability','TAXABLE');		// TODO: Fix where this comes from.
			$tax->createChild('EffectiveRate', $i->getTaxPercent());
			$tax->createChild('TaxableAmount', sprintf('%.02f', $i->getPrice()-$i->getTaxAmount()));
			$tax->createChild('CalculatedTax', sprintf('%.02f', $i->getTaxAmount()));
			// End Duty
				
		}
		// </OrderItems>

		/**
		 * <Shipping>
		 * Magento never has multiple shipping destinations per-order. In Multi-Shipping, Magento 
		 *	creates several orders all sent to a single destination.
		 */
		$shipGroup = $order->createChild('Shipping')
			->createChild('ShipGroups')
			->createChild('ShipGroup');
		$shipGroup->setAttribute('id', 'shipGroup_1');
		$shipGroup->setAttribute('chargeType','');
		$shipGroup->createChild('DestinationTarget')->setAttribute('ref', self::SHIPGROUP_DESTINATION_ID);
		$shipGroup->createChild('OrderItems');
		$destinations = $shipGroup->createChild('Destinations');

		$dest_1 = $destinations->createChild('MailingAddress');
		$dest_1->setAttribute('id', self::SHIPGROUP_DESTINATION_ID);

		// We'll may have to revisit billig details in case of multiple tenders for a single order.
		$billing_1 = $destinations->createChild('MailingAddress');
		$billing_1->setAttribute('id',self::SHIPGROUP_BILLING_ID);
		// </Shipping>

		// <Payment>
		$payment = $order->createChild('Payment');
		$payment->createChild('BillingAddress')->setAttribute('ref',self::SHIPGROUP_BILLING_ID);
		$payments = $payment->createChild('Payments');
		// </Payment>

		// <Currency>
		$order->createChild('Currency', $this->_o->getOrderCurrencyCode());
		// </Currency>

		// <TaxHeader>
		$taxHeader = $order->createChild('TaxHeader');
		$taxHeader->createChild('Error','false');	// TODO: Tax Details needed here.
		// </TaxHeader>

		// <PrintedCatalogCode>
		$order->createChild('PrintedCatalogCode'); // TODO: Do we actually have this.
		// </PrintedCatalogCode>

		// <Locale>
		$order->createChild('Locale');	// TODO: Maybe comes from store config?
		// </Locale>

		// <OrderSource>
		$order->CreateChild('OrderSource'); // TODO: Not sure what this means.
		// </OrderSource>

		// <OrderHistoryUrl>
		$order->createChild('OrderHistoryUrl', 
				Mage::app()->getStore($this->_o->getStoreId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) .
				self::ORDER_HISTORY_PATH . 
				$this->_o->getEntityId());
		// </OrderHistoryUrl>

		// <OrderTotal>
		$order->createChild('OrderTotal', sprintf('%.02f',$this->_o->getGrandTotal())); // TODO: Need understand MultiShipping here, even if we don't necessarily code for it.
		// </OrderTotal>

		$doc->formatOutput = true;
		$this->_doc = $doc;
		$this->_xml = $this->_doc->saveXML();
	}
}
