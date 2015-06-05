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

use eBayEnterprise\RetailOrderManagement\Payload\Exception\InvalidPayload;

class EbayEnterprise_Amqp_Test_Model_RunnerTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /** @var EbayEnterprise_Eb2cCore_Helper_Data mock*/
    protected $_coreHelper;
    /** @var EbayEnterprise_Amqp_Helper_Data mock */
    protected $_helper;
    /** @var EbayEnterprise_Amqp_Helper_Config mock */
    protected $_amqpConfigHelper;
    /** @var eBayEnterprise\RetailOrderManagement\Api\IAmqpApi mock */
    protected $_sdk;
    /** @var eBayEnterprise\RetailOrderManagement\Payload\IPayloadIterator mock */
    protected $_payloadIterator;
    /** @var eBayEnterprise\RetailOrderManagement\Payload\IOrderEvent mock */
    protected $_payload;

    public function setUp()
    {
        $this->_coreHelper = $this->getHelperMock('eb2ccore/data');
        $this->_helper = $this->getHelperMock('ebayenterprise_amqp');
        $this->_amqpConfigHelper = $this->getHelperMock('ebayenterprise_amqp/config');
        $this->_sdk = $this->getMock('eBayEnterprise\RetailOrderManagement\Api\IAmqpApi');
        $this->_payloadIterator = $this->getMock('eBayEnterprise\RetailOrderManagement\Payload\IPayloadIterator');
        $this->_payload = $this->getMock('eBayEnterprise\RetailOrderManagement\Payload\OrderEvents\IOrderEvent');
        // suppressing the real session from starting
        $session = $this->getModelMockBuilder('core/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->replaceByMock('singleton', 'core/session', $session);
    }
    /**
     * Test looping though all of the necessary stores and queues to consume
     * messages from all configured queues.
     */
    public function testProcessQueues()
    {
        $runner = $this->getModelMock(
            'ebayenterprise_amqp/runner',
            array('_consumeQueue'),
            false,
            array(array('helper' => $this->_helper, 'amqp_config_helper' => $this->_amqpConfigHelper))
        );

        // stub out queue name configuration, won't matter which store as this is
        // currently only configured globally
        $config = $this->buildCoreConfigRegistry(array('queueNames' => array('q.One', 'q.Two')));
        $this->_helper->expects($this->any())
            ->method('getConfigModel')
            ->will($this->returnValue($config));
        // stub there to be two stores to process queues for
        $this->_amqpConfigHelper->expects($this->any())
            ->method('getQueueConfigurationScopes')
            ->will($this->returnValue(array(Mage::getModel('core/store'), Mage::getModel('core/store'))));

        // make sure _consumeQueue gets called for each queue that should be
        // processed, 2 stores, each with 2 queues so four times total
        $runner->expects($this->exactly(4))
            ->method('_consumeQueue')
            ->will($this->returnSelf());

        $runner->processQueues();
    }
    /**
     * Behavior test for consuming messages from the queue. Should get a new
     * AMQP API for the SDK, fetch messages from the queue and iterate over
     * messages, passing any valid messages to _dispatchPayload.
     */
    public function testConsumeQueue()
    {
        $store = Mage::getModel('core/store');
        $queueName = 'q.Test.Queue';

        $this->_helper->expects($this->once())
            ->method('getSdkAmqp')
            ->with($this->identicalTo($queueName), $this->identicalTo($store))
            ->will($this->returnValue($this->_sdk));
        $this->_sdk->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($this->_payloadIterator));

        // script the iterator to have one good payload
        $this->_payloadIterator->expects($this->exactly(2))
            ->method('valid')
            ->will($this->onConsecutiveCalls(true, false));
        $this->_payloadIterator->expects($this->once())
            ->method('current')
            ->will($this->returnValue($this->_payload));
        $this->_payloadIterator->expects($this->once())
            ->method('next');

        $runner = $this->getModelMock(
            'ebayenterprise_amqp/runner',
            array('_dispatchPayload'),
            false,
            array(array('helper' => $this->_helper, 'core_helper' => $this->_coreHelper))
        );
        $runner->expects($this->once())
            ->method('_dispatchPayload')
            ->with($this->identicalTo($this->_payload), $this->identicalTo($store))
            ->will($this->returnSelf());

        EcomDev_Utils_Reflection::invokeRestrictedMethod($runner, '_consumeQueue', array($queueName, $store));
    }
    /**
     * When the AMQP API gets an invalid messages, an exception will be thrown
     * when getting the payload from the iterator. In such cases, loop execution
     * should continue but the bad payload should not be sent to the event dispatch.
     */
    public function testConsumeQueueBadMessage()
    {
        $store = Mage::getModel('core/store');
        $queueName = 'q.Test.Queue';

        $this->_helper->expects($this->once())
            ->method('getSdkAmqp')
            ->with($this->identicalTo($queueName), $this->identicalTo($store))
            ->will($this->returnValue($this->_sdk));
        $this->_sdk->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($this->_payloadIterator));

        // script the iterator to have one bad payload
        $this->_payloadIterator->expects($this->exactly(2))
            ->method('valid')
            ->will($this->onConsecutiveCalls(true, false));
        $this->_payloadIterator->expects($this->once())
            ->method('current')
            ->will($this->throwException(new InvalidPayload('invlid payload')));
        $this->_payloadIterator->expects($this->once())
            ->method('next');

        $runner = $this->getModelMock(
            'ebayenterprise_amqp/runner',
            array('_dispatchPayload'),
            false,
            array(array('helper' => $this->_helper, 'core_helper' => $this->_coreHelper))
        );
        $runner->expects($this->never())
            ->method('_dispatchPayload');

        EcomDev_Utils_Reflection::invokeRestrictedMethod($runner, '_consumeQueue', array($queueName, $store));
    }
    /**
     * Test dispatching an appropriate event for a given payload
     */
    public function testDispatchPayloadEvents()
    {
        $this->_payload->expects($this->any())
            ->method('getEventType')
            ->will($this->returnValue('UnitTestMessageType'));
        $store = Mage::getModel('core/store');

        $runner = Mage::getModel('ebayenterprise_amqp/runner');
        EcomDev_Utils_Reflection::invokeRestrictedMethod($runner, '_dispatchPayload', array($this->_payload, $store));
        $prefix = EcomDev_Utils_Reflection::getRestrictedPropertyValue($runner, '_eventPrefix');
        $this->assertEventDispatched($prefix . '_unit_test_message_type');
    }
}
