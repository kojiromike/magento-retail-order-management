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

	protected function _construct()
	{
		$this->_doc = new TrueAction_Dom_Document('1.0', 'utf8');
		$this->_doc->loadXML($this->getXml());
		$this->_parseResults();
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
	 * create a Tax record using data extracted from the arguments
	 * @param  int                            $type       @see TrueAction_Eb2c_Tax_Model_Tax
	 * @param  string                         $amount
	 * @param  TrueAction_Dom_Element         $tax
	 * @param  Mage_Sales_Model_Quote_Address $address
	 * @param  Mage_Sales_Model_Quote_Item    $quoteItem
	 * @return TrueAction_Eb2c_Tax_Model_Tax
	 */
	protected function _createTaxRecord(
		$type,
		$amount,
		TrueAction_Dom_Element $tax,
		Mage_Sales_Model_Quote_Address $address,
		Mage_Sales_Model_Quote_Item $quoteItem
	) {
		$xpath = new DOMXPath($tax->ownerDocument);
		$record = new TrueAction_Eb2c_Tax_Model_Tax(array(
			'type' => $type,
			'amount' => $amount,
			'quote_address_id' => $address->getId(),
			'quote_item_id'  =>  $quoteItem->getId(),
			// get effective rate
			'effecive_rate' => $xpath->evaluate('EffectiveRate/text()', $tax),
			// get taxable amount
			'taxable_amount' => $xpath->evaluate('TaxableAmount/text()', $tax),
			// get taxexemptamunt
			'exempt_amount' => $xpath->evaluate('ExemptAmount/text()', $tax),
			// get nontaxableamount
			'non_taxable_amount' => $xpath->evaluate('NonTaxableAmount/text()', $tax),
			// calculatedtax
			'calculated_tax' => $xpath->evaluate('CalculatedTax/text()', $tax),
		));
		$this->_itemResults[] = $record;
	}

	/**
	 * generate tax quote records with data extracted from the response.
	 */
	protected function _extractResults()
	{
		$xpath = new DOMXPath($this->_doc);
		$mailingAddresses = $xpath->query(
			'/TaxDutyQuoteResponse/Shipping/Destinations/MailingAddress'
		);
		$shipGroups = $xpath->query('/TaxDutyQuoteResponse/Shipping/ShipGroups/ShipGroup');
		foreach ($shipGroups as $shipGroup) {
			$address = $this->_getAddress($shipGroup->getAttribute('ref'));
			if (!is_null($address)) {
				return;
			}
			// foreach item
			$items = $xpath->query('Items/OrderItem', $shipGroup);
			foreach ($items as $item) {
				// get item address id (mage_quote_address)
				$quoteItem = $this->_getQuoteItem($item);
				// get item quantity
				// get merchandise unitprice
				$amount = $this->_verifyItemAmount();
				$type = TaxDutyRecord::ITEM_TYPE;
				$taxes = $xpath->query('Pricing/Merchandise/TaxData/Taxes/Tax');
				foreach ($taxes as $tax) {
					// foreach pricing/merchandise/taxdata/taxes/tax
					$this->_createTaxRecord($type, $amount, $tax, $address, $quoteItem);
				}
				// get shipping amount
				$amount = $this->_verifyShippingAmount();
				$type = TaxDutyRecord::SHIPPING_TYPE;
				$taxes = $xpath->query('Pricing/Shipping/TaxData/Taxes/Tax');
				foreach ($taxes as $tax) {
					// foreach pricing/shipping/taxdata/
					$this->_createTaxRecord($type, $amount, $tax, $address, $quoteItem);
				}
				$amount = $this->_verifyDutyAmount();
				$type = TaxDutyRecord::DUTY_TYPE;
				$taxes = $xpath->query('Pricing/Duty/TaxData/Taxes/Tax');
				foreach ($taxes as $tax) {
					// foreach pricing/shipping/taxdata/
					$this->_createTaxRecord($type, $amount, $tax, $address, $quoteItem);
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
	 * @return string                           the OrderItem's ItemId value (sku)
	 * @throws TrueAction_Eb2c_Tax_Model_Response_MismatchError
	 */
	protected function _validateResponseItem(TrueAction_Dom_Element $itemNode)
	{
		$requestDoc = $this->getRequest()->getDocument();
		$requestXpath = new DOMXPath($requestDoc);
		$xpath = new DOMXPath($this->_doc);
		$sku = $xpath->evaluate('ItemId/text()', $itemNode);
		$reqNode = $requestXpath->query('//OrderItem/ItemId[.=' . $sku)->items(0);
		$isValid = true;
		// if we get back an order item we didn't send log it as a debug message and
		// ignore it.
		if (!$reqNode) {
			$isValid = false;
			Mage::log(
				sprintf('%s: sku "%s" not found in the request.', 'TaxDutyQuoteResponse', $sku),
				Zend_Log::DEBUG
			);
			$sku = '';
		} else {
			$path = 'Quantity';
			if (!$this->_checkPathValues($path, $xpath, $itemNode, $requestXpath, $reqNode, true)) {
				$isValid = false;
				throw new TrueAction_Eb2c_Tax_Model_Response_Mismatch("$sku @ $path");
			}
			$path = 'Pricing/Merchandise/UnitPrice';
			if (!$this->_checkPathValues($path, $xpath, $itemNode, $requestXpath, $reqNode, true)) {
				$isValid = false;
				throw new TrueAction_Eb2c_Tax_Model_Response_Mismatch("$sku @ $path");
			}
			$path = 'Pricing/Shipping/Amount';
			if (!$this->_checkPathValues($path, $xpath, $itemNode, $requestXpath, $reqNode, true)) {
				$isValid = false;
				throw new TrueAction_Eb2c_Tax_Model_Response_Mismatch("$sku @ $path");
			}
			if ($reqNode->getAttribute('LineNumber') !== $itemNode->getAttribute('LineNumber'))
			{
				Mage::log(
					sprintf(
						'%s: %s "%s" does not match request "%s"',
						'TaxDutyQuoteResponse',
						'LineNumber',
						$resValue,
						$reqValue
					),
					Zend_Log::WARN
				);
			}
			$path = 'ItemDesc';
			$isValid &= $this->_checkPathValues($path, $xpath, $itemNode, $requestXpath, $reqNode);
			$path = 'HTSCode';
			$isValid &= $this->_checkPathValues($path, $xpath, $itemNode, $requestXpath, $reqNode);
			$path = 'Pricing/Merchandise/Amount';
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
		$resValue = $xpath->evaluate($path . '/text()', $itemNode);
		$reqValue = $reqXpath->evaluate($path . '/text()', $reqNode);
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