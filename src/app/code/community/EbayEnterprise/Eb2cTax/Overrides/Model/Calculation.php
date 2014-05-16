<?php
class EbayEnterprise_Eb2cTax_Overrides_Model_Calculation extends Mage_Tax_Model_Calculation
{
	const TAX_REGULAR = 'regular';
	const TAX_REGULAR_FOR_AMOUNT = 'regular-for-amount';
	const TAX_DISCOUNT = 'discount';
	const TAX_DISCOUNT_FOR_AMOUNT = 'discount-for-amount';
	const MERCHANDISE_TYPE = 0;
	const SHIPPING_TYPE = 1;
	const DUTY_TYPE = 2;
	/**
	 * Extract tax data regular or discount
	 * @param EbayEnterprise_Eb2cTax_Model_Response_Orderitem $itemResponse
	 * @param string $mode, [regular | regular-for-amount | discount | discount-for-amount]
	 * @return array
	 */
	protected function _extractTax(EbayEnterprise_Eb2cTax_Model_Response_Orderitem $itemResponse, $mode=self::TAX_REGULAR)
	{
		return ($mode === self::TAX_DISCOUNT || $mode === self::TAX_DISCOUNT_FOR_AMOUNT) ?
			$itemResponse->getTaxQuoteDiscounts() : $itemResponse->getTaxQuotes();
	}
	/**
	 * Calculate tax by mode
	 * @param float $amount The amount to calculate tax on
	 * @param Varien_Object $itemSelector
	 * @param int $type One of the values of these constants [MERCHANDISE_TYPE, SHIPPING_TYPE, DUTY_TYPE]
	 * @param string $mode, [regular | discount | discount-for-amount, regular, regular-for-amount]
	 * @return float the total tax amount for any discounts
	 */
	protected function _calcTaxByMode($amount=0, Varien_Object $itemSelector, $type=self::MERCHANDISE_TYPE, $mode=self::TAX_REGULAR)
	{
		$tax = 0.0;
		$itemResponse = $this->_getItemResponse($itemSelector->getItem(), $itemSelector->getAddress());
		if ($itemResponse) {
			$taxQuotes = $this->_extractTax($itemResponse, $mode);
			$taxMethod = $this->_isForAmountMode($mode)? 'getEffectiveRate' : 'getCalculatedTax';
			$tax = array_reduce($taxQuotes, function ($result, $taxQuote) use ($taxMethod, $type, $amount) {
				if ($type === $taxQuote->getType()) {
					$result += ($taxMethod === 'getEffectiveRate')? $amount * $taxQuote->getEffectiveRate() : $taxQuote->getCalculatedTax();
				}
				return $result;
			});
			if ($type === self::DUTY_TYPE) {
				$dutyAmount = $itemResponse->getDutyAmount();
				if (!is_nan($dutyAmount)) {
					$tax += $itemResponse->getDutyAmount();
				}
			}
		}
		return $tax;
	}
	/**
	 * check if mode is for tax amount, regular or discount
	 * @param string $mode, [regular | discount | discount-for-amount, regular, regular-for-amount]
	 * @return bool, true is in regular or discount tax amount otherwise false
	 */
	protected function _isForAmountMode($mode)
	{
		return in_array($mode, array(self::TAX_DISCOUNT_FOR_AMOUNT, self::TAX_REGULAR_FOR_AMOUNT));
	}
	/**
	 * calculate tax amount for an item filtered by $type.
	 * @param  Varien_Object $itemSelector
	 * @param  string        $type
	 * @return float
	 */
	public function getTax(Varien_Object $itemSelector, $type=self::MERCHANDISE_TYPE)
	{
		return $this->_calcTaxByMode(0, $itemSelector, $type, self::TAX_REGULAR);
	}
	/**
	 * calculate the tax for $amount using the effective rates in the response.
	 * @param  float        $amount
	 * @param  Varien_Object $itemSelector
	 * @param  int           $type
	 * @return float
	 */
	public function getTaxForAmount($amount, Varien_Object $itemSelector, $type=self::MERCHANDISE_TYPE)
	{
		return $this->_calcTaxByMode($amount, $itemSelector, $type, self::TAX_REGULAR_FOR_AMOUNT);
	}

