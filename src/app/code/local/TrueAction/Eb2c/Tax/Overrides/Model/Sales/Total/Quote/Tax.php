<?php
/**
 * Magento Enterprise Edition
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magento Enterprise Edition License
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.magentocommerce.com/license/enterprise-edition
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Tax
 * @copyright   Copyright (c) 2013 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Tax totals calculation model
 */
class TrueAction_Eb2c_Tax_Overrides_Model_Sales_Total_Quote_Tax extends Mage_Tax_Model_Sales_Total_Quote_Tax
{
	/**
	 * Class constructor
	 */
	public function __construct()
	{
		$this->setCode('tax');
		$this->_helper      = Mage::helper('tax');
		$this->_calculator  = Mage::getSingleton('tax/calculation');
		$this->_config      = Mage::getSingleton('tax/config');
	}

	/**
	 * Collect tax totals for quote address
	 *
	 * @param   Mage_Sales_Model_Quote_Address $address
	 * @return  Mage_Tax_Model_Sales_Total_Quote
	 */
	public function collect(Mage_Sales_Model_Quote_Address $address)
	{
		Mage_Sales_Model_Quote_Address_Total_Abstract::collect($address);
		$this->_roundingDeltas      = array();
		$this->_baseRoundingDeltas  = array();
		$this->_hiddenTaxes         = array();
		$address->setShippingTaxAmount(0);
		$address->setBaseShippingTaxAmount(0);
		// zero amounts for the address.
		$this->_store = $address->getQuote()->getStore();
		$customer = $address->getQuote()->getCustomer();

		if (!$address->getAppliedTaxesReset()) {
			$address->setAppliedTaxes(array());
		}

		// alias for $address->getAllNonNominalItems()
		// may need to use this in the request as well so that we are
		// using the same list for sending/receiving
		///////////////////////////////////////////////
		$items = $this->_getAddressItems($address);
		if (!count($items)) {
			return $this;
		}

		$this->_calcTaxForAddress($address);
		// adds extra tax amounts to the address
		$this->_addAmount($address->getExtraTaxAmount());
		$this->_addBaseAmount($address->getBaseExtraTaxAmount());

		// code in function can be moved out to here while rest goes to request
		$this->_calculateShippingTax($address, $request);


		$this->_processHiddenTaxes();

		//round total amounts in address
		$this->_roundTotals($address);
		return $this;
	}

	/**
	 * Calculate address total tax based on row total
	 *
	 * @param   Mage_Sales_Model_Quote_Address $address
	 * @param   Varien_Object $taxRateRequest
	 * @return  Mage_Tax_Model_Sales_Total_Quote
	 */
	protected function _calcTaxForAddress(Mage_Sales_Model_Quote_Address $address) {
		$items          = $this->_getAddressItems($address);
		$itemTaxGroups  = array();
		$itemSelector   = new Varien_Object(array('address' => $address));
		foreach ($items as $item) {
			if ($item->getParentItem()) {
				continue;
			}
			if ($item->getHasChildren() && $item->isChildrenCalculated()) {
				foreach ($item->getChildren() as $child) {
					$itemSelector->setItem($child);
					$this->_calcTaxForItem($itemSelector);
					$this->_addAmount($child->getTaxAmount());
					$this->_addBaseAmount($child->getBaseTaxAmount());
					$applied = $this->_calculator->getAppliedRates($itemSelector);

					// need to come up with a similar concept (ie hasTaxes or some such)
					if ($this->_calculator->getTaxforItem($child, $address) > 0) {
						$itemTaxGroups[$child->getId()] = $applied;
					}
					$this->_saveAppliedTaxes(
						$address,
						$applied,
						$child->getTaxAmount(),
						$child->getBaseTaxAmount(),
						$rateReplacemnt
					);
					$child->setTaxRates($applied);
				}
				$this->_recalculateParent($item);
			} else {
				$itemSelector->setItem($item);
				$this->_calcTaxForItem($itemSelector);
				$this->_addAmount($item->getTaxAmount());
				$this->_addBaseAmount($item->getBaseTaxAmount());
				$applied = $this->_calculator->getAppliedRates($itemSelector);
				if ($this->_calculator->getTaxforItem($item, $address) > 0) {
					$itemTaxGroups[$item->getId()] = $applied;
				}
				$this->_saveAppliedTaxes(
					$address,
					$applied,
					$item->getTaxAmount(),
					$item->getBaseTaxAmount(),
					$rateReplacemnt
				);
				$item->setTaxRates($applied);
			}
		}

		if ($address->getQuote()->getTaxesForItems()) {
			$itemTaxGroups += $address->getQuote()->getTaxesForItems();
		}
		$address->getQuote()->setTaxesForItems($itemTaxGroups);
		return $this;
	}

