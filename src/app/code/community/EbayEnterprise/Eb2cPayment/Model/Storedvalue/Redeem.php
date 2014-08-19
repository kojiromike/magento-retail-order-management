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

class EbayEnterprise_Eb2cPayment_Model_Storedvalue_Redeem
{
	/** @var EbayEnterprise_MageLog_Helper_Data $_log */
	protected $_log;

	public function __construct()
	{
		$this->_log = Mage::helper('ebayenterprise_magelog');
	}
	/**
	 * Get gift card redeem from eb2c.
	 * @param string $pan Either a raw PAN or a token representing a PAN
	 * @param string $pin personal identification number or code associated with a gift card or gift certificate
	 * @param string $entityId sales/quote entity_id value
	 * @param string $amount amount to redeem
	 * @return string eb2c response to the request
	 */
	public function getRedeem($pan, $pin, $entityId, $amount)
	{
		$hlpr = Mage::helper('eb2cpayment');
		$uri = $hlpr->getSvcUri('get_gift_card_redeem', $pan);
		if ($uri === '') {
			$this->_log->logWarn('[%s] pan "%s" is not in any configured tender type bin.', array(__CLASS__, $pan));
			return '';
		}
		return Mage::getModel('eb2ccore/api')
			->setStatusHandlerPath(EbayEnterprise_Eb2cPayment_Helper_Data::STATUS_HANDLER_PATH)
			->request(
				$hlpr->buildRedeemRequest($pan, $pin, $entityId, $amount, false),
				$hlpr->getConfigModel()->xsdFileStoredValueRedeem,
				$uri
			);
	}
	/**
	 * Parse gift card Redeem response xml.
	 * @param string $storeValueRedeemReply the xml response from eb2c
	 * @return array, an associative array of response data
	 */
	public function parseResponse($storeValueRedeemReply)
	{
		$redeemData = array();
		if (trim($storeValueRedeemReply) !== '') {
			$doc = Mage::helper('eb2ccore')->getNewDomDocument();
			$doc->loadXML($storeValueRedeemReply);
			$redeemXpath = new DOMXPath($doc);
			$redeemXpath->registerNamespace('a', Mage::helper('eb2cpayment')->getXmlNs());
			$nodeOrderId = $redeemXpath->query('//a:PaymentContext/a:OrderId');
			$nodePaymentAccountUniqueId = $redeemXpath->query('//a:PaymentContext/a:PaymentAccountUniqueId');
			$nodeResponseCode = $redeemXpath->query('//a:ResponseCode');
			$nodeAmountRedeemed = $redeemXpath->query('//a:AmountRedeemed');
			$nodeBalanceAmount = $redeemXpath->query('//a:BalanceAmount');
			$redeemData = array(
				'orderId' => ($nodeOrderId->length)? (int) $nodeOrderId->item(0)->nodeValue: 0,
				'paymentAccountUniqueId' => ($nodePaymentAccountUniqueId->length)? (string) $nodePaymentAccountUniqueId->item(0)->nodeValue : null,
				'responseCode' => ($nodeResponseCode->length)? (string) $nodeResponseCode->item(0)->nodeValue : null,
				'amountRedeemed' => ($nodeAmountRedeemed->length)? (float) $nodeAmountRedeemed->item(0)->nodeValue : 0,
				'balanceAmount' => ($nodeBalanceAmount->length)? (float) $nodeBalanceAmount->item(0)->nodeValue : 0,
			);
		}
		return $redeemData;
	}
}
