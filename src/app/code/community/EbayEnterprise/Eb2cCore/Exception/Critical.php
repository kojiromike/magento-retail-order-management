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
 * This exception should get noticed.
 */
class EbayEnterprise_Eb2cCore_Exception_Critical extends EbayEnterprise_Eb2cCore_Exception
{
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $_logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $_context;

    /**
     * @link http://www.php.net/manual/en/class.exception.php
     */
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        $this->_logger = Mage::helper('ebayenterprise_magelog');
        $this->_context = Mage::helper('ebayenterprise_magelog/context');
        /**
         * @note This runs counter to our styleguide because it is
         * itself an exception. Furthermore we want to be both
         * inescapable and verbose with critical exceptions.
         */
        $this->_logger->critical($message, $this->_context->getMetaData(__CLASS__, [], $previous));
        parent::__construct($message, $code, $previous);
    }
}
