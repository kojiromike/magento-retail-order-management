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

class EbayEnterprise_Address_Test_Block_SuggestionsTest extends EcomDev_PHPUnit_Test_Case
{
    /**
     * Get an instance of the suggestions block to run some tests against.
     * @return EbayEnterprise_Address_Block_Suggestions
     */
    protected function _createSuggestionsBlock()
    {
        return Mage::app()->getLayout()->createBlock('ebayenterprise_address/suggestions');
    }

    /**
     * Replace the customer session object with a mock.
     * @return PHPUnit_Framework_MockObject_MockObject - the mock session model
     */
    protected function _mockCustomerSession()
    {
        $sessionMock = $this->getModelMockBuilder('customer/session')
            ->disableOriginalConstructor() // This one removes session_start and other methods usage
            ->setMethods(null) // Enables original methods usage, because by default it overrides all methods
            ->getMock();
        $this->replaceByMock('singleton', 'customer/session', $sessionMock);
        return $sessionMock;
    }

    /**
     * Test set up - mock out customer session.
     */
    public function setUp()
    {
        $this->_mockCustomerSession();
    }

    /**
     * Test determining if suggestions should be shown
     * @dataProvider dataProvider
     */
    public function testShouldShowSuggestions($hasFreshSuggestions, $hasSuggestions, $isValid)
    {
        $validator = $this->getModelMock('ebayenterprise_address/validator', array('hasFreshSuggestions', 'hasSuggestions', 'isValid'));
        $validator->expects($this->once())
            ->method('hasFreshSuggestions')
            ->will($this->returnValue($hasFreshSuggestions));

        // when there are fresh suggestions, check further for there to be suggestions
        // or validation errors
        if ($hasFreshSuggestions) {
            $validator->expects($this->once())
                ->method('hasSuggestions')
                ->will($this->returnValue($hasSuggestions));

            // if there aren't any suggestions, last step would be to check if the
            // last recorded message was valid
            if (!$hasSuggestions) {
                $validator->expects($this->once())
                    ->method('isValid')
                    ->will($this->returnValue($isValid));
            } else {
                $validator->expects($this->never())
                    ->method('isValid');
            }

        } else {
            // if the suggestions/validation isn't fresh, it doesn't matter
            // if there are suggestions or errors
            $validator->expects($this->never())
                ->method('hasSuggestions');
            $validator->expects($this->never())
                ->method('isValid');
        }

        $this->replaceByMock('model', 'ebayenterprise_address/validator', $validator);

        $block = $this->_createSuggestionsBlock();

        $this->assertEquals(
            $this->expected('%s-%s-%s', $hasFreshSuggestions, $hasSuggestions, $isValid)->getShowSuggestions(),
            $block->shouldShowSuggestions(),
            'Suggestions should only be shown when there are suggestions and the suggestions are "fresh"'
        );
        $this->assertEquals(
            $this->expected('%s-%s-%s', $hasFreshSuggestions, $hasSuggestions, $isValid)->getShowSuggestions(),
            $block->shouldShowSuggestions(),
            'Calling shouldShowSuggestions multiple times should not re-call the validator methods, hence everything still only called once.'
        );
    }

    public function testGetSuggestedAddresses()
    {
        $addresses = array();
        $addresses[] = Mage::getModel('customer/address', array(
            'street'      => '123 Main St',
            'city'        => 'King of Prussia',
            'region_code' => 'PA',
            'country_id'  => 'US',
            'postcode'    => '19406',
        ));
        $validator = $this->getModelMock('ebayenterprise_address/validator', array('getSuggestedAddresses'));
        $validator->expects($this->once())
            ->method('getSuggestedAddresses')
            ->will($this->returnValue($addresses));
        $this->replaceByMock('model', 'ebayenterprise_address/validator', $validator);

        $this->assertSame(
            $addresses,
            $this->_createSuggestionsBlock()->getSuggestedAddresses()
        );
    }

    public function testGetOriginalAddress()
    {
        $address = Mage::getModel('customer/address');
        $address->addData(array(
            'street' => '123 Main St'
        ));
        $validator = $this->getModelMock('ebayenterprise_address/validator', array('getOriginalAddress'));
        $validator->expects($this->once())
            ->method('getOriginalAddress')
            ->will($this->returnValue($address));
        $this->replaceByMock('model', 'ebayenterprise_address/validator', $validator);

        $this->assertSame(
            $address,
            $this->_createSuggestionsBlock()->getOriginalAddress()
        );
    }