	/**
	 * Calculate item tax amount based on row total
	 *
	 * @param   Mage_Sales_Model_Quote_Item_Abstract $item
	 * @param   float $rate
	 * @return  Mage_Tax_Model_Sales_Total_Quote
	 */
	protected function _calcTaxForItem($itemSelector, $rate = null)
	{
		$item           = $itemSelector->getItem();
		$inclTax        = $item->getIsPriceInclTax();
		$subtotal       = $taxSubtotal     = $item->getTaxableAmount();
		$baseSubtotal   = $baseTaxSubtotal = $item->getBaseTaxableAmount();
		// default to 0 since there isn't any one rate
		$item->setTaxPercent(0);
		$hiddenTax      = null;
		$baseHiddenTax  = null;

		switch ($this->_helper->getCalculationSequence($this->_store)) {
			case Mage_Tax_Model_Calculation::CALC_TAX_BEFORE_DISCOUNT_ON_EXCL:
			case Mage_Tax_Model_Calculation::CALC_TAX_BEFORE_DISCOUNT_ON_INCL:
				$rowTax     = $this->_calculator->getTaxForItem($itemSelector->getItem(), $itemSelector->getAddress());
				$baseRowTax = $this->_calculator->getAmount($baseSubtotal, $itemSelector->getItem(), $itemSelector->getAddress());
				break;
			case Mage_Tax_Model_Calculation::CALC_TAX_AFTER_DISCOUNT_ON_EXCL:
			case Mage_Tax_Model_Calculation::CALC_TAX_AFTER_DISCOUNT_ON_INCL:
				// prices sent after adding discounts
				// taxes received are already reduced due to discounted prices sent

				$discountAmount           = $item->getDiscountAmount();
				$baseDiscountAmount       = $item->getBaseDiscountAmount();

				$rowTax                   = $this->_calculator->getTax($itemSelector);
				$rowTaxDiscount           = 0//$this->_calculator->getTaxDiscount($itemSelector);
				$rowTaxBeforeDiscount     = $rowTax + $rowTaxDiscount;

				$baseRowTax               = $this->_calculator->getTaxForItem($baseSubtotal, $itemSelector->getItem(), $itemSelector->getAddress());
				$baseRowTaxDiscount       = 0//$this->_calculator->getTaxDiscountAmount($baseDiscountAmount, $itemSelector->getItem(), $itemSelector->getAddress());
				$baseRowTaxBeforeDiscount = $baseRowTax + $baseRowTaxDiscount;

				$rowTax = $this->_calculator->round($rowTax);
				$baseRowTax = $this->_calculator->round($baseRowTax);
				if ($discountAmount > $subtotal) { // case with 100% discount on price incl. tax
                    $hiddenTax      = $discountAmount - $subtotal;
                    $baseHiddenTax  = $baseDiscountAmount - $baseSubtotal;
                    $this->_hiddenTaxes[] = array(
                        'rate_key'   => $rateKey,
                        'qty'        => 1,
                        'item'       => $item,
                        'value'      => $hiddenTax,
                        'base_value' => $baseHiddenTax,
                        'incl_tax'   => $inclTax,
                    );
                }
				break;
		}

		$item->setTaxAmount(max(0, $rowTax));
		$item->setBaseTaxAmount(max(0, $baseRowTax));

		$rowTotalInclTax = $item->getRowTotalInclTax();
		if (!isset($rowTotalInclTax)) {
			$taxCompensation = $item->getDiscountTaxCompensation() ? $item->getDiscountTaxCompensation() : 0;
			$item->setRowTotalInclTax($subtotal + $rowTax + $taxCompensation);
			$item->setBaseRowTotalInclTax($baseSubtotal + $baseRowTax + $item->getBaseDiscountTaxCompensation());
		}

		return $this;
	}

