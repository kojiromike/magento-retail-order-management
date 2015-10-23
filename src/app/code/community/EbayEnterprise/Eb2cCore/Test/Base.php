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

abstract class EbayEnterprise_Eb2cCore_Test_Base extends EcomDev_PHPUnit_Test_Case
{

    const EB2CCORE_CONFIG_REGISTRY_MODEL = 'eb2ccore/config_registry';

    public function getLocalFixture($key = null)
    {
        $fixture = $this->getFixture()->getStorage()->getLocalFixture();
        if (!is_null($key)) {
            if (isset($fixture[$key])) {
                $fixture = $fixture[$key];
            } else {
                throw new Exception("Unable to get fixture data for key [$key]");
            }
        }
        return $fixture;
    }

    /**
     * @todo Is this the best way to do this?
     * Mocks an Observer, use this as an configuration option as shown. Needed a 'null' stubObserver to bypass
     *  dispatch functionality in a specific test.
     *
     * config:
     *   global/events/Name_Of_Your_Dispatched_Event/observers/Your_Observers_Name/class: 'EbayEnterprise_YourTest_Class'
     *   global/events/Name_Of_Your_Dispatched_Event/observers/Your_Observers_Name/method: 'stubObserver'
     *
     */
    public function stubObserver()
    {
        return $this;
    }

    public function buildCoreConfigRegistry($userConfigValuePairs = [])
    {
        $configValuePairs = [
            // Core Values:
            'clientId'                => 'TAN-OS-CLI',
            'feedDestinationType'     => 'MAILBOX',
        ];

        // Replace and/ or add to the default configValuePairs if the user has supplied some config values
        foreach ($userConfigValuePairs as $configPath => $configValue) {
            $configValuePairs[$configPath] = $configValue;
        }

        // Build the array in the format returnValueMap wants
        $valueMap = [];
        foreach ($configValuePairs as $configPath => $configValue) {
            $valueMap[] = [$configPath, $configValue];
        }

        $mockConfig = $this->getModelMock(
            self::EB2CCORE_CONFIG_REGISTRY_MODEL,
            ['__get', 'setStore', 'addConfigModel']
        );
        $mockConfig->expects($this->any())
            ->method('__get')
            ->will($this->returnValueMap($valueMap));
        $mockConfig->expects($this->any())
            ->method('setStore')
            ->will($this->returnSelf());
        $mockConfig->expects($this->any())
            ->method('addConfigModel')
            ->will($this->returnSelf());

        return $mockConfig;
    }

    /**
     * Replaces the Magento eb2ccore/config_registry model.
     *
     * @param array name/ value map
     */
    public function replaceCoreConfigRegistry($userConfigValuePairs = [])
    {
        $this->replaceByMock('model', self::EB2CCORE_CONFIG_REGISTRY_MODEL, $this->buildCoreConfigRegistry($userConfigValuePairs));
    }

    /**
     * clears the config cache in the specified store.
     * @param  mixed $store a code, id, or model of a magento storm
     * @return null
     */
    public function clearStoreConfigCache($store = null)
    {
        $store = EcomDev_PHPUnit_Test_Case_Util::app()->getStore($store);
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($store, '_configCache', []);
    }

    /**
     * @return null
     */
    public function setUp()
    {
        EcomDev_PHPUnit_Test_Case_Util::setUp();
    }

    /**
     * performs the following cleanup tasks:
     * - discards fixtures
     * @return null
     */
    public function tearDown()
    {
        Mage::app()->setCurrentStore(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID);
        EcomDev_PHPUnit_Test_Case_Util::tearDown();
    }

