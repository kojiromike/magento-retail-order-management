<?php
/**
 * reads the response from the TaxDutyRequest.
 */
class TrueAction_Eb2c_Tax_Model_Response_OrderItem extends Mage_Core_Model_Abstract
{
	/**
	 * orderitem node this instance represents
	 * @var TrueAction_Dom_Element
	 */
	protected $_xpath           = null;

	protected $_isValid         = true;

	/**
	 * @see self::$_isValid
	 * @return boolean
	 */
	public function isValid()
	{
		return $this->_isValid;
	}

	protected function _construct()
	{
		if ($this->getNode()) {
			$xpath = new DOMXPath($this->getNode()->ownerDocument);
			$xpath->registerNamespace('a', $this->getNamespaceUri());
			$this->_xpath = $xpath;
			$this->_extractData();
		}
	}

	/**
	 * generate tax quote records with data extracted from the response.
	 */
	protected function _extractData()
	{
		$xpath = $this->_xpath;
		$itemNode = $this->getNode();
		$this->setSku($xpath->evaluate('string(./a:ItemId)', $itemNode));
		$this->setLineNumber($xpath->evaluate('string(./@lineNumber)', $itemNode));
		$this->setItemDesc($xpath->evaluate('string(./a:ItemDesc)', $itemNode));
		$this->setHtsCode($xpath->evaluate('string(./a:HTSCode)', $itemNode));
		$this->setMerchandiseAmount(
			$xpath->evaluate('number(a:Pricing/a:Merchandise/a:Amount)', $itemNode)
		);
		$this->setUnitPrice(
			$xpath->evaluate('number(a:Pricing/a:Merchandise/a:UnitPrice)', $itemNode)
		);
		$this->setShippingAmount(
			$xpath->evaluate('number(a:Pricing/a:Shipping/a:Amount)', $itemNode)
		);
		$this->setDutyAmount($xpath->evaluate('number(a:Pricing/a:Duty/a:Amount)', $itemNode));
		$this->_validate();
		// don't bother reading the tax data since the item is invalid
		if ($this->_isValid) {
			$this->_processTaxData($xpath, $itemNode);
		}
	}

	protected function _processTaxData($xpath, $itemNode)
	{
		$arr = array();
		$type = TrueAction_Eb2c_Tax_Model_Response_Quote::MERCHANDISE;
		$taxNodes = $xpath->query('a:Pricing/a:Merchandise/a:TaxData/a:Taxes/a:Tax', $itemNode);
		foreach ($taxNodes as $taxNode) {
			$arr[] = Mage::getModel(
				'eb2ctax/response_quote',
				array(
					'type'           => $type,
					'node'           => $taxNode,
					'namspace_uri'   => $this->getNamespaceUri()
				)
			);
		}
		$type = TrueAction_Eb2c_Tax_Model_Response_Quote::SHIPPING;
		$taxNodes = $xpath->query('a:Pricing/a:Shipping/a:TaxData/a:Taxes/a:Tax', $itemNode);
		foreach ($taxNodes as $taxNode) {
			// foreach pricing/shipping/taxdata/
			$arr[] = Mage::getModel(
				'eb2ctax/response_quote',
				array(
					'type' => $type,
					'node' => $taxNode,
					'namspace_uri' => $this->getNamespaceUri()
				)
			);
		}
		$type = TrueAction_Eb2c_Tax_Model_Response_Quote::DUTY;
		$taxNodes = $xpath->query('a:Pricing/a:Duty/a:TaxData/a:Taxes/a:Tax', $itemNode);
		foreach ($taxNodes as $taxNode) {
			// foreach pricing/shipping/taxdata/
			$arr[] = Mage::getModel(
				'eb2ctax/response_quote',
				array(
					'type'           => $type,
					'node'           => $taxNode,
					'namspace_uri'   => $this->getNamespaceUri()
				)
			);
		}
		$this->setTaxQuotes($arr);
	}

	/**
	 */
	protected function _validate()
	{
		if (!$this->getSku()) {
			$this->_isValid = false;
			Mage::log('TaxDutyResponse: OrderItem received with an empty sku.');
		}
		if (!$this->getLineNumber()) {
			Mage::log('TaxDutyResponse: OrderItem received with an empty lineNumber attribute.');
		}
	}
}