    /**
     * Test getting JSON representation of an address object.
     */
    public function testAddressJson()
    {
        $address = Mage::getModel('customer/address', array(
            'firstname'   => 'Foo',
            'lastname'    => 'Bar',
            'street'      => "123 Main St\nSTE 6\nLine 3\nLine 4",
            'city'        => 'Fooville',
            'region_id'   => 51,
            'region_code' => 'PA',
            'country_id'  => 'US',
            'postcode'    => '99999',
        ));
        $block = $this->_createSuggestionsBlock();
        $jsonString = $block->getAddressJsonData($address);
        $this->assertNotNull($jsonString);
        $jsonData = Mage::helper('core')->jsonDecode($jsonString);
        $this->assertEquals(
            array(
                'street1'    => '123 Main St',
                'street2'    => 'STE 6',
                'street3'    => 'Line 3',
                'street4'    => 'Line 4',
                'city'       => 'Fooville',
                'region_id'  => 51,
                'country_id' => 'US',
                'postcode'   => '99999',
            ),
            $jsonData,
            'JSON Data contains correct data for address.'
        );
    }

    /**
     * Test the rendering of address objects. Not a huge fan of testing against the
     * expected HTML markup...seems overly brittle.
     * @large
     */
    public function testGetRenderedAddress()
    {
        $expectedFull = 'Foo Bar<br/>123 Main St<br/>King of Prussia, Pennsylvania<br/>19406<br/>United States';
        $expectedAdOnly = '123 Main St<br/>King of Prussia, Pennsylvania<br/>19406<br/>United States';
        $address = Mage::getModel('customer/address', array(
            'firstname'   => 'Foo',
            'lastname'    => 'Bar',
            'street'      => '123 Main St',
            'city'        => 'King of Prussia',
            'region_code' => 'PA',
            'region_id'   => 51,
            'country_id'  => 'US',
            'postcode'    => '19406',
        ));
        $block = $this->_createSuggestionsBlock();
        $rendered = preg_replace('/<br\s*\/\>\s+/', '<br/>', $block->getRenderedAddress($address));
        $this->assertEquals($expectedFull, $rendered);
        $rendered = preg_replace(
            '/<br\s*\/\>\s+/',
            '<br/>',
            $block->setAddressFormat('address_format_address_only')->getRenderedAddress($address)
        );
        $this->assertEquals($expectedAdOnly, $rendered);
    }

    /**
     * This may not be the best test as the test is basically the same as the implementation, but not much else to test.
     * My main reason for doing this is that other code also uses the const, such as
     * the validator model to pull out the selected value, hence it is important
     * to ensure this method pulls the same value other code is expecting it to be.
     */
    public function testSuggestionInputName()
    {
        $this->assertSame(
            EbayEnterprise_Address_Block_Suggestions::SUGGESTION_INPUT_NAME,
            Mage::app()->getLayout()->createBlock('ebayenterprise_address/suggestions')->getSuggestionInputName()
        );
    }

    /**
     * This may not be the best test as the test is basically the same as the implementation, but not much else to test.
     * My main reason for doing this is that other code also uses the const, such as
     * the validator model to determine if the "New Address" seleciton is made,
     * hence it is important to ensure this method pulls the same value
     * other code is expecting it to be.
     */
    public function testNewAddressSuggestionValue()
    {
        $this->assertSame(
            EbayEnterprise_Address_Block_Suggestions::NEW_ADDRESS_SELECTION_VALUE,
            Mage::app()->getLayout()->createBlock('ebayenterprise_address/suggestions')->getNewAddressSelectionValue()
        );
    }

    /**
     * Get the user messages. Less concerned with the actual message, more so
     * ensuring they are all passed through the translation helper method.
     * @dataProvider dataProvider
     */
    public function testMessages($methodName)
    {
        $helper = $this->getHelperMock('ebayenterprise_address/data', array('__'));
        $helper->expects($this->once())
            ->method('__')
            ->will($this->returnArgument(0));
        $this->replaceByMock('helper', 'ebayenterprise_address', $helper);

        $block = Mage::app()->getLayout()->createBlock('ebayenterprise_address/suggestions');
        $this->assertSame(
            $this->expected($methodName)->getMessage(),
            $block->$methodName()
        );
    }
}
