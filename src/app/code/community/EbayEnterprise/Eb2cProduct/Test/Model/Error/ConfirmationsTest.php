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

class EbayEnterprise_Eb2cProduct_Test_Model_Error_ConfirmationsTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * Test initFeed method with the following assumptions when call with 'ItemMaster' as a parameter
	 * Expectation 1: the method EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::append will be call three times
	 *                once with the xml directive, second with the open root node and third with the content
	 *                return from calling the eb2cproduct helper method (generateMessageHeader)
	 * Expectation 2: the method EbayEnterprise_Eb2cProduct_Helper_Data::generateMessageHeader will be called once with
	 *              the parameter 'ItemMaster' it will return some hard-coded message header string of nodes
	 * Expectation 3: the method EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::initFeed will return itself
	 * @mock EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::append
	 * @mock rueAction_Eb2cProduct_Helper_Data::generateMessageHeader
	 * @param string $headerMessage the header message response from the mocking generateMessageHeader
	 * @dataProvider dataProvider
	 */
	public function testInitFeed($headerMessage)
	{
		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('generateMessageHeader'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('generateMessageHeader')
			->with($this->equalTo('ItemMaster'))
			->will($this->returnValue($headerMessage));

		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$confirmationsModelMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array('append'))
			->getMock();
		$confirmationsModelMock->expects($this->at(0))
			->method('append')
			->with($this->equalTo(EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::XML_DIRECTIVE))
			->will($this->returnSelf());
		$confirmationsModelMock->expects($this->at(1))
			->method('append')
			->with($this->equalTo(EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::XML_OPEN_ROOT_NODE))
			->will($this->returnSelf());
		$confirmationsModelMock->expects($this->at(2))
			->method('append')
			->with($this->equalTo($headerMessage))
			->will($this->returnSelf());

		$this->assertSame($confirmationsModelMock, $confirmationsModelMock->initFeed('ItemMaster'));
	}

	/**
	 * Test append method with the following assumptions when call with given content as a parameter
	 * Expectation 1: the class property EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::_fileStream will be checked if
	 *                it is not is null it will throw EbayEnterprise_Eb2cProduct_Model_Error_Exception exception,
	 *                that is why in this set we are setting the class property EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::_fileStream
	 *                to a know state of null so that will throw the EbayEnterprise_Eb2cProduct_Model_Error_Exception as annotated for this test
	 * @param string $content the content to be appended to the file in error confirmation class stream
	 * @dataProvider dataProvider
	 * @expectedException EbayEnterprise_Eb2cProduct_Model_Error_Exception
	 */
	public function testAppendInvalidFileStreamException($content)
	{
		$confirmations = Mage::getModel('eb2cproduct/error_confirmations');

		// set the class property '_fileStream' to a known state of null
		$this->_reflectProperty($confirmations, '_fileStream')->setValue($confirmations, null);

		$confirmations->append($content);
	}
	/**
	 * Test addMessage method with the following assumptions when call with given message template ($msgTemplate) and message ($message) as parameters
	 * Expectation 1: the method EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::_getLangCode will be called once and will return the language code (en-us)
	 *                to be set in the array key (language_code) and then the parameters ($msgTemplate, $message) will be passed to the built-in sprint method
	 *                to be set in the array key (message_data).
	 * Expectation 2: the array that was built in expectation one will then be passed as the first parameter to the class method
	 *                EbayEnterprise_Eb2cProduct_Helper_Data::mapPattern, and the second parameter is class constant for the error message node with mapped
	 *                place holder for the value in the first parameter
	 * Expectation 3: the return value from the mock method EbayEnterprise_Eb2cProduct_Helper_Data::mapPattern will be set to the class property
	 *                EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::_queueMessage as an array element, to prove this occurred the class property
	 *                is set to an empty array in the test, and is then proven to have an array element of the content the mapPattern return
	 * Expectation 4: the method EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::addMessage will return itself as asserted in the test
	 * @mock EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::_getLangCode
	 * @mock EbayEnterprise_Eb2cProduct_Helper_Data::mapPattern
	 */
	public function testAddMessage()
	{
		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('mapPattern'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('mapPattern')
			->with($this->isType('array'))
			->will($this->returnValue('<Message xml:lang="en-US" code="">Error exception occurred: UnitTest Simulate Throw Exception on Dom load</Message>'));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$confirmationsModelMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array('_getLangCode'))
			->getMock();
		$confirmationsModelMock->expects($this->once())
			->method('_getLangCode')
			->will($this->returnValue('en-us'));
		$this->replaceByMock('model', 'eb2cproduct/error_confirmations', $confirmationsModelMock);

		$confirmations = Mage::getModel('eb2cproduct/error_confirmations');
		// class property _queueMessage to a known state
		$this->_reflectProperty($confirmations, '_queueMessage')->setValue($confirmations, array());

		$this->assertInstanceOf('EbayEnterprise_Eb2cProduct_Model_Error_Confirmations', $confirmations->addMessage(
			$confirmations::SKU_NOT_REMOVE, 'UnitTest Simulate Throw Exception on Dom load'
		));

		$this->assertSame(
			array('<Message xml:lang="en-US" code="">Error exception occurred: UnitTest Simulate Throw Exception on Dom load</Message>'),
			$this->_reflectProperty($confirmations, '_queueMessage')->getValue($confirmations)
		);
	}

	/**
	 * Test addError method with the following assumptions when call with given message template ($type) and message ($fileName) as parameters
	 * Expectation 1: the method EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::_getErrorTemplate will be called once with
	 *                for following parameter (ItemMaster, ItemMaster_TestSubset.xml), and it will return an array of keys and values
	 * Expectation 2: the value return from mock method EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::_getErrorTemplate will then pass as the first
	 *                parameter to class method EbayEnterprise_Eb2cProduct_Helper_Data::mapPattern, and the second parameter is class constant for the error
	 *                open node with mapped place holder for the values
	 * Expectation 3: the return value from EbayEnterprise_Eb2cProduct_Helper_Data::mapPattern will be mapped as the first place holder string format for the
	 *                sprintf method, the second parameter implode the class property EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::_queueMessage, and
	 *                the third parameter is a class constant for the error close node.
	 * Expectation 4: the test set the class property EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::_queueMessage to a know state with array with
	 *                element on it and after the addError run the _queueMessage class property is asserted to be empty as expected.
	 * Expectation 5: the test set the class property EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::_queueError of an empty array and when addError
	 *                is run the property is asserted to have array elements as expected
	 * @mock EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::_getErrorTemplate
	 * @mock EbayEnterprise_Eb2cProduct_Helper_Data::mapPattern
	 */
	public function testAddError()
	{
		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('mapPattern'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('mapPattern')
			->with($this->isType('array'))
			->will($this->returnValue('<Error event_type="ItemMaster" file_name="ItemMaster_TestSubset.xml" reported_from="WMS">'));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$confirmationsModelMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array('_getErrorTemplate'))
			->getMock();
		$confirmationsModelMock->expects($this->once())
			->method('_getErrorTemplate')
			->with($this->equalTo('ItemMaster'), $this->equalTo('ItemMaster_TestSubset.xml'))
			->will($this->returnValue(array(
				'feed_type' => 'ItemMaster',
				'feed_file_name' => 'ItemMaster_TestSubset.xml',
				'from' => EbayEnterprise_Eb2cCore_Helper_Feed::DEST_ID
			)));
		$this->replaceByMock('model', 'eb2cproduct/error_confirmations', $confirmationsModelMock);

		$confirmations = Mage::getModel('eb2cproduct/error_confirmations');
		// class property _queueMessage and _queueError to a known state
		$this->_reflectProperty($confirmations, '_queueMessage')->setValue($confirmations, array(
			'<Message xml:lang="en-US" code="">Error exception occurred: UnitTest Simulate Throw Exception on Dom load</Message>'
		));
		$this->_reflectProperty($confirmations, '_queueError')->setValue($confirmations, array());

		$this->assertSame($confirmations, $confirmations->addError('ItemMaster', 'ItemMaster_TestSubset.xml'));

		$this->assertSame(
			array('<Error event_type="ItemMaster" file_name="ItemMaster_TestSubset.xml" reported_from="WMS">
<Message xml:lang="en-US" code="">Error exception occurred: UnitTest Simulate Throw Exception on Dom load</Message>
</Error>'),
			$this->_reflectProperty($confirmations, '_queueError')->getValue($confirmations)
		);

		$this->assertEmpty($this->_reflectProperty($confirmations, '_queueMessage')->getValue($confirmations));
	}

	/**
	 * Test addErrorConfirmation method with the following assumptions when call with given sku as a parameter
	 * Expectation 1: the sku parameter will be assigned to an array with key (sku), and this array will be the first parameter pass to
	 *                the class method EbayEnterprise_Eb2cProduct_Helper_Data::mapPattern and the second parameter will be a class constant that
	 *                has value for the error confirmation node.
	 * Expectation 2: the mocked method EbayEnterprise_Eb2cProduct_Helper_Data::mapPattern will return value that will be mapped to the first
	 *                sprintf formatted string the second parameter to the sprintf method will be the implode result of the class property
	 *                EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::_queueError the third parameter is the class constant for error
	 *                confirmation close node.
	 * Expectation 3: the test set the class property EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::_queueError to a know state with
	 *                array with element and set the property EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::_queueConfirmation to a
	 *                state of empty array, when the test run the method addErrorConfirmation we can properly asserted that the class
	 *                property _queueConfirmation was an empty array and now it has an array with elements and we can also assert that
	 *                the class property _queueError had an array of element but now empty
	 * @mock EbayEnterprise_Eb2cProduct_Helper_Data::mapPattern
	 */
	public function testAddErrorConfirmation()
	{
		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('mapPattern'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('mapPattern')
			->with($this->equalTo(array('sku' => 'SK-ABC-1334')))
			->will($this->returnValue('<ErrorConfirmation unique_id="SK-ABC-1334">'));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$confirmations = Mage::getModel('eb2cproduct/error_confirmations');
		// class property _queueConfirmation and _queueError to a known state
		$this->_reflectProperty($confirmations, '_queueError')->setValue($confirmations, array(
			'<Error event_type="ItemMaster" file_name="ItemMaster_TestSubset.xml" reported_from="WMS">
				<Message xml:lang="en-US" code="">Error exception occurred: UnitTest Simulate Throw Exception on Dom load</Message>
			</Error>'
		));
		$this->_reflectProperty($confirmations, '_queueConfirmation')->setValue($confirmations, array());

		$this->assertSame($confirmations, $confirmations->addErrorConfirmation('SK-ABC-1334'));

		$this->assertSame(
			array('<ErrorConfirmation unique_id="SK-ABC-1334">
<Error event_type="ItemMaster" file_name="ItemMaster_TestSubset.xml" reported_from="WMS">
				<Message xml:lang="en-US" code="">Error exception occurred: UnitTest Simulate Throw Exception on Dom load</Message>
			</Error>
</ErrorConfirmation>'
			),
			$this->_reflectProperty($confirmations, '_queueConfirmation')->getValue($confirmations)
		);

		$this->assertEmpty($this->_reflectProperty($confirmations, '_queueError')->getValue($confirmations));
	}

	/**
	 * Test flush method with the following assumptions when called
	 * Expectation 1: in order to fully test the EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::flush method we need to set
	 *                the class property EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::_queueConfirmation to a known states with array with elements
	 * Expectation 2: since the test set the class property _queueConfirmation to an array with one element we expect that the class method
	 *                EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::append will be called once with the content of the first array element and return itself
	 * Expectation 3: the method EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::flush is expected to return itself as expected
	 * Expectation 4: the class property EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::_queueConfirmation is expected to be an empty array.
	 * @mock EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::append
	 */
	public function testFlush()
	{
		$confirmationsModelMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array('append'))
			->getMock();
		$confirmationsModelMock->expects($this->once())
			->method('append')
			->with($this->equalTo('<ErrorConfirmation unique_id="SK-ABC-1334">
<Error event_type="ItemMaster" file_name="ItemMaster_TestSubset.xml" reported_from="WMS">
				<Message xml:lang="en-US" code="">Error exception occurred: UnitTest Simulate Throw Exception on Dom load</Message>
			</Error>
	</ErrorConfirmation>'))
			->will($this->returnSelf());

		// class property _queueConfirmation to a known state
		$this->_reflectProperty($confirmationsModelMock, '_queueConfirmation')->setValue($confirmationsModelMock, array('<ErrorConfirmation unique_id="SK-ABC-1334">
<Error event_type="ItemMaster" file_name="ItemMaster_TestSubset.xml" reported_from="WMS">
				<Message xml:lang="en-US" code="">Error exception occurred: UnitTest Simulate Throw Exception on Dom load</Message>
			</Error>
	</ErrorConfirmation>'
		));

		$this->assertSame($confirmationsModelMock, $confirmationsModelMock->flush());

		$this->assertEmpty($this->_reflectProperty($confirmationsModelMock, '_queueConfirmation')->getValue($confirmationsModelMock));
	}

	/**
	 * Test _getErrorTemplate method with the following assumptions when call with given message template ($type) and message ($fileName) as parameters
	 * Expectation 1: passing type and filename to the tested method EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::_getErrorTemplate
	 *                will return an array with key (feed_type) that hold the value of the parameter type, another array key (feed_file_name),
	 *                with the value of filename parameter and one last array key (from) with the value of the eb2ccore helper class feed constant value
	 */
	public function testGetErrorTemplate()
	{
		$confirmations = Mage::getModel('eb2cproduct/error_confirmations');

		$testData = array(
			array(
				'expect' => array('feed_type' => 'ItemMaster', 'feed_file_name' => 'ItemMaster_Test_Subset.xml', 'from' => EbayEnterprise_Eb2cCore_Helper_Feed::DEST_ID),
				'type' => 'ItemMaster',
				'fileName' => 'ItemMaster_Test_Subset.xml'
			),
		);

		foreach ($testData as $data) {
			$this->assertSame($data['expect'], $this->_reflectMethod($confirmations, '_getErrorTemplate')->invoke($confirmations, $data['type'], $data['fileName']));
		}
	}

	/**
	 * Test hasMessage method with the following assumptions when called
	 * Expectation 1: the EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::hasMessage revolved around the class
	 *                property EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::_queueMessage current state in the instantiated object
	 *                Therefore the test is testing two scenarios by setting the state of the class property _queueMessage to a known state
	 *                and then testing the return value of the hasMessage method
	 * Expectation 2: setting the state of the class property EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::_queueMessage to an empty array
	 *                the test assert that the method EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::hasMessage will return false
	 * Expectation 3: calling the EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::addMessage with parameter one being the class constant for dom load err
	 *                and the second parameter being a text string verbiage and the test can then make the assertion that
	 *                EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::hasMessage will return true
	 */
	public function testHasMessage()
	{
		$confirmations = Mage::getModel('eb2cproduct/error_confirmations');

		// set the class property '_queueMessage' to a known state
		$this->_reflectProperty($confirmations, '_queueMessage')->setValue($confirmations, array());
		$this->assertSame(false, $confirmations->hasMessage());

		$confirmations->addMessage($confirmations::SKU_NOT_REMOVE, 'UnitTest Simulate Throw Exception on Dom load');
		$this->assertSame(true, $confirmations->hasMessage());
	}

	/**
	 * Test hasError method with the following assumptions when called
	 * Expectation 1: the EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::hasError revolved around the class
	 *                property EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::_queueError current state in the instantiated object
	 *                Therefore the test is testing two scenarios by setting the state of the class property _queueError and _queueMessage to a known state
	 *                and then testing the return value of the hasError method
	 * Expectation 2: setting the state of the class property EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::_queueError and _queueMessage to an empty array
	 *                the test assert that the method EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::hasError will return false
	 * Expectation 3: calling the EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::addMessage with parameter one being the class constant for dom load err
	 *                and the second parameter being a text string verbiage and then calling the method
	 *                EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::addError with type as first parameter and file name as the second parameter
	 *                the test can then make the assertion that
	 *                EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::hasError will return true
	 */
	public function testHasError()
	{
		$confirmations = Mage::getModel('eb2cproduct/error_confirmations');

		// set the class property '_queueMessage' and '_queueError' to a known state
		$this->_reflectProperty($confirmations, '_queueMessage')->setValue($confirmations, array());
		$this->_reflectProperty($confirmations, '_queueError')->setValue($confirmations, array());
		$this->assertSame(false, $confirmations->hasError());

		$confirmations->addMessage($confirmations::SKU_NOT_REMOVE, 'UnitTest Simulate Throw Exception on Dom load')
			->addError('ItemMaster', 'ItemMaster_Test_Subset.xml');
		$this->assertSame(true, $confirmations->hasError());
	}

	/**
	 * Test hasErrorConfirmation method with the following assumptions when called
	 * Expectation 1: the EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::hasErrorConfirmation revolved around the class
	 *                property EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::_queueConfirmation current state in the instantiated object
	 *                Therefore the test is testing two scenarios by setting the state of the class property _queueConfirmation,
	 *                _queueError and _queueMessage to a known state and then testing the return value of the hasErrorConfirmation method
	 * Expectation 2: setting the state of the class property EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::_queueConfirmation, _queueError
	 *                and _queueMessage to an empty array
	 *                the test assert that the method EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::hasErrorConfirmation will return false
	 * Expectation 3: calling the EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::addMessage with parameter one being the class constant
	 *                for dom load err and the second parameter being a text string verbiage, then calling the method
	 *                EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::addError with type as first parameter and file name as the
	 *                second parameter and then calling the method EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::addErrorConfirmation with sku
	 *                the test can then make the assertion that EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::hasErrorConfirmation will return true
	 */
	public function testHasErrorConfirmation()
	{
		$confirmations = Mage::getModel('eb2cproduct/error_confirmations');

		// set the class property '_queueMessage', '_queueError' and '_queueConfirmation' to a known state
		$this->_reflectProperty($confirmations, '_queueMessage')->setValue($confirmations, array());
		$this->_reflectProperty($confirmations, '_queueError')->setValue($confirmations, array());
		$this->_reflectProperty($confirmations, '_queueConfirmation')->setValue($confirmations, array());
		$this->assertSame(false, $confirmations->hasErrorConfirmation());

		$confirmations->addMessage($confirmations::SKU_NOT_REMOVE, 'UnitTest Simulate Throw Exception on Dom load')
			->addError('ItemMaster', 'ItemMaster_Test_Subset.xml')
			->addErrorConfirmation('1234');
		$this->assertSame(true, $confirmations->hasErrorConfirmation());
	}

	/**
	 * Test process method with the following assumptions when call with given Varien_Event_Observer object as a parameter
	 * Expectation 1: the EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::process is expected to be called with a mock
	 *                Varien_Event_Observer object in which the mocked object method Varien_Event_Observer::getEvent will be called once
	 *                and will return a mocked Varien_Event object, the Varien_Event::getFeedDetails will be called once and will return
	 *                an array of an array of key elements just error_file which is needed for the processing the error confirmation files
	 * Expectation 2: the array return by the Varien_Event::getFeedDetails method will then use to load through each array of array with keys,
	 *                each array with key (error_file), will then call method EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::loadFile with the file name once,
	 *                then call the method EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::close once,
	 *                then call the method EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::transferFile with the file name as parameter once,
	 *                and then call the method EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::archive with the file name as parameter once
	 * Expectation 3: the EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::process method is expected to return itself
	 * @mock Varien_Event_Observer::getEvent
	 * @mock Varien_Event::getFeedDetails
	 * @mock EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::loadFile
	 * @mock EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::close
	 * @mock EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::transferFile
	 * @mock EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::archive
	 */
	public function testProcess()
	{
		$exportFile = '/Mage/var/outbox/error.xml';
		$localFile = '/Mage/var/processing/error.xml';

		$event = $this->getMockBuilder('Varien_Event')
			->disableOriginalConstructor()
			->setMethods(array('getFeedDetails'))
			->getMock();
		$event->expects($this->once())
			->method('getFeedDetails')
			->will($this->returnValue(array(
				array(
					'local_file' => 'path/to/imported/product/file.xml',
					'timestamp' => '1364823587',
					'core_feed' => 'core feed model used for the feed',
					'error_file' => $localFile,
				)
			)));

		$observer = $this->getMockBuilder('Varien_Event_Observer')
			->disableOriginalConstructor()
			->setMethods(array('getEvent'))
			->getMock();
		$observer->expects($this->once())
			->method('getEvent')
			->will($this->returnValue($event));

		$cfg = $this->buildCoreConfigRegistry(array(
			'errorFeed' => array('local_directory' => 'local/error'),
		));
		$coreFeed = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('mvToLocalDirectory'))
			->getMock();
		$feedHelper = $this->getHelperMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('getConfig'))
			->getMock();
		$confirmationsModelMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array('loadFile', 'close'))
			->getMock();

		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeed);
		$this->replaceByMock('helper', 'eb2ccore/feed', $feedHelper);

		$feedHelper->expects($this->once())
			->method('getConfig')
			->will($this->returnValue($cfg));
		$confirmationsModelMock->expects($this->once())
			->method('loadFile')
			->with($this->equalTo($localFile))
			->will($this->returnSelf());
		$confirmationsModelMock->expects($this->once())
			->method('close')
			->will($this->returnSelf());
		$coreFeed->expects($this->once())
			->method('mvToLocalDirectory')
			->with($this->identicalTo($localFile))
			->will($this->returnValue($exportFile));

		$this->assertSame($confirmationsModelMock, $confirmationsModelMock->process($observer));
	}

	/**
	 * Test processByOperationType method for the following expectations
	 * Expectation 1: the method EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::_processByOperationType will be
	 *                invoked and given an object of Varient_Event_Observer contain methods to get all skues that were deleted
	 *                and another method got get feed file detail. It will query the magento database for these
	 *                deleted skus and report any product that still exist in magento.
	 */
	public function testProcessByOperationType()
	{
		$coreFeed = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('getEventType'))
			->getMock();
		$skus = array('58-HTC038', '58-JKT8844');
		$type = 'ItemMaster';
		$file = 'local/Feed/ItemMaster/inbox/ItemMaster_Subset-Sample.xml';
		$errorFile = 'local/Feed/ItemMaster/outbound/ItemMaster_20140115063947_ABCD_1234.xml';
		$operationType = 'delete';
		$fileDetail = array('local_file' => $file, 'core_feed' => $coreFeed, 'error_file' => $errorFile, 'operation_type' => $operationType);

		$coreFeed->expects($this->once())
			->method('getEventType')
			->will($this->returnValue($type));
		$eventMock = $this->getMockBuilder('Varien_Event')
			->disableOriginalConstructor()
			->setMethods(array('getFeedDetail', 'getSkus', 'getOperationType'))
			->getMock();
		$eventMock->expects($this->once())
			->method('getFeedDetail')
			->will($this->returnValue($fileDetail));
		$eventMock->expects($this->once())
			->method('getSkus')
			->will($this->returnValue($skus));
		$eventMock->expects($this->once())
			->method('getOperationType')
			->will($this->returnValue($operationType));

		$observerMock = $this->getMockBuilder('Varien_Event_Observer')
			->disableOriginalConstructor()
			->setMethods(array('getEvent'))
			->getMock();
		$observerMock->expects($this->once())
			->method('getEvent')
			->will($this->returnValue($eventMock));

		$productCollection = Mage::getResourceModel('catalog/product_collection');

		$errorConfirmationMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array('loadFile', '_getProductCollectionBySkus', '_addDeleteErrors'))
			->getMock();
		$errorConfirmationMock->expects($this->once())
			->method('loadFile')
			->with($this->identicalTo($errorFile))
			->will($this->returnSelf());
		$errorConfirmationMock->expects($this->once())
			->method('_getProductCollectionBySkus')
			->with($this->identicalTo($skus))
			->will($this->returnValue($productCollection));
		$errorConfirmationMock->expects($this->once())
			->method('_addDeleteErrors')
			->with($this->identicalTo($productCollection), $this->identicalTo(basename($file)), $this->identicalTo($type))
			->will($this->returnSelf());

		$this->assertSame($errorConfirmationMock, $errorConfirmationMock->processByOperationType($observerMock));
	}

	/**
	 * Test _addDeleteErrors method for the following expectations
	 * Expectation 1: given a mock object of class Mage_Catalog_Model_Resource_Product_Collection, a feed file name
	 *                and an event type to the method EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::_addDeleteErrors
	 *                when invoked by this test will check the count on the collection loop through all the product
	 *                on the collection call addMessage, addError, AddErrrorConfirmation and flush methods to error confirmation
	 *                to the file
	 */
	public function testAddDeleteErrors()
	{
		$skus = array('58-HTC038', '58-JKT8844');
		$type = 'ItemMaster';
		$fileName = 'ItemMaster_Subset-Sample.xml';

		$productCollection = Mage::getResourceModel('catalog/product_collection');
		foreach ($skus as $sku) {
			$productCollection->addItem(Mage::getModel('catalog/product')->addData(array('sku' => $sku)));
		}

		$errorConfirmationMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array('_appendError'))
			->getMock();
		$errorConfirmationMock->expects($this->at(0))
			->method('_appendError')
			->with(
				$this->identicalTo(EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::SKU_NOT_REMOVE),
				$this->identicalTo(''),
				$this->identicalTo($type),
				$this->identicalTo($fileName),
				$this->identicalTo($skus[0])
			)
			->will($this->returnSelf());
		$errorConfirmationMock->expects($this->at(1))
			->method('_appendError')
			->with(
				$this->identicalTo(EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::SKU_NOT_REMOVE),
				$this->identicalTo(''),
				$this->identicalTo($type),
				$this->identicalTo($fileName),
				$this->identicalTo($skus[1])
			)
			->will($this->returnSelf());

		$this->assertSame($errorConfirmationMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$errorConfirmationMock,
			'_addDeleteErrors',
			array($productCollection, $fileName, $type)
		));
	}

	/**
	 * Test _addImportErrors method for the following expectations
	 * Expectation 1: given a mock object of class Mage_Catalog_Model_Resource_Product_Collection, a list of imported skus, a feed file name
	 *                and an event type to the method EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::_addImportErrors
	 *                when invoked by this test will loop through all the imported skus and check if the sku is in
	 *                the collection if it is not it will add error confirmations
	 */
	public function testAddImportErrors()
	{
		$skus = array('58-HTC038', '58-JKT8844');
		$type = 'ItemMaster';
		$fileName = 'ItemMaster_Subset-Sample.xml';

		$collectionMock = $this->getResourceModelMockBuilder('catalog/product_collection')
			->disableOriginalConstructor()
			->setMethods(array('getItemByColumnValue'))
			->getMock();
		$collectionMock->expects($this->exactly(2))
			->method('getItemByColumnValue')
			->will($this->returnValueMap(array(
				array('sku', $skus[0], null),
				array('sku', $skus[1], null)
			)));

		$errorConfirmationMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array('_appendError'))
			->getMock();
		$errorConfirmationMock->expects($this->at(0))
			->method('_appendError')
			->with(
				$this->identicalTo(EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::SKU_NOT_IMPORTED),
				$this->identicalTo(''),
				$this->identicalTo($type),
				$this->identicalTo($fileName),
				$this->identicalTo($skus[0])
			)
			->will($this->returnSelf());
		$errorConfirmationMock->expects($this->at(1))
			->method('_appendError')
			->with(
				$this->identicalTo(EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::SKU_NOT_IMPORTED),
				$this->identicalTo(''),
				$this->identicalTo($type),
				$this->identicalTo($fileName),
				$this->identicalTo($skus[1])
			)
			->will($this->returnSelf());

		$this->assertSame($errorConfirmationMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$errorConfirmationMock,
			'_addImportErrors',
			array($collectionMock, $skus, $fileName, $type)
		));
	}

	/**
	 * Test _appendError method for the following expectations
	 * Expectation 1: given a mock object of class Mage_Catalog_Model_Resource_Product_Collection, a list of imported skus, a feed file name
	 *                and an event type to the method EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::_appendError
	 *                when invoked by this test will loop through all the imported skus and check if the sku is in
	 *                the collection if it is not it will add error confirmations
	 */
	public function testAppendError()
	{
		$sku = '58-HTC038';
		$type = 'ItemMaster';
		$fileName = 'ItemMaster_Subset-Sample.xml';

		$errorConfirmationMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array('addMessage', 'addError', 'addErrorConfirmation', 'flush'))
			->getMock();
		$errorConfirmationMock->expects($this->once())
			->method('addMessage')
			->with($this->identicalTo(EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::SKU_NOT_IMPORTED), $this->identicalTo(''))
			->will($this->returnSelf());
		$errorConfirmationMock->expects($this->once())
			->method('addError')
			->with($this->identicalTo($type), $this->identicalTo($fileName))
			->will($this->returnSelf());
		$errorConfirmationMock->expects($this->once())
			->method('addErrorConfirmation')
			->with($this->identicalTo($sku))
			->will($this->returnSelf());
		$errorConfirmationMock->expects($this->once())
			->method('flush')
			->will($this->returnSelf());

		$this->assertSame($errorConfirmationMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$errorConfirmationMock,
			'_appendError',
			array(EbayEnterprise_Eb2cProduct_Model_Error_Confirmations::SKU_NOT_IMPORTED, '', $type, $fileName, $sku)
		));
	}

	/**
	 * Test _getProductCollectionBySkus method for the following expectations
	 * Expectation 1: the method EbayEnterprise_Eb2cProduct_Model_Feed_File::_getProductCollectionBySkus when
	 *                invoked by this test will be given an array of skus as parameter
	 *                with that parameter it will query the Mage_Catalog_Model_Resource_Product_Collection by skus
	 *                and return a collection of product
	 */
	public function testGetProductCollectionBySkus()
	{
		$skus = array('58-HTC038', '58-JKT8844');

		$collectionMock = $this->getResourceModelMockBuilder('catalog/product_collection')
			->disableOriginalConstructor()
			->setMethods(array('addFieldToFilter', 'addAttributeToSelect', 'load'))
			->getMock();
		$collectionMock->expects($this->once())
			->method('addFieldToFilter')
			->with($this->identicalTo('sku'), $this->identicalTo($skus))
			->will($this->returnSelf());
		$collectionMock->expects($this->once())
			->method('addAttributeToSelect')
			->with($this->identicalTo(array('sku')))
			->will($this->returnSelf());
		$collectionMock->expects($this->once())
			->method('load')
			->will($this->returnSelf());
		$this->replaceByMock('resource_model', 'catalog/product_collection', $collectionMock);

		$errorConfirmationMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->assertSame($collectionMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$errorConfirmationMock,
			'_getProductCollectionBySkus',
			array($skus)
		));
	}
}
