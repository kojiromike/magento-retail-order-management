<?php
/**
 * reads the response from the TaxDutyRequest.
 */
class TrueAction_Eb2c_Tax_Model_Response extends Mage_Core_Model_Abstract
{
	/**
	 * the dom document object for the response
	 * @var TrueAction_Dom_Document
	 */
	protected $_doc = null;

	/**
	 * result objects parsed from the response
	 * @var array
	 */
	protected $_itemResults = array();

	/**
	 * skus of OrderItem elements that passed validation
	 * @var array(string)
	 */
	protected $_validSkus = array();

	/**
	 * is the response valid
	 * @var boolean
	 */
	protected $_isValid   = false;

	/**
	 * alias to use when registering the root level namespace.
	 * @var string
	 */
	protected $_namespaceAlias = 'a';
	protected $_namespaceUri   = '';

	protected function _construct()
	{
		$this->_doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		if ($this->hasXml()) {
			$this->_doc->loadXML($this->getXml());
			$this->_namespaceUri =  $this->_doc->documentElement->namespaceURI;
			$this->_extractResults();
		}
	}

	/**
	 * get the result records of the request
	 * @return array(TrueAction_Eb2c_Tax_Model_Tax)
	 */
	public function getResults()
	{
		return $this->_itemResults;
	}

	/**
	 * @see self::$_isValid
	 * @return boolean
	 */
	public function isValid()
	{
		return $this->_isValid;
	}

	/**
	 * get the address using the value from the ref attribute.
	 * @param  string $idRef
	 * @return Mage_Sales_Model_Quote_Address
	 */
	protected function _getAddress($idRef)
	{
		$address = Mage::getModel('sales/quote_address')->load($idRef);
		if (!$address->getId())
		{
			$message = "Address referenced by '$idRef' could not be verified in the original request";
			Mage::log($message, Zend_Log::DEBUG);
			$address = null;
		}
		return $address;
	}

	/**
	 * generate tax quote records with data extracted from the response.
	 */
	protected function _extractResults()
	{
		$xpath = new DOMXPath($this->_doc);
		// namespace variable
		$xpath->registerNamespace($this->_namespaceAlias, $this->_namespaceUri);
		$n = $this->_namespaceAlias . ':';
		$root = $this->_doc->documentElement;
		$mailingAddresses = $xpath->query(
			'/a:Shipping/a:Destinations/a:MailingAddress',
			$root
		);
		$shipGroups = $xpath->query(
			'a:Shipping/a:ShipGroups/a:ShipGroup',
			$root
		);
		foreach ($shipGroups as $shipGroup) {
			$address = $this->_getAddress($shipGroup->getAttribute('ref'));
			if (!is_null($address)) {
				return;
			}
			$responseSkus = array();
			// foreach item
			$items = $xpath->query('//a:Items/a:OrderItem', $shipGroup);
			foreach ($items as $item) {
				$validSku = $this->_validateResponseItem($item);
				if ($validSku) {
					$quoteItem = $this->getRequest()->getItemBySku($validSku);
					Mage::log('got item ' . $validSku);
					// get item quantity
					// get merchandise unitprice
					$type = TrueAction_Eb2c_Tax_Model_Tax::ITEM_TYPE;
					$taxes = $xpath->query('Pricing/a:Merchandise/a:TaxData/a:Taxes/a:Tax');
					foreach ($taxes as $tax) {
						// foreach pricing/merchandise/taxdata/taxes/tax
						$this->_createTaxRecord($type, $amount, $tax, $address, $quoteItem);
					}
					// get shipping amount
					$amount = $this->_verifyShippingAmount();
					$type = TrueAction_Eb2c_Tax_Model_Tax::SHIPPING_TYPE;
					$taxes = $xpath->query('a:Pricing/a:Shipping/a:TaxData/a:Taxes/a:Tax');
					foreach ($taxes as $tax) {
						// foreach pricing/shipping/taxdata/
						$this->_createTaxRecord($type, $amount, $tax, $address, $quoteItem);
					}
					$amount = $this->_verifyDutyAmount();
					$type = TrueAction_Eb2c_Tax_Model_Tax::DUTY_TYPE;
					$taxes = $xpath->query('a:Pricing/a:Duty/a:TaxData/a:Taxes/a:Tax');
					foreach ($taxes as $tax) {
						// foreach pricing/shipping/taxdata/
						$this->_createTaxRecord($type, $amount, $tax, $address, $quoteItem);
					}
				} else {
					Mage::log("Skipping item '%s'", Zend_Log::DEBUG);
				}
			}
		}
		// foreach destination
		// verify data
	}

