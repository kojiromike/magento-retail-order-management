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

class EbayEnterprise_Eb2cOrder_Helper_Detail
{
	/**
	 * Cache of order detail response messages for orderId keys.
	 * As helpers are singletons, this cache should exist thoughout a request
	 * but no longer.
	 * @var array String responses from the order detail request
	 */
	protected $_orderDetailResponses = array();
	/**
	 * Generate a key for the order id pair. Order detail
	 * searches are based upon these two values so just need to make sure that
	 * given the same order id, the same response gets returned
	 * from the cache.
	 * @param  string $orderId
	 * @return string
	 */
	protected function _getOrderDetailCacheKey($orderId)
	{
		return sprintf('%s', $orderId);
	}
	/**
	 * Get a cached response for the order id if it exists.
	 * @param  string $orderId
	 * @return string|null cached response from the order detail request
	 */
	public function getCachedOrderDetailResponse($orderId)
	{
		$cacheKey = $this->_getOrderDetailCacheKey($orderId);
		return isset($this->_orderDetailResponses[$cacheKey]) ?
			$this->_orderDetailResponses[$cacheKey] :
			null;
	}
	/**
	 * Update the detail response cache. This will overwrite any previously set
	 * responses if given the same order id.
	 * @param  string $orderId
	 * @param  string $response
	 * @return self
	 */
	public function updateOrderDetailResponseCache($orderId, $response)
	{
		$this->_orderDetailResponses[$this->_getOrderDetailCacheKey($orderId)] = $response;
		return $this;
	}
}
