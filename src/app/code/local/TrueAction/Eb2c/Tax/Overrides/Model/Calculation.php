<?php
/**
 *
 */
class TrueAction_Eb2c_Tax_Overrides_Model_Calculation extends Mage_Tax_Model_Calculation
{
	public function __construct()
	{
		parent::__construct();
		if (!$this->hasTaxResponse()) {
			$checkout = Mage::getSingleton('checkout/session');
			if ($checkout->hasEb2cTaxResponse()) {
				parent::setTaxResponse($checkout->getEb2cTaxResponse());
			}
		}
	}

	/**
	 * generate a request object to use when calculating taxes and duties.
	 * @param  Mage_Sale_Model_Quote_Addres $shippingAddress
	 * @param  Mage_Sale_Model_Quote_Addres $billingAddress
	 * @param  string                       $customerTaxClass
	 * @param  int|Mage_Core_Model_Store    $store
	 * @return TrueAction_Eb2c_Tax_Model_TaxDutyRequest
	 * get a request object for the quote.
	 * @param  Mage_Sales_Model_Quote $quote
	 * @return TrueAction_Eb2c_Tax_Model_Request
	 */
	public function getRateRequest(
		$shippingAddress = null,
		$billingAddress = null,
		$customerTaxClass = '',
		$store = null
	) {
		return null;
	}

	/**
	 * return 0
	 * @param  TrueAction_Eb2c_Tax_Model_Request $request
	 * @return float
	 */
	public function getRate($request)
	{
		return 0.0;
	}


	/**
	 * get a request object for the quote.
	 * @param  Mage_Sales_Model_Quote $quote
	 * @return TrueAction_Eb2c_Tax_Model_Request
	 */
	public function getTaxRequest(Mage_Sales_Model_Quote $quote)
	{
		return Mage::getModel('eb2ctax/request', array('quote' => $quote));
	}

	/**
	 * store the tax response from eb2c
	 * @param TrueAction_Eb2c_Tax_Model_Response $response
	 */
	public function setTaxResponse(TrueAction_Eb2c_Tax_Model_Response $response)
	{
		parent::setTaxResponse($response);
		Mage::getSingleton('checkout/session')->setEb2cTaxResponse($response);
	}

	/**
	 * calculate tax amount for an item with the values from the response.
	 * @param  Mage_Sales_Model_Quote_Item $item
	 * @return float
	 */
	public function getTaxforItem(Mage_Sales_Model_Quote_Item $item)
	{
		$response = $this->getTaxResponse();
		$itemResponse = ($response) ?
			$response->getResponseForItem($item) : null;
		$tax = 0.0;
		if ($itemResponse) {
			$taxQuotes = $itemResponse->getTaxQuotes();
			foreach ($taxQuotes as $taxQuote) {
				$tax += $taxQuote->getCalculatedTax();
			}
		}
		return $tax;
	}

	/**
	 * calculate tax for an amount with the rates from the response for the item.
	 * @param  float                       $amount
	 * @param  Mage_Sales_Model_Quote_Item $item
	 * @param  boolean                     $amountInlcudesTax
	 * @param  boolean                     $round
	 * @return float
	 */
	public function getTaxforItemAmount(
		$amount,
		Mage_Sales_Model_Quote_Item $item,
		$amountInlcudesTax = false,
		$round = true
	) {
		$response = $this->getTaxResponse();
		$itemResponse = ($response) ?
			$response->getResponseForItem($item) : null;
		$tax = 0.0;
		if ($itemResponse) {
			$taxQuotes = $itemResponse->getTaxQuotes();
			foreach ($taxQuotes as $taxQuote) {
				$tax += $this->calcTaxAmount(
					$amount,
					$taxQuote->getEffectiveRate(),
					$amountInlcudesTax,
					$round
				);
			}
		}
		return $tax;
	}

	/**
	 * Calculate rated tax abount based on price and tax rate.
	 * If you are using price including tax $priceIncludeTax should be true.
	 *
	 * @param   float $price
	 * @param   float $taxRate
	 * @param   boolean $priceIncludeTax
	 * @return  float
	 */
	public function calcTaxAmount($price, $taxRate, $priceIncludeTax=false, $round=true)
	{
		if ($priceIncludeTax) {
			$amount = $price*(1-1/(1+$taxRate));
		} else {
			$amount = $price*$taxRate;
		}

		if ($round) {
			return $this->round($amount);
		}

		return $amount;
	}


	/**
	 * Get information about tax rates applied
	 *
	 * @param   Varien_Object $request
	 * @return  array
	 */
	public function getAppliedRates($item)
	{
		$result = array();
		return $result;
	}
}