	/**
	 * compare an OrderItem element with the corresponding element in the request
	 * to make sure we got back what we sent.
	 * throw a mismatch error exception if a required field does not match what was sent.
	 * log a debug message for any optional data that does not match.
	 * @param  TrueAction_Dom_Element $itemNode OrderItem element from the request
	 * @param  string                           ShipGroup's id
	 * @return string                           the OrderItem's ItemId value (sku)
	 * @throws TrueAction_Eb2c_Tax_Model_Response_MismatchError
	 */
	protected function _validateResponseItem(TrueAction_Dom_Element $itemNode, $shipGrpId)
	{
		$n = $this->_namespaceAlias . ':';
		$requestDoc = $this->getRequest()->getDocument();
		$requestXpath = new DOMXPath($requestDoc);
		$requestXpath->registerNamespace('a', $this->_namespaceUri);
		$xpath = new DOMXPath($this->_doc);
		$xpath->registerNamespace('a', $this->_namespaceUri);
		$sku = $xpath->evaluate("a:ItemId/text()", $itemNode);
		$reqNode = $requestXpath->query(
			'//a:ShipGroup[@id=' . $shipGrpId . '//a:OrderItem/a:ItemId[.=' . $sku . ']'
		)->items(0);
		$isValid = true;
		// if we get back an order item we didn't send log it as a debug message and
		// ignore it.
		if (!$reqNode) {
			$isValid = false;
			Mage::log(
				sprintf('TaxDutyQuoteResponse: sku "%s" not found in the request.', $sku),
				Zend_Log::DEBUG
			);
			$sku = '';
		} else {
			$path = 'a:Quantity';
			if (!$this->_checkPathValues($path, $xpath, $itemNode, $requestXpath, $reqNode, true)) {
				$isValid = false;
				throw new TrueAction_Eb2c_Tax_Model_Response_Mismatch("$sku @ $path");
			}
			$path = 'a:Pricing/a:Merchandise/a:UnitPrice';
			if (!$this->_checkPathValues($path, $xpath, $itemNode, $requestXpath, $reqNode, true)) {
				$isValid = false;
				throw new TrueAction_Eb2c_Tax_Model_Response_Mismatch("$sku @ $path");
			}
			$path = 'a:Pricing/a:Shipping/a:Amount';
			if (!$this->_checkPathValues($path, $xpath, $itemNode, $requestXpath, $reqNode, true)) {
				$isValid = false;
				throw new TrueAction_Eb2c_Tax_Model_Response_Mismatch("$sku @ $path");
			}
			if ($reqNode->getAttribute("LineNumber") !== $itemNode->getAttribute('LineNumber'))
			{
				Mage::log(
					sprintf(
						'TaxDutyQuoteResponse: %s "%s" does not match request "%s"',
						'LineNumber',
						$resValue,
						$reqValue
					),
					Zend_Log::WARN
				);
			}
			$path = 'a:ItemDesc';
			$isValid &= $this->_checkPathValues($path, $xpath, $itemNode, $requestXpath, $reqNode);
			$path = 'a:HTSCode';
			$isValid &= $this->_checkPathValues($path, $xpath, $itemNode, $requestXpath, $reqNode);
			$path = 'a:Pricing/a:Merchandise/a:Amount';
			$isValid &= $this->_checkPathValues($path, $xpath, $itemNode, $requestXpath, $reqNode);
		}
		if ($isValid) {
			$this->_validSkus[] = $sku;
		} else {
			$sku = '';
		}
		return $sku;
	}

	/**
	 * compare the value of an element in the response document to an element in the
	 * request document.
	 * @param  string  $path
	 * @param  [type]  $xpath      [description]
	 * @param  [type]  $itemNode   [description]
	 * @param  [type]  $reqXpath   [description]
	 * @param  [type]  $reqNode    [description]
	 * @param  boolean $isRequired [description]
	 * @return [type]              [description]
	 */
	protected function _checkPathValues(
		$path,
		$xpath,
		$itemNode,
		$reqXpath,
		$reqNode,
		$isRequired = false
	) {
		$resValue = $xpath->evaluate("{$path}/text()", $itemNode);
		$reqValue = $reqXpath->evaluate("{$path}/text()", $reqNode);
		$isMatching = true;
		if ($resValue !== $reqValue) {
			$isMatching = false;
			$message = sprintf(
				'TaxDutyQuoteResponse: %s "%s" does not match request "%s"',
				$path,
				$resValue,
				$reqValue
			);
			if ($isRequired) {
				Mage::log($message, Zend_Log::WARN);
			} else {
				Mage::log($message, Zend_Log::DEBUG);
			}
		}
		return $isMatching;
	}
}