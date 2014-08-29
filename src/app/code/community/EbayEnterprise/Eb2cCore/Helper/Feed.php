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

class EbayEnterprise_Eb2cCore_Helper_Feed extends Mage_Core_Helper_Abstract
{
	const FILETRANSFER_CONFIG_PATH = 'eb2ccore/feed';
	// MWS stands for "Magento Web Store" and won't change according to Eb2c.
	const DEST_ID = 'MWS';
	const DEST_ID_XPATH = '//MessageHeader/DestinationData/DestinationId[normalize-space()="%s"]';
	const EVENT_TYPE_XPATH = '//MessageHeader/EventType[normalize-space()="%s"]';

	const DEFAULT_HEADER_CONF = 'eb2ccore/feed/outbound/message_header';
	const FILE_NAME_CONF = 'eb2ccore/feed/outbound/file_name';
	const GIFTCARD_TENDER_CONFIG_PATH = 'eb2ccore/feed/gift_card_tender_code';

	const IMPORT_CONFIG_PATH = 'eb2ccore/feed/filetransfer_imports';
	const EXPORT_CONFIG_PATH = 'eb2ccore/feed/filetransfer_exports';
	const HEADER_RELATIVE_PATH = '/outbound/message_header';
	const KEY_OUTBOUND = 'outbound';
	const KEY_EVENT_TYPE = 'event_type';

	/** @var EbayEnterprise_MageLog_Helper_Data */
	protected $_log;

	public function __construct()
	{
		$this->_log = Mage::helper('ebayenterprise_magelog');
	}

	/**
	 * This method will derive a map of event type to header configuration path
	 * using the event type in the file transfer import/export configuration section.
	 * @return array
	 *         Example: array(
	 *             'ItemMaster' => 'eb2ccore/feed/filetransfer_imports/item_master/outbound/message_header',
	 *             'ImageMaster' => 'eb2ccore/feed/filetransfer_exports/image_master/outbound/message_header',
	 *             ...
	 *         )
	 */
	protected function _getEventTypeToHeaderConfigPath()
	{
		$headerMap = array();
		$paths = array(static::IMPORT_CONFIG_PATH, static::EXPORT_CONFIG_PATH);
		$relativeHeaderPath = static::HEADER_RELATIVE_PATH;
		$cfgModel = Mage::helper('eb2ccore')->getConfigModel();
		foreach ($paths as $path) {
			$cfgData = $cfgModel->getConfigData($path);
			foreach ($cfgData as $key => $cfg) {
				if (isset($cfg[static::KEY_OUTBOUND]) && isset($cfg[static::KEY_EVENT_TYPE])) {
					$headerMap[$cfg[static::KEY_EVENT_TYPE]] = $path . '/' . $key . $relativeHeaderPath;
				}
			}
		}
		return $headerMap;
	}

	/**
	 * Validate the event type.
	 * @param bool whether the event type matches for the given feed.
	 */
	private function _validateEventType($doc, $eventType)
	{
		$xpath = new DOMXPath($doc);
		$matches = $xpath->query(sprintf(self::EVENT_TYPE_XPATH, $eventType));
		if ($matches->length) {
			return true;
		} else {
			Mage::log(sprintf('[%s] Feed does not have "%s" EventType node.', __CLASS__, $eventType), Zend_Log::WARN);
			return false;
		}
	}

	/**
	 * Get a DateTime object for when the file was created. This will
	 * initially attempt to use the value of the CreateDateTimeNode
	 * in the XML contained in the file. If the node cannot be found in
	 * the file, the mtime of the file will be used. Should both methods
	 * fail to produce a usable DateTime, beginning of the Unix epoch will be used.
	 * @param string $filename path to the xml file
	 * @return DateTime
	 */
	public function getMessageDate($filename)
	{
		$messageDate = $this->_getDateTimeFromFeed($filename);
		if (!$messageDate) {
			$this->_log->logWarn('[%s] Unable to read the message date from file "%s"', array(__CLASS__, $filename));
			// When no CreateDateAndTime node found in the feed, fallback
			// to the file's mtime.
			$mtime = filemtime($filename);
			// Get a formatted date from the unix mtime, when filemtime
			// failed, the resulting date('c', false) call will result in
			// the same thing as date('c', 0), which is the expected final fallback value
			$messageDate = date('c', $mtime);
		}
		return $this->_getDateTimeForMessage($messageDate);
	}
	/**
	 * Get the value of the CreateDateAndTime node from the XML in the given file.
	 * If the node doesn't exist in the file, will return null.
	 * @param string $filename path to XML file
	 * @return string|null
	 */
	protected function _getDateTimeFromFeed($filename)
	{
		$reader = new XMLReader();
		$reader->open($filename);
		// the following 2 variables prevent the edge case where we get a large file
		// with a node depth < 2
		$elementsRead = 0;
		$maxElements = 2;
		// the date/time node is at depth 2
		$targetDepth = 2;
		// navigate to within the message header
		while ($reader->depth < $targetDepth && $elementsRead <= $maxElements && $reader->read()) {
			// ignore whitespace
			if ($reader->nodeType !== XMLReader::ELEMENT) {
				continue;
			}
		}
		$dateNode = null;
		// at this point we should be at the depth where the creation date is.
		// if we stopped on the node, then grab it
		if ($reader->localName === 'CreateDateAndTime') {
			$dateNode = $reader->expand();
		} elseif ($reader->next('CreateDateAndTime')) {
			// otherwise go to the next instance of it
			$dateNode = $reader->expand();
		}
		return $dateNode ? $dateNode->nodeValue : null;
	}
	/**
	 * Get a DateTime object for the given message date time.
	 * When an invalid date time is given, either an unrecognizable format or
	 * none at all, a DateTime object representing beginning of Unix epoch will be used
	 * @param string $messageDateTime Date and time of the message.
	 * @return DateTime
	 */
	protected function _getDateTimeForMessage($messageDateTime)
	{
		// Need to ensure this will *always* return a DateTime. Either the
		// strtotime or DateTime::createFromFormat may if the passed date time
		// is not parsable by strtotime or the time created falls outside unix time
		// (dates prior to unix epoch time 0 for example). If the two combined
		// functions are unable to produce a valid time, fall back to the unix
		// time `0` so something can be returned.
		return DateTime::createFromFormat('U', strtotime($messageDateTime)) ?:
			DateTime::createFromFormat('U', 0);
	}
	/**
	 * Ensure the Feed's event type matches.
	 * @param DOMDocument $doc, the loaded Dom xml feed
	 * @param string eventType - what event type caller is trying to process
	 *
	 * @return bool true if this matches our client id, false otherwise
	 */
	public function validateHeader($doc, $eventType)
	{
		return $this->_validateEventType($doc, trim($eventType));
	}

