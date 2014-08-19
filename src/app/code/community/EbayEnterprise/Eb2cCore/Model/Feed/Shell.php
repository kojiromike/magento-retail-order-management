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

/**
 * Eb2c Feed Shell
 *
 * Ensures that feeds instantiate appropriate models by fetching the feed, instantiating the model, and
 * validating that it implements the correct interface
 */
class EbayEnterprise_Eb2cCore_Model_Feed_Shell extends Varien_Object
{
	private $_availableFeeds;

	/** @var EbayEnterprise_MageLog_Helper_Data $_log */
	protected $_log;

	/**
	 * Loads the configured feed models.
	 */
	public function _construct()
	{
		parent::_construct();
		$this->_log = Mage::helper('ebayenterprise_magelog');
		$this->_availableFeeds = $this->hasFeedSet() ? $this->getFeedSet() : Mage::helper('eb2ccore/feed_shell')->getConfiguredFeedModels();
	}

	/**
	 * Instantiate and Validate that this model name implements 'EbayEnterprise_Eb2cCore_Model_Feed_Interface' - that ensure we'll
	 * have a processFeeds method. That will convince me that this is an OK feed processor.
	 *
	 * @param string $modelName a Model to validate
	 * @return bool null - Not a valid 'EbayEnterprise_Eb2cCore_Model_Feed_Interface'
	 * @return Mage::getModel() - valid model for feed processing.
	 */
	private function _validateModel($modelName)
	{
		try {
			$model = Mage::getModel($modelName);
		} catch (Exception $e) {
			$this->_log->logWarn('[%s] %s', array(__CLASS__, $e->getMessage()));
			return false;
		}
		if ($model) {
			$reflector = new ReflectionClass($model);
			if (!$reflector->implementsInterface('EbayEnterprise_Eb2cCore_Model_Feed_Interface')) {
				$this->_log->logWarn('[%s] "%s" does not implement appropriate interface(s).', array(__CLASS__, get_class($model)));
				return false;
			}
		}
		return $model;
	}

	/**
	 * Given a partial feed name (or full model/method name), return the approriate model for that feed.
	 * The name must resovle uniquely - if you pass just 'feed' for example, you'll fail - too many matches.
	 *
	 * @param $partialFeedName
	 * @return bool null - Not a valid 'EbayEnterprise_Eb2cCore_Model_Feed_Interface'
	 * @return bool ::getModel() - valid model for feed processing.
	 */
	public function getFeedModel($partialFeedName)
	{
		$searchArg = preg_replace('/\//', '\\/', $partialFeedName); // escape '/', as I *know* that's part of the full Model Name
		$match = preg_grep("/$searchArg/", $this->_availableFeeds);
		if( count($match) === 1 ) {
			$modelName = $match[key($match)];
			return $this->_validateModel($modelName);
		}
		return false;
	}

	/**
	 * Given a partial feed name, run the processFeedsMethod
	 *
	 * @param $partialFeedName
	 * @throws Mage_Core_Exception
	 * @return Number of files processed
	 */
	public function runFeedModel($partialFeedName)
	{
		$model = $this->getFeedModel($partialFeedName);
		if( !$model ) {
			throw Mage::exception('Mage_Core', 'No valid model found for feed type ' . $partialFeedName);
		}
		return $model->processFeeds();
	}

	/**
	 * Return a list of the configured feeds
	 *
	 * @return array List of Feeds configured
	 */
	public function listAvailableFeeds()
	{
		return $this->_availableFeeds;
	}
}
