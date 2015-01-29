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

class EbayEnterprise_Eb2cFraud_Helper_Http extends Mage_Core_Helper_Http
{
	const COOKIES_DELIMITER = ';';
	// Relative path where scripts are stored
	const JSC_JS_PATH = 'ebayenterprise_eb2cfraud';
	// Form field name that will contain the name of the randomly selected JSC
	// form field. Used to find the generated JSC data in the POST data
	const JSC_FIELD_NAME = 'eb2cszyvl';

	/** @var Mage_Core_Model_Cookie */
	protected $_cookie;
	/** @var string */
	protected $_jscUrl;

	/**
	 * inject dependencies
	 * @param array
	 */
	public function __construct(array $args=[])
	{
		list($this->_cookie) =
			$this->_checkTypes(
				$this->_nullCoalesce('cookie', $args, Mage::getSingleton('core/cookie'))
			);
		$this->_jscUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS, true) . self::JSC_JS_PATH;
	}

	/**
	 * return $ar[$key] if it exists otherwise return $default
	 * @param  string
	 * @param  array
	 * @param  mixed
	 * @return mixed
	 */
	protected function _nullCoalesce($key, array $ar, $default)
	{
		return isset($ar[$key]) ? $ar[$key] : $default;
	}

	/**
	 * ensure correct types
	 * @param Mage_Core_Model_Cookie
	 * @return array
	 */
	protected function _checkTypes(Mage_Core_Model_Cookie $cookie)
	{
		return [$cookie];
	}


	/**
	 * Retrieve HTTP Accept header
	 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.1
	 * @param bool $clean clean non UTF-8 characters
	 * @return string
	 */
	public function getHttpAccept($clean=true)
	{
		return $this->_getHttpCleanValue('HTTP_ACCEPT', $clean);
	}

	/**
	 * Retrieve HTTP Accept-Encoding header
	 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.3
	 * @param bool $clean clean non UTF-8 characters
	 * @return string
	 */
	public function getHttpAcceptEncoding($clean=true)
	{
		return $this->_getHttpCleanValue('HTTP_ACCEPT_ENCODING', $clean);
	}

	/**
	 * Retrieve HTTP Accept-Language header
	 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.4
	 * @param bool $clean clean non UTF-8 characters
	 * @return string
	 */
	public function getHttpAcceptLanguage($clean=true)
	{
		return $this->_getHttpCleanValue('HTTP_ACCEPT_LANGUAGE', $clean);
	}

	/**
	 * Retrieve HTTP Connection header
	 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.10
	 * @param bool $clean clean non UTF-8 characters
	 * @return string
	 */
	public function getHttpConnection($clean=true)
	{
		return $this->_getHttpCleanValue('HTTP_CONNECTION', $clean);
	}

	/**
	 * Retrieve the remote client's host name
	 *
	 * @return string
	 */
	public function getRemoteHost()
	{
		return gethostbyaddr($this->getRemoteAddr(false));
	}

	/**
	 * return a string representation of the given cookie array
	 * @return string
	 */
	public function getCookiesString()
	{
		$cookies = $this->_cookie->get();
		return implode(self::COOKIES_DELIMITER, array_map(function ($key, $value) {
			return "$key=$value";
		}, array_keys($cookies), $cookies));
	}

	/**
	 * get url to our JavaScript
	 * @return string
	 */
	public function getJscUrl()
	{
		return $this->_jscUrl;
	}

	/**
	 * Find the generated JS data from the given request's POST data. This uses
	 * a known form field in the POST data, self::JSC_FIELD_NAME, to find the
	 * form field populated by the JS collector. As the form field populated is
	 * selected at random, this mapping is the only way to find the data
	 * populated by the collector.
	 * @param  Mage_Core_Controller_Request_Http
	 * @return string
	 */
	public function getJavaScriptFraudData()
	{
		$request = $this->_getRequest();
		return $request->getPost($request->getPost(static::JSC_FIELD_NAME, ''), '');
	}
}
