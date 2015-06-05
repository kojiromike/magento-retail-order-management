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
 * @codeCoverageIgnore Mostly side-effects, little that can be covered well by unit tests
 */
class EbayEnterprise_Amqp_Block_System_Config_Testamqpconnection extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    const VALIDATION_URL = '*/exchange_system_config_validate/validateamqp';
    /** @var string */
    protected $_template = 'ebayenterprise_amqp/system/config/testamqpconnection.phtml';
    /** @var EbayEnterprise_Amqp_Helper_Data */
    protected $_amqpHelper;

    protected function _construct()
    {
        list($this->_amqpHelper) = $this->_checkTypes(
            $this->getAmqpHelper() ?: Mage::helper('ebayenterprise_amqp')
        );
    }
    /**
     * Type hints for injected dependencies via the self::__construct $args
     * @param  EbayEnterprise_Amqp_Helper_Data $amqpHelper
     * @return mixed[]
     */
    protected function _checkTypes(EbayEnterprise_Amqp_Helper_Data $amqpHelper)
    {
        return array($amqpHelper);
    }
    /**
     * Unset some non-related element parameters
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Get the button and scripts contents
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $originalData = $element->getOriginalData();
        $this->addData(array(
            'button_label' => $this->escapeHtml($this->_amqpHelper->__($originalData['button_label'])),
            'html_id' => $element->getHtmlId(),
            'ajax_url' => $this->getUrl(self::VALIDATION_URL, array('_current' => true))
        ));

        return $this->_toHtml();
    }
}
