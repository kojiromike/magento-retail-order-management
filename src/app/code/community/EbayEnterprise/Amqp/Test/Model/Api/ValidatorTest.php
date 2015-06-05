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

use eBayEnterprise\RetailOrderManagement\Api\Exception\ConnectionError;

class EbayEnterprise_Amqp_Test_Model_Api_ValidatorTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    const QUEUE_NAME = 'q.Event.Name';
    /** @var EbayEnterprise_Amqp_Helper_Api_Validator */
    protected $_validator;
    /** @var EbayEnterprise_Amqp_Helper_Data mock */
    protected $_amqpHelper;
    /** @var eBayEnterprise\RetailOrderManagement\Api\IAmqpSdk mock */
    protected $_amqpApi;

    public function setUp()
    {
        $this->_amqpApi = $this->getMock('eBayEnterprise\RetailOrderManagement\Api\IAmqpApi');
        $this->_amqpHelper = $this->getHelperMock('ebayenterprise_amqp/data');
        $this->_amqpHelper->expects($this->any())
            ->method('getSdkAmqp')
            ->will($this->returnValue($this->_amqpApi));
        $this->_validator = Mage::getModel('ebayenterprise_amqp/api_validator', array('helper' => $this->_amqpHelper));

        // suppressing the real session from starting
        $session = $this->getModelMockBuilder('core/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->replaceByMock('singleton', 'core/session', $session);
    }
    public function provideTestConnectionResults()
    {
        return array(
            array(false, true, 'EbayEnterprise_Amqp_Exception_Configuration_Exception'),
            array(true, false, 'EbayEnterprise_Amqp_Exception_Connection_Exception'),
            array(true, false, 'Exception'),
            array(true, true),
        );
    }
    /**
     * Test testing the AMQP connection
     * @param bool $isConfigValid
     * @param bool $isConnectionValid
     * @param string $exceptionType If an exception should be thrown, type of exception to throw
     * @dataProvider provideTestConnectionResults
     */
    public function testTestConnection($isConfigValid, $isConnectionValid, $exceptionType = 'Exception')
    {
        $validator = $this->getModelMock('ebayenterprise_amqp/api_validator', array('_validateConfiguration', '_validateConnection'));
        $configExpectation = $validator->expects($this->any())
            ->method('_validateConfiguration');
        $connectExpectation = $validator->expects($this->any())
            ->method('_validateConnection');

        // stub config and connection validations
        if ($isConfigValid) {
            $configExpectation->will($this->returnSelf());
        } else {
            $configExpectation->will($this->throwException(new $exceptionType('config failed')));
        }
        if ($isConnectionValid) {
            $connectExpectation->will($this->returnSelf());
        } else {
            $connectExpectation->will($this->throwException(new $exceptionType('connection failed')));
        }

        $resp = $validator->testConnection('example.com', 'user', 'secret');
        $this->assertSame(($isConfigValid && $isConnectionValid), $resp['success']);
    }
    /**
     * Provide params to configure the testValidateConnection test.
     * @return array
     */
    public function provideValidateConnectionParams()
    {
        return array(
            array(array(), false, false),
            array(array(self::QUEUE_NAME), false, false),
            array(array(self::QUEUE_NAME), true, false),
            array(array(self::QUEUE_NAME), true, true),
        );
    }
    /**
     * Test validating that a connection to the AMQP server can be made with the
     * provided configuration.
     * @param  array  $queues
     * @param  bool $openSuccess
     * @param  bool $connectionSuccess
     * @dataProvider provideValidateConnectionParams
     */
    public function testValidateConnection(array $queues = array(), $openSuccess, $connectionSuccess)
    {
        $hostname = 'example.com';
        $usename = 'name';
        $password = 'secret';
        // when no queues configured, should thrown a configuration exception
        if (empty($queues)) {
            $this->setExpectedException('EbayEnterprise_Amqp_Exception_Configuration_Exception');
        // when the connection fails to be made, should throw a connection exception
        } elseif (!$connectionSuccess) {
            $this->setExpectedException('EbayEnterprise_Amqp_Exception_Connection_Exception');
        }

        // mock the queues configuration
        $this->_amqpHelper->expects($this->any())
            ->method('getConfigModel')
            ->will($this->returnValue($this->buildCoreConfigRegistry(array('queueNames' => $queues))));

        // open connection may throw an exception if the connection fails to be made
        if (!$openSuccess) {
            $this->_amqpApi->expects($this->any())
                ->method('openConnection')
                ->will($this->throwException(new ConnectionError('test message')));
        } else {
            $this->_amqpApi->expects($this->any())
                ->method('openConnection')
                ->will($this->returnSelf());
        }

        $this->_amqpApi->expects($this->any())
            ->method('isConnected')
            ->will($this->returnValue($connectionSuccess));

        $this->assertSame(
            $this->_validator,
            EcomDev_Utils_Reflection::invokeRestrictedMethod($this->_validator, '_validateConnection', array($hostname, $usename, $password))
        );
    }
    /**
     * Provide admin configurations for the AMQP connection
     * @return array
     */
    public function provideConfigurations()
    {
        return array(
            array('', '', '', false),
            array('example.com', '', '', false),
            array('example.com', 'name', '', false),
            array('example.com', 'name', 'secret', true),
        );
    }
    /**
     * Test validating AMQP connection configuration.
     * @param  string $hostname
     * @param  string $username
     * @param  string $password
     * @param  bool $valid
     * @dataProvider provideConfigurations
     */
    public function testValidateConfiguration($hostname, $username, $password, $valid)
    {
        if (!$valid) {
            $this->setExpectedException('EbayEnterprise_Amqp_Exception_Configuration_Exception');
        }
        $this->assertSame(
            $this->_validator,
            EcomDev_Utils_Reflection::invokeRestrictedMethod($this->_validator, '_validateConfiguration', array($hostname, $username, $password))
        );
    }
}
