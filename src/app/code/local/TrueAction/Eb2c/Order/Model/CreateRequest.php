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
	protected $_xml = null;
	protected $_doc = null;

	protected function _construct()
	{
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$createRequest = $doc->addElement('OrderCreateRequest')->firstChild;
		$createRequest
			->addChild('Customer', 'Mike West');
		$doc->formatOutput = true;
		$this->_doc = $doc;
		$this->_xml = $this->_doc->saveXML();
	}

	/**
	 * The Request as human-readable/ POST-able XML
	 *
	 * @returns string Well formatted XML Request
	 */
	public function toXml()
	{
		return $this->_xml;
	}
}
