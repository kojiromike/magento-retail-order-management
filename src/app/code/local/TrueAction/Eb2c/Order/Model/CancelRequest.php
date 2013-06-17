<?php
/**
 * Generates an OrderCancelRequest
 * @package Eb2c\Order
 * @author westm@trueaction.com
 *
 * Some events I *may* need to care about. I need to investigate whether 'order_cancel_after' ... it's my 
 *	first candidate for where I should hook the cancel request in.
 * 
 *  order_cancel_after
 */
class TrueAction_Eb2c_Order_Model_CancelRequest extends Mage_Core_Model_Abstract
{
	protected $_xml = null;
	protected $_doc = null;

	public function __construct($args)
	{
		// TODO: api url needs to come from configuration. And to be added in.
		parent::__construct();
		$doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$cancelRequest = $doc->createElement('OrderCancelRequest')->addAttribute('orderType', $args['order_type']);
		$cancelRequest->addChild('CustomerOrderId', $args['order_id'])
						->addChild('ReasonCode', $args['reason_code'])
						->addChild('Reason', $args['reason']);
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