	public function getTaxResponse()
	{
		return $this->getData('tax_response') ?: Mage::getModel('eb2ctax/response');
	}
	/**
	 * @param Varien_Object $itemSelector
	 * @param int $type One of the values of these constants [MERCHANDISE_TYPE, SHIPPING_TYPE, DUTY_TYPE]
	 * @return float the total tax amount for any discounts
	 */
	public function getDiscountTax(Varien_Object $itemSelector, $type=self::MERCHANDISE_TYPE)
	{
		return $this->_calcTaxByMode(0, $itemSelector, $type, self::TAX_DISCOUNT);
	}
	/**
	 * @param float $amount The amount to calculate tax on
	 * @param Varien_Object $itemSelector
	 * @param int $type One of the values of these constants [MERCHANDISE_TYPE, SHIPPING_TYPE, DUTY_TYPE]
	 * @return float the total tax amount for any discounts
	 */
	public function getDiscountTaxForAmount($amount, Varien_Object $itemSelector, $type=self::MERCHANDISE_TYPE)
	{
		return $this->_calcTaxByMode($amount, $itemSelector, $type, self::TAX_DISCOUNT_FOR_AMOUNT);
	}
	/**
	 * if $address is null and a current request exists, return the existing request.
	 * if $address is not null return a new request using the quote's data.
	 * otherwise return a new invalid request.
	 * @param Mage_Sales_Model_Quote_Address $address
	 * @return EbayEnterprise_Eb2cTax_Model_Request a tax request object
	 */
	public function getTaxRequest(Mage_Sales_Model_Quote_Address $address=null)
	{
		if ($address) {
			// delete old response/request
			$this->unsTaxResponse();
			return Mage::getModel('eb2ctax/request')
				->processAddress($address);
		}
		$response = $this->getTaxResponse();
		if ($response) {
			$request = $response->getRequest();
			if ($request) {
				return $request;
			}
		}
		return Mage::getModel('eb2ctax/request');
	}
	/**
	 * store the tax response from eb2c
	 * @param EbayEnterprise_Eb2cTax_Model_Response $response
	 * @return self
	 */
	public function setTaxResponse(EbayEnterprise_Eb2cTax_Model_Response $response=null)
	{
		return $this->setData('tax_response', $response);
	}
	/**
	 * Unset the tax response from eb2c
	 * @return self
	 */
	public function unsTaxResponse()
	{
		return $this->unsetData('tax_response');
	}
	/**
	 * return the response data for the specified item.
	 * @param  Mage_Sales_Model_Quote_Item $item
	 * @param  Mage_Salse_Model_Quote_Address $address
	 * @return EbayEnterprise_Eb2cTax_Model_Response_Orderitem
	 */
	protected function _getItemResponse(Mage_Sales_Model_Quote_Item $item=null, Mage_Sales_Model_Quote_Address $address=null)
	{
		$response = $this->getTaxResponse();
		return $response && $response->isValid() ?
			$response->getResponseForItem($item, $address) : Mage::getModel('eb2ctax/response_orderitem');
	}
	/**
	 * Get the tax rates that have been applied for the item.
	 *
	 * @param  Varien_Object $itemSelector A wrapper object for an item and address
	 * @return array               The tax rates that have been applied to the item.
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 */
	public function getAppliedRates($itemSelector)
	{
		$helper       = Mage::helper('eb2ctax');
		$item         = $itemSelector->getItem();
		$address      = $itemSelector->getAddress();
		$store        = $address->getQuote()->getStore();
		$result       = array();
		$itemResponse = $this->_getItemResponse($item, $address);
		if ($itemResponse) {
			foreach ($itemResponse->getTaxQuotes() as $index => $taxQuote) {
				$taxRate = $taxQuote->getEffectiveRate();
				$code    = $taxQuote->getCode();
				$id      = $code . '-' . $taxRate;

				switch($taxQuote->getType()) {
					case $taxQuote::MERCHANDISE:
						$typeStr = 'Merchandise';
						break;
					case $taxQuote::SHIPPING:
						$typeStr = 'Shipping';
						break;
					case $taxQuote::DUTY:
						$typeStr = 'Duty';
						break;
				}

				if (isset($result[$id])) {
					$group = $result[$id];
				} else {
					$group = array();
					$group['id']      = $id;
					$group['percent'] = $taxRate * 100.0;
					$group['amount']  = 0;
					$group['base_amount'] = 0;
					$group['rates']   = array();
				}
				$rate = array();
				$rate['code']        = $typeStr . '-' . $code;
				$rate['title']       = $helper->__($code);
				$rate['percent']     = $taxRate * 100.0;
				$rate['base_amount'] = $taxQuote->getCalculatedTax();
				$rate['amount']      = $store->convertPrice($rate['base_amount']);
				$rate['position']    = 1;
				$rate['priority']    = 1;
				$group['rates'][]    = $rate;
				$group['amount']     += $rate['amount'];
				$group['base_amount'] += $rate['base_amount'];
				$result[$id]         = $group;
			}
			// add rate for any duty amounts
			if ($itemResponse->getDutyAmount()) {
				$id = $helper->taxDutyAmountRateCode($store);
				$group = array();
				$group['id']      = $id;
				$group['percent'] = null;
				$group['amount']  = 0;
				$group['base_amount'] = 0;
				$rate = array();
				$rate['code']        = $id;
				$rate['title']       = $helper->__($id);
				$rate['percent']     = null;
				$rate['base_amount'] = $itemResponse->getDutyAmount();
				$rate['amount']      = $store->convertPrice($rate['base_amount']);
				$rate['position']    = 1;
				$rate['priority']    = 1;
				$group['rates'][]    = $rate;
				$group['amount']     += $rate['amount'];
				$group['base_amount'] += $rate['base_amount'];
				$result[$id]         = $group;
			}
			if ($helper->getApplyTaxAfterDiscount($store)) {
				foreach($itemResponse->getTaxQuoteDiscounts() as $index => $discountQuote) {
					$taxRate = $discountQuote->getEffectiveRate();
					$code    = $discountQuote->getCode();
					$id      = "{$code}-{$taxRate}";
					if (isset($result[$id])) {
						$group = $result[$id];
					} else {
						continue;
					}
					$rate                = $group['rates'][0];
					$rate['base_amount'] -= $discountQuote->getCalculatedTax();
					$rate['amount']      = $store->convertPrice($rate['base_amount']);
					$group['amount']     = $rate['amount'];
					$group['rates'][0]   = $rate;
					$result[$id]         = $group;
				}
			}
		}
		return $result;
	}
	/**
	 * @codeCoverageIgnore
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getRateRequest($shippingAddress=null, $billingAddress=null, $customerTaxClass='', $store=null)
	{
		return $this->getTaxRequest();
	}
	/**
	 * @codeCoverageIgnore
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getRate($request)
	{
		return 0.0;
	}
}