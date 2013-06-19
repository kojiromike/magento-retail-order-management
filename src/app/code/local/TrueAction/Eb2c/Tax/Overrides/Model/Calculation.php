<?php
/**
 * replacement for the magento tax caclulation model that uses the eb2c api to
 * determine the tax/duty rates.
 * NOTE:
 * this class is meant to be used as a singleton
 */
class TrueAction_Eb2c_Tax_Overrides_Model_Calculation extends Mage_Tax_Model_Calculation
{
	private $callCounts = 0;
	private $callCounts2 = 0;
	/**
	 * generate a request object to use when calculating taxes and duties.
	 * @param  Mage_Sale_Model_Quote_Addres $shippingAddress
	 * @param  Mage_Sale_Model_Quote_Addres $billingAddress
	 * @param  string                       $customerTaxClass
	 * @param  int|Mage_Core_Model_Store    $store
	 * @return TrueAction_Eb2c_Tax_Model_TaxDutyRequest
	 */
	public function getRateRequest(
		$shippingAddress = null,
		$billingAddress = null,
		$customerTaxClass = '',
		$store = null
	) {
		// if ($billingAddress && $billingAddress->getQuote()->getTdRequest()) {
		// 	$request = $billingAddress->getQuote()->getTdRequest();
		// } else {
			$request = new TrueAction_Eb2c_Tax_Model_TaxDutyRequest(array(
				'shipping_address'   => $shippingAddress,
				'billing_address'    => $billingAddress,
				'customer_tax_class' => $customerTaxClass,
				'store'              => $store
			));
			// $billingAddress->getQuote()->setTdRequest($request);
		// }
		return $request;
	}

	/**
	 * get the tax rate for the specific request.
	 * @param  TaxDutyRequest $request
	 * @return float
	 */
	public function getRate($request)
	{
		$rate = 1;
		// if ($request->isUsable()) {
		// 	$key = $request->getUniqueKey();
		// 	if (isset($this->_rateCache[$key])) {
		// 		$rate = $this->_rateCache[$key];
		// 	} else {
		// 		$doc = $request->getDocument();
		// 		Mage::helper('eb2ctax')->sendRequest($request);
		// 	}
		// }
		// check cache
		// if in cache return rate from cache
		// else generate xml from request and return result of rate.
		//
		return $rate;
	}



	/**
	 * compare two requests to see if they yield the same rates.
	 * return true if requests are same; false otherwise
	 * @param  TaxDutyRequest $first  [description]
	 * @param  TaxDutyRequest $second [description]
	 * @return bool
	 */
	public function compareRequests($first, $second)
	{
		return false;
	}
}