	/// most of this function can be merged up into the collect function
	////////////////
	/**
	 * Tax caclulation for shipping price
	 *
	 * @param   Mage_Sales_Model_Quote_Address $address
	 * @param   Varien_Object $taxRateRequest
	 * @return  Mage_Tax_Model_Sales_Total_Quote
	 */
	protected function _calculateShippingTax(Mage_Sales_Model_Quote_Address $address, $taxRateRequest = null)
	{
		// this should be moved to the request if needed
		// $taxRateRequest->setProductClassId($this->_config->getShippingTaxClass($this->_store));

		// dont need

		// $rate           = $this->_calculator->getRate($taxRateRequest);

		//////////////////////////////////////////////////////////////////////////
		// figure out these as they may need to be moved to the request.
		$shipping       = $address->getShippingTaxable();
		$baseShipping   = $address->getBaseShippingTaxable();
		//////////////////////////////////////////////////////////////////////////
		//$rateKey        = (string)$rate;

		$hiddenTax      = null;
		$baseHiddenTax  = null;
		/// this needs to be moved to before generating the request
		switch ($this->_helper->getCalculationSequence($this->_store)) {
			case Mage_Tax_Model_Calculation::CALC_TAX_BEFORE_DISCOUNT_ON_EXCL:
			case Mage_Tax_Model_Calculation::CALC_TAX_BEFORE_DISCOUNT_ON_INCL:
				$tax        = $this->_calculator->calcTaxAmount($shipping, $rate, $inclTax, false);
				$baseTax    = $this->_calculator->calcTaxAmount($baseShipping, $rate, $inclTax, false);
				break;
			case Mage_Tax_Model_Calculation::CALC_TAX_AFTER_DISCOUNT_ON_EXCL:
			case Mage_Tax_Model_Calculation::CALC_TAX_AFTER_DISCOUNT_ON_INCL:
				$discountAmount     = $address->getShippingDiscountAmount();
				$baseDiscountAmount = $address->getBaseShippingDiscountAmount();
				$tax = $this->_calculator->calcTaxAmount(
					$shipping - $discountAmount,
					$rate,
					$inclTax,
					false
				);
				$baseTax = $this->_calculator->calcTaxAmount(
					$baseShipping - $baseDiscountAmount,
					$rate,
					$inclTax,
					false
				);
				break;
		}

		//     $tax        = $this->_calculator->round($tax);
		//     $baseTax    = $this->_calculator->round($baseTax);
		//     $this->_addAmount(max(0, $tax));
		//     $this->_addBaseAmount(max(0, $baseTax));

		//////// after taxes so can be removed
		// if ($inclTax && !empty($discountAmount)) {
		//     $hiddenTax      = $this->_calculator->calcTaxAmount($discountAmount, $rate, $inclTax, false);
		//     $baseHiddenTax  = $this->_calculator->calcTaxAmount($baseDiscountAmount, $rate, $inclTax, false);
		//     $this->_hiddenTaxes[] = array(
		//         'rate_key'   => $rateKey,
		//         'value'      => $hiddenTax,
		//         'base_value' => $baseHiddenTax,
		//         'incl_tax'   => $inclTax,
		//     );
		// }

		// this needs to be the sum of the shipping tax lines
		$address->setShippingTaxAmount(max(0, $tax));
		$address->setBaseShippingTaxAmount(max(0, $baseTax));
		$applied = $this->_calculator->getAppliedRates($taxRateRequest);
		$this->_saveAppliedTaxes($address, $applied, $tax, $baseTax, $rate);

		return $this;
	}

	/**
	 * Check if price include tax should be used for calculations.
	 * We are using price include tax just in case when catalog prices are including tax
	 * and customer tax request is same as store tax request
	 *
	 * @param $store
	 * @return bool
	 * @codeCoverageIgnore
	 */
	protected function _usePriceIncludeTax($store)
	{
		return false;
	}
}
