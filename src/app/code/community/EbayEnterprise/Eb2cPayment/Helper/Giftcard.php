<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class EbayEnterprise_Eb2cPayment_Helper_Giftcard
{
	/**
	 * With the passed in pan and pin parameter we make storedvalue balance request to
	 * ROM api. We parsed the response and update the passed in giftcardaccount
	 * object. If the data is new we save it otherwise we just leave it to be updated
	 * when redeeming the giftcard.
	 * @param string $pan
	 * @param string $pin
	 * @param Enterprise_GiftCardAccount_Model_Giftcardaccount $giftCard the gift card object
	 * @return self
	 */
	public function synchStorevalue($pan, $pin, Enterprise_GiftCardAccount_Model_Giftcardaccount $giftCard)
	{
		if (trim($pan) !== '' && trim($pin) !== '') {
			// only fetch eb2c stored value balance when both pan and pin is valid
			$balance = Mage::getModel('eb2cpayment/storedvalue_balance');
			$balanceData = $balance->parseResponse($balance->getBalance($pan, $pin));
			if (!empty($balanceData)) {
				$balanceData['pin'] = $pin;
				$balanceData['paymentAccountUniqueId'] = $pan; // the return pan might be tokenized.
				// making sure we have the right data
				$this->_updateGiftCard($giftCard, $balanceData);
			}
		}
		return $this;
	}
	/**
	 * Update Gift Card Account Data
	 * @param Enterprise_GiftCardAccount_Model_Giftcardaccount $giftCard the gift card object
	 * @param array $balanceData , the eb2c stored value balance data
	 * @return self
	 */
	protected function _updateGiftCard(Enterprise_GiftCardAccount_Model_Giftcardaccount $giftCard, array $balanceData)
	{
		$giftCard->unsDateExpires()
			->addData($this->_getExtractedData($balanceData));
		// only save when there's no record in Magento
		if (!$giftCard->getId()) {
			$giftCard->save();
		}
		return $this;
	}
	/**
	 * Get giftcard storedvalue extracted data
	 * @param array $balanceData
	 * @return array
	 */
	protected function _getExtractedData(array $balanceData)
	{
		return array(
			'code' => $balanceData['paymentAccountUniqueId'],
			'eb2c_pan' => $balanceData['paymentAccountUniqueId'],
			'eb2c_pin' => $balanceData['pin'],
			'status' => 1,
			'state' => 1,
			'balance' => (float) $balanceData['balanceAmount'],
			'is_redeemable' => 1,
			'website_id' => Mage::app()->getStore()->getWebsiteId()
		);
	}
}
