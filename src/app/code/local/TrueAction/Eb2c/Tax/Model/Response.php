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

	protected function _construct()
	{
		$this->_doc = new TrueAction_Dom_Document('1.0', 'utf8');
		$this->_doc->loadXML($this->getXml());
		$this->_parseResults();
	}

	public function getResults()
	{
		return $this->_itemResults;
	}

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

	protected function _createTaxRecord($type, $amount, TrueAction_Dom_Element $tax, $address, $quoteItem)
	{
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

	protected function _getQuoteItem(TrueAction_Dom_Element $item)
	{
		// get item sku
		// get Quote_Item
		// verify line number
	}

	protected function _parseResults()
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
}