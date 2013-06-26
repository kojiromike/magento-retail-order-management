<?php
/**
 * Generates an OrderCancel
 * @package Eb2c\Order
 * @author westm@trueaction.com
 *
 * Some events I *may* need to care about. I need to investigate whether 'order_cancel_after' ... it's my 
 *	first candidate for where I should hook the cancel request in.
 * 
 *  order_cancel_after
 */
class TrueAction_Eb2c_Order_Model_Cancel extends Mage_Core_Model_Abstract
{
	const DOM_ROOT_NODE_NAME = 'OrderCancelRequest';
	const DOM_ROOT_NS = 'http://api.gsicommerce.com/schema/checkout/1.0';

	protected $_xml = null;
	protected $_domRequest = null;

	private $_helper;
	private $_coreHelper;

	public function __construct()
	{
		parent::__construct();
		$this->_domRequest = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$this->_helper = $this->_getHelper();
		$this->_coreHelper = $this->_getCoreHelper();
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

	protected function _getCoreHelper()
	{
		if (!$this->_coreHelper) {
			$this->_coreHelper = Mage::helper('eb2ccore');
		}
		return $this->_coreHelper;
	}

	public function cancel($args)
	{
		$cancelRequest = $this->_domRequest->addElement(self::DOM_ROOT_NODE_NAME, null, self::DOM_ROOT_NS)->firstChild;
		$cancelRequest->addAttribute('orderType', $args['order_type']);

		$cancelRequest->createChild('CustomerOrderId', $args['order_id']);
		$cancelRequest->createChild('ReasonCode', $args['reason_code']);
		$cancelRequest->createChild('Reason', $args['reason']);;

		$this->_domRequest->formatOutput = true;
		$this->_xml = $this->_domRequest->saveXML();
		return $this->_transmit();
	}

	/**
	 * Transmit Cancellation
	 *
	 */
	private function _transmit()
	{
		$response = $this->_getCoreHelper()->callApi(
						$this->_domRequest, Mage::getStoreConfig('eb2c/order/cancel_uri') 
						);

		$this->_domResponse = new TrueAction_Dom_Document(); 
		$this->_domResponse->loadXML($response);
		$elementSet = $this->_domResponse->getElementsByTagName('ResponseStatus');
		$status='';
		foreach( $elementSet as $element ) {
			$status = $element->nodeValue;
		}
		return strcmp($status,'CANCELLED') ? false : true;
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
