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
class EbayEnterprise_Eb2cPayment_Test_Model_Paypal_Adminhtml_ConfigTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	protected $_store;
	protected $_website;
	protected $_defaultWebsite;
	protected $_mageConfig;
	private $_oldStores;

	public function setUp()
	{
		parent::setUp();
		// disable the store and website constructors to prevent the 'this is not a controller test' error.
		$this->_store = $this->getModelMockBuilder('core/store')->disableOriginalConstructor()->setMethods(array('getId', 'getConfig'))->getMock();
		$this->_store->addData(array('code' => 'store1'));
		$this->_store->expects($this->any())->method('getId')->will($this->returnValue(1));
		$this->_website = $this->getModelMockBuilder('core/website')->disableOriginalConstructor()->setMethods(array('getId', 'getConfig'))->getMock();
		$this->_website->addData(array('code' => 'web1'));
		$this->_website->expects($this->any())->method('getId')->will($this->returnValue(2));
		$this->_defaultWebsite = $this->getModelMockBuilder('core/website')->disableOriginalConstructor()->setMethods(array('getId', 'getConfig'))->getMock();
		$this->_defaultWebsite->addData(Mage::app()->getWebsite(0)->getData());
		$this->_defaultWebsite->expects($this->any())->method('getId')->will($this->returnValue(0));
		$this->_mageConfig = $this->getModelMock('core/config', array('saveConfig'));
		// add the store and website to mage so it doesn't crash when we try
		// to get them
		$this->_oldStores = EcomDev_Utils_Reflection::getRestrictedPropertyValue(Mage::app(), '_stores');
		$this->_oldWebsites = EcomDev_Utils_Reflection::getRestrictedPropertyValue(Mage::app(), '_websites');
		$websites = array(0 => $this->_defaultWebsite, 'web1' => $this->_website);
		EcomDev_Utils_Reflection::setRestrictedPropertyValues(Mage::app(), array(
			'_stores' => array_merge($this->_oldStores, array('store1' => $this->_store)),
			'_websites' => array_replace($this->_oldWebsites, $websites),
		));
	}
	// restore the store and websites.
	public function tearDown()
	{
		EcomDev_Utils_Reflection::setRestrictedPropertyValues(Mage::app(), array(
			'_stores' => $this->_oldStores, '_websites' => $this->_oldWebsites
		));
		parent::tearDown();
	}
	/**
	 * verify the payment action is saved to the correct scope.
	 * @covers EbayEnterprise_Eb2cPayment_Model_Paypal_Adminhtml_Config::applyExpressPaymentAction
	 * @covers EbayEnterprise_Eb2cPayment_Model_Paypal_Adminhtml_Config::_determineScope
	 * @covers EbayEnterprise_Eb2cPayment_Model_Paypal_Adminhtml_Config::_isPayPalExpressEnabled
	 * @covers EbayEnterprise_Eb2cPayment_Model_Paypal_Adminhtml_Config::__construct
	 * @dataProvider dataProvider
	 */
	public function testApplyExpressPaymentAction($store, $website, $scope, $scopeId)
	{
		$this->_store->expects($this->any())->method('getConfig')->will($this->returnValue(true));
		$this->_website->expects($this->any())->method('getConfig')->will($this->returnValue(true));
		$this->_defaultWebsite->expects($this->any())->method('getConfig')->will($this->returnValue(true));
		$this->_mageConfig->expects($this->once())
			->method('saveConfig')
			->with(
				$this->identicalTo('payment/' . Mage_Paypal_Model_Config::METHOD_WPP_EXPRESS . '/payment_action'),
				$this->identicalTo(Mage_Paypal_Model_Config::PAYMENT_ACTION_ORDER),
				$this->identicalTo($scope),
				$this->identicalTo($scopeId)
			)
			->will($this->returnSelf());

		$adminConfig = Mage::getModel('eb2cpayment/paypal_adminhtml_config');
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($adminConfig, '_mageConfig', $this->_mageConfig);
		$adminConfig->applyExpressPaymentAction($store, $website);
	}
	/**
	 * nothing is done if payments or paypal express is disabled.
	 * @covers EbayEnterprise_Eb2cPayment_Model_Paypal_Adminhtml_Config::applyExpressPaymentAction
	 * @covers EbayEnterprise_Eb2cPayment_Model_Paypal_Adminhtml_Config::_isPayPalExpressEnabled
	 * @dataProvider dataProvider
	 */
	public function testApplyExpressPaymentActionWhenDisabled($isPaymentEnabled, $isPaypalExpressEnabled)
	{
		// the saveConfig method should never get called.
		$this->_mageConfig->expects($this->never())->method('saveConfig');
		$adminConfig = Mage::getModel('eb2cpayment/paypal_adminhtml_config');
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($adminConfig, '_mageConfig', $this->_mageConfig);
		$this->_store->expects($this->any())->method('getConfig')->will($this->returnValueMap(
			array($adminConfig::PAYMENT_MODULE_ACTIVE_PATH, $isPaymentEnabled),
			array($adminConfig::PAYPAL_EXPRESS_ENABLE_PATH, $isPaypalExpressEnabled)
		));

		$adminConfig->applyExpressPaymentAction('store1', null);
	}
}