	/**
	 * call a class static method base on the meta data in the given array
	 * @param array $meta a composite array with class name and method to be executed
	 * @return string|null
	 */
	public function invokeCallback(array $meta)
	{
		if (empty($meta)) {
			return null;
		}
		$parameters = isset($meta['parameters'])? $meta['parameters'] : array();
		switch ($meta['type']) {
			case 'model':
				return call_user_func_array(array(Mage::getModel($meta['class']), $meta['method']), $parameters);
			case 'helper':
				return call_user_func_array(array(Mage::helper($meta['class']), $meta['method']), $parameters);
			case 'singleton':
				return call_user_func_array(array(Mage::getSingleton($meta['class']), $meta['method']), $parameters);
			default:
				return null;
		}
	}

	/**
	 * From the passed in event type determine if the event-type has a key in the
	 * derived event type to message header map. If the event type key doesn't
	 * exist simply return an empty array, otherwise proceed to pass the message header
	 * configuration path to build the message header base on configuration data.
	 * @param string $feedType known feed types are
	 *        (ItemMaster, ContentMaster, iShip, Pricing, ImageMaster, ItemInventories)
	 * @return array
	 */
	public function getHeaderConfig($feedType)
	{
		$type = $this->_getEventTypeToHeaderConfigPath();
		if (!isset($type[$feedType])) {
			return array();
		}
		$cfg = Mage::helper('eb2ccore')->getConfigModel();
		return $this->_doConfigTranslation(array_merge(
			$cfg->getConfigData(self::DEFAULT_HEADER_CONF),
			$cfg->getConfigData($type[$feedType])
		));
	}

	/**
	 * getting file name config data key and value
	 * @param string $feedType
	 * @return array
	 */
	public function getFileNameConfig($feedType)
	{
		return $this->_doConfigTranslation(array_merge(
			array('feed_type' => $feedType),
			Mage::helper('eb2ccore')->getConfigModel()->getConfigData(self::FILE_NAME_CONF)
		));
	}

	/**
	 * do configuration translation and mapping call
	 * @param array $mhc, key value mapped of configuration data
	 * @return array
	 */
	protected function _doConfigTranslation(array $mhc)
	{
		$data = array();
		foreach ($mhc as $key => $value) {
			$data[$key] = (is_array($value))? $this->invokeCallback($value) : $value;
		}
		return $data;
	}

	/**
	 * retrieve the store id from the config
	 * @return string
	 */
	public function getStoreId()
	{
		return Mage::helper('eb2ccore')->getConfigModel()->storeId;
	}

	/**
	 * retrieve the client id from the config
	 * @return string
	 */
	public function getClientId()
	{
		return Mage::helper('eb2ccore')->getConfigModel()->clientId;
	}

	/**
	 * retrieve the catalog id from the config
	 * @return string
	 */
	public function getCatalogId()
	{
		return Mage::helper('eb2ccore')->getConfigModel()->catalogId;
	}

	/**
	 * Generate a message id
	 * This is an arbitrary construct, designed only to pass XSD validation
	 * @return string
	 */
	public function getMessageId()
	{
		return sprintf("%-.20s", uniqid('M-',true));
	}

	/**
	 * Generate a correlation id
	 * This is an arbitrary construct, designed only to pass XSD validation
	 * @return string
	 */
	public function getCorrelationId()
	{
		return sprintf("%-.20s", uniqid('C-',true));
	}

	/**
	 * get current date and time
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function getCreatedDateTime()
	{
		return date('c');
	}

	/**
	 * get timestamp
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function getTimeStamp()
	{
		return Mage::getModel('core/date')->gmtDate('YmdHis', time());
	}

}
