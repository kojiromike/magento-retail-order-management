<?php
class TrueAction_Eb2c_Tax_Overrides_Model_Observer extends Mage_Tax_Model_Observer
{
	public function salesEventItemAdded(Varien_Event_Observer $observer)
	{
		Mage::log('salesEventItemAdded');
		$quote = $observer->getEvent()->getQuoteItem()->getQuote();
		$this->_fetchTaxDutyInfo($quote);
	}

	public function cartEventProductUpdated(Varien_Event_Observer $observer)
	{
		Mage::log('cartEventProductUpdated');
		$quote = $observer->getEvent()->getQuoteItem()->getQuote();
		$this->_fetchTaxDutyInfo($quote);
	}

	public function salesEventItemRemoved(Varien_Event_Observer $observer)
	{
		Mage::log('salesEventItemRemoved');
		$quote = $observer->getEvent()->getQuoteItem()->getQuote();
		$this->_fetchTaxDutyInfo($quote);
	}

	public function salesEventItemQtyUpdated(Varien_Event_Observer $observer)
	{
		Mage::log('salesEventItemQtyUpdated');
		$quote = $observer->getEvent()->getItem()->getQuote();
		$this->_fetchTaxDutyInfo($quote);
	}

	public function salesEventDiscountItem(Varien_Event_Observer $observer)
	{
		Mage::log('salesEventDiscountItem');
		$quote = $observer->getEvent()->getItem()->getQuote();
		$this->_fetchTaxDutyInfo($quote);
	}

    /**
     * Reset extra tax amounts on quote addresses before recollecting totals
     *
     * @param Varien_Event_Observer $observer
     * @return Mage_Tax_Model_Observer
     */
    public function quoteCollectTotalsBefore(Varien_Event_Observer $observer)
    {
		Mage::log('quoteCollectTotalsBefore');
        /* @var $quote Mage_Sales_Model_Quote */
        $quote = $observer->getEvent()->getQuote();
        foreach ($quote->getAllAddresses() as $address) {
            $address->setExtraTaxAmount(0);
            $address->setBaseExtraTaxAmount(0);
        }
        return $this;
    }

	protected function _fetchTaxDutyInfo($quote)
	{
		$request = Mage::helper('tax')->getCalculator()
			->getRateRequest(
				$quote->getBillingAddress(),
				$quote->getShippingAddress()
			);
		if ($request->isValid()) {
			$response = Mage::helper('tax')->sendRequest($request);
			Mage::helper('tax')->getCalculator()
				->setResponse($response);
		}
	}
}