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
	const ORDER_CREATE_REQUEST_NAME = 'OrderCreateRequest';

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
	 *
	 */
	private function _create($orderId)
	{
		$mage_order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
		if( !$mage_order->getId() ) {
			Mage::throwException('Order ' . $orderId . ' not found.' );
		}

		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$orderCreateRequest = $doc->addElement(self::ORDER_CREATE_REQUEST_NAME)->firstChild;

		$orderCreateRequest->addChild('Customer', $mage_order->getCustomerName());

		$el = $doc->createElement('CreateTime', $mage_order->getCreatedAt());
		$orderCreateRequest->appendChild($el);

		$el = $doc->createElement('OrderItems', '');
		$orderCreateRequest->appendChild($el);

		$el = $doc->createElement('Shipping', '');
		$orderCreateRequest->appendChild($el);

		$el = $doc->createElement('Payment', '');
		$orderCreateRequest->appendChild($el);

		$el = $doc->createElement('Currency', '');
		$orderCreateRequest->appendChild($el);

		$el = $doc->createElement('TaxHeader', '');
		$orderCreateRequest->appendChild($el);

		$el = $doc->createElement('Locale', '');
		$orderCreateRequest->appendChild($el);

		$el = $doc->createElement('OrderSource', '');
		$orderCreateRequest->appendChild($el);

		$el = $doc->createElement('OrderHistoryUrl', '');
		$orderCreateRequest->appendChild($el);

		$el = $doc->createElement('OrderTotal', '');
		$orderCreateRequest->appendChild($el);

		$doc->formatOutput = true;
		$this->_doc = $doc;
		$this->_xml = $this->_doc->saveXML();
	}
}
