<?php
/**
 * reads the response from the TaxDutyRequest.
 */
class TrueAction_Eb2cTax_Model_Response_Orderitem extends Varien_Object
{
	/**
	 * orderitem node this instance represents
	 * @var TrueAction_Dom_Element
	 */
	protected $_xpath             = null;
	protected $_isValid           = true;
	protected $_taxQuotes         = array();
	protected $_taxQuoteDiscounts = array();
	/**
	 * pseudo-constant mapping of discount tax types to xpath expressions
	 */
	private static $_promoTaxMap = array(
		TrueAction_Eb2cTax_Model_Response_Quote::MERCHANDISE => 'a:Pricing/a:Merchandise/a:PromotionalDiscounts/a:Discount/a:Taxes/a:Tax',
		TrueAction_Eb2cTax_Model_Response_Quote::SHIPPING => 'a:Pricing/a:Shipping/a:PromotionalDiscounts/a:Discount/a:Taxes/a:Tax',
		TrueAction_Eb2cTax_Model_Response_Quote::DUTY => 'a:Pricing/a:Duty/a:PromotionalDiscounts/a:Discount/a:Taxes/a:Tax',
	);
	/**
	 * pseudo-constant mapping of tax types to xpath expressions
	 */
	private static $_taxMap = array(
		TrueAction_Eb2cTax_Model_Response_Quote::MERCHANDISE => 'a:Pricing/a:Merchandise/a:TaxData/a:Taxes/a:Tax',
		TrueAction_Eb2cTax_Model_Response_Quote::SHIPPING => 'a:Pricing/a:Shipping/a:TaxData/a:Taxes/a:Tax',
		TrueAction_Eb2cTax_Model_Response_Quote::DUTY => 'a:Pricing/a:Duty/a:TaxData/a:Taxes/a:Tax',
	);
	/**
	 * get whether the response is valid or not.
	 * @return boolean
	 */
	public function isValid()
	{
		return $this->_isValid;
	}

	/**
	 * get array of tax quotes
	 * @return array(TrueAction_Eb2cTax_Model_Response_Quote)
	 */
	public function getTaxQuotes()
	{
		return $this->_taxQuotes;
	}

	/**
	 * get array of tax quotes for discounts
	 * @return array(TrueAction_Eb2cTax_Model_Response_Quote_Discount)
	 */
	public function getTaxQuoteDiscounts()
	{
		return $this->_taxQuoteDiscounts;
	}

	protected function _construct()
	{
		if ($this->getNode()) {
			$xpath = new DOMXPath($this->getNode()->ownerDocument);
			$xpath->registerNamespace('a', $this->getNode()->namespaceURI);
			$this->_xpath = $xpath;
			$this->_extractData();
		}
	}

	public function setOrderItemData(array $data)
	{
		$this->_taxQuotes = array();
		if (array_key_exists('tax_quotes', $data)) {
			foreach ($data['tax_quotes'] as $record) {
				$this->_taxQuotes[] = Mage::getModel('eb2ctax/response_quote', $record);
			}
		}
	}

	public function getOrderItemData()
	{
		$quoteData = array_map(
			function($obj)
			{
				return $obj->unsNode()->getData();
			},
			$this->_taxQuotes
		);
		return $this
			->setTaxQuotes($quoteData)
			->getData();
	}

	/**
	 * generate tax quote records with data extracted from the response.
	 * using magic here by first casting the amount and unit price as float and checking if
	 * they are greater than zero to the create the node magically if it is zero setting the value to null
	 * will not create the node
	 * @return void
	 */
	protected function _extractData()
	{
		$xpath = $this->_xpath;
		$itemNode = $this->getNode();
		$this->addData(array_merge(
			$this->_extractByType($itemNode, $xpath, array(
				'sku' => 'string(./a:ItemId)',
				'line_number' => 'string(./@lineNumber)',
				'item_desc' => 'string(./a:ItemDesc)',
				'hts_code' => 'string(./a:HTSCode)'
			), 'string'),
			$this->_extractByType($itemNode, $xpath, array(
				'merchandise_amount' => 'number(a:Pricing/a:Merchandise/a:Amount)',
				'unit_price' => 'number(a:Pricing/a:Merchandise/a:UnitPrice)',
				'shipping_amount' => 'number(a:Pricing/a:Shipping/a:Amount)',
				'duty_amount' => 'number(a:Pricing/a:Duty/a:Amount)'
			), 'float')
		));

		$this->_validate();
		// Only bother reading the tax data if the item is valid.
		if ($this->_isValid) {
			$this->_taxQuotes = array_merge($this->_taxQuotes, $this->_processTaxData($xpath, $itemNode));
			$this->_taxQuoteDiscounts = array_merge($this->_taxQuoteDiscounts, $this->_processTaxData($xpath, $itemNode, true));
		}
	}

	/**
	 * extract all the map key from the map argument and return
	 * an array of extracted value cast by the type pass to the method
	 * @param DomElement $itemNode
	 * @param DOMXPath $xpath
	 * @param array $map
	 * @param string $type (string, float)
	 * @return array
	 */
	protected function _extractByType(DomElement $itemNode, DOMXPath $xpath, array $map, $type='string')
	{
		return array_reduce(array_keys($map), function($result=array(), $key) use ($itemNode, $xpath, $map, $type) {
			$val = $xpath->evaluate($map[$key], $itemNode);
			switch ($type) {
				case 'float':
					$val = (float) $val;
					$result[$key] = $val > 0 ? $val : null;
					break;
				default:
					$result[$key] = $val;
					break;
			}
			return $result;
		});
	}
	/**
	 * Parse the discount nodes into objects.
	 *
	 * @param DOMXPath $xpath
	 * @param TrueAction_Dom_Element $itemNode
	 * @param bool $isDiscount Whether or not to use discount tax mappings
	 * @return array the list of tax nodes
	 */
	protected function _processTaxData($xpath, $itemNode, $isDiscount=false)
	{
		$taxTypes = array_map(function ($expr) use ($xpath, $itemNode) {
			return $xpath->query($expr, $itemNode);
		}, $isDiscount ? self::$_promoTaxMap : self::$_taxMap);
		$modelFactory = 'eb2ctax/response_quote' . ($isDiscount ? '_discount' : '');
		$taxQuote = array();
		foreach ($taxTypes as $type => $nodeList) {
			foreach ($nodeList as $taxNode) {
				$taxQuote[] = Mage::getModel($modelFactory, array('type' => $type, 'node' => $taxNode));
			}
		}
		return $taxQuote;
	}

	protected function _validate()
	{
		if (!$this->getSku()) {
			$this->_isValid = false;
			Mage::log('[' . __CLASS__ . '] TaxDutyResponse: OrderItem received with an empty sku.');
		}
		if (!$this->getLineNumber()) {
			Mage::log('[' . __CLASS__ . '] TaxDutyResponse: OrderItem received with an empty lineNumber attribute.');
		}
	}
}