    /**
     * @param  string $alias           model alias
     * @param  array  $methodActions   mapping of method name to a value or PHPUnit_Framework_MockObject_Stub
     * @return object                  mock object with specified methods mocked under the 'any' invokation constraint.
     */
    protected function _buildModelMock($alias, array $methods = [])
    {
        $mock = $this->getModelMock($alias, array_keys((array) $methods));
        foreach (array_filter((array) $methods) as $name => $will) {
            $mock->expects($this->any())
                ->method($name)
                ->will($will);
        }
        return $mock;
    }
    /**
     * Create a stub class for a session model and replace it in the Magento
     * factory. Returns the stub object. Expected to only be used for session
     * model instances but no real enforcement of that.
     * @param  string $classAlias
     * @param  array|null $methods Methods to be mocked on the object. Default to null for no methods to be replace.
     * @return Mage_Core_Model_Session_Abstract (stub)
     */
    protected function _replaceSession($classAlias, $methods = null)
    {
        $session = $this->getModelMockBuilder($classAlias)
            ->setMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();
        $this->replaceByMock('singleton', $classAlias, $session);
        return $session;
    }
    /**
     * Replace the checkout session object with a mock.
     * @param  array $methods Enables original methods usage if null
     * @return PHPUnit_Framework_MockObject_MockObject - the mock session model
     */
    protected function mockCheckoutSession($methods = null)
    {
        return $this->_replaceSession('checkout/session', $methods);
    }

    protected function _setupBaseUrl()
    {
        parent::setUp();
        $_SESSION = [];
        $_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
        $this->app()->getRequest()->setBaseUrl($_baseUrl);
    }

    protected function _mockCookie()
    {
        $cookieMock = $this->getModelMockBuilder('core/cookie')
            ->disableOriginalConstructor() // This one removes session_start and other methods usage
            ->setMethods(['set']) // Enables original methods usage, because by default it overrides all methods
            ->getMock();
        $cookieMock->expects($this->any())
            ->method('set')
            ->will($this->returnSelf());
        $this->replaceByMock('singleton', 'core/cookie', $cookieMock);
    }
    /**
     * Simple data provider function that will provide true then false.
     * @return array
     */
    public function provideTrueFalse()
    {
        return [[true], [false]];
    }
    /**
     * Build a simple event observer.
     * @param  array $magicData magic initializer array
     * @return Varien_Event_Observer
     */
    public function _buildEventObserver(array $magicData)
    {
        return new Varien_Event_Observer(['event' => new Varien_Event($magicData)]);
    }

    /**
     * Test that events are configured as expected.
     * (Set up a fixture and call this from your public test method.)
     *
     * @param string $area the area of event observer definition, possible values are global, frontend, adminhtml
     * @param string $eventName is the name of the event that should be observed
     * @param string $observerClassAlias observer class alias, for instance catalog/observer
     * @param string $observerMethod the method name that should be invoked for
     */
    protected function _testEventConfig($area, $eventName, $observerClassAlias, $observerMethod)
    {
        $config = Mage::getConfig();
        $observerClassName = $config->getGroupedClassName('model', $observerClassAlias);
        EcomDev_PHPUnit_Test_Case_Config::assertEventObserverDefined($area, $eventName, $observerClassAlias, $observerMethod);
        $this->assertTrue(
            method_exists($observerClassName, $observerMethod),
            "Expected method '$observerClassName::$observerMethod' is not defined."
        );
    }

    /**
     * Mark test skipped if module not installed
     *
     * @param string $module The name of a module to check
     * @return self
     */
    protected function requireModule($module)
    {
        if (!Mage::helper('core')->isModuleEnabled($module)) {
            $message = sprintf('Test Requires Module "%s".', $module);
            $this->markTestSkipped($message);
        }
        return $this;
    }

    /**
     * Mark test skipped if class does not exist.
     *
     * @param string $class the raw name of the class to check
     * @return self
     */
    protected function requireClass($class)
    {
        if (!class_exists($class)) {
            $message = sprintf('Test Requires Class "%s".', $class);
            $this->markTestSkipped($message);
        }
        return $this;
    }

    /**
     * Mark test skipped if helper does not exist.
     *
     * @param string $helper the Magento name of the helper to check
     * @return self
     */
    protected function requireHelper($helper)
    {
        $className = Mage::getConfig()->getHelperClassName($helper);
        return $this->requireClass($className);
    }

    /**
     * Mark test skipped if model does not exist.
     *
     * @param string $model the Magento name of the model to check
     * @return self
     */
    protected function requireModel($model)
    {
        $className = Mage::getConfig()->getModelClassName($model);
        return $this->requireClass($className);
    }
}
