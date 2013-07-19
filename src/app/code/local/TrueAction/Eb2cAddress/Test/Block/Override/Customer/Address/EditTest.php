<?php

class TrueAction_Eb2cAddress_Test_Block_Override_Customer_Address_EditTest
	extends EcomDev_PHPUnit_Test_Case
{

	/**
	 * This method is just a pass through to the validator model so there isn't to test.
	 * @test
	 */
	public function testHasSuggestions()
	{
		$validatorMock = $this->getModelMock('eb2caddress/validator', array('hasSuggestions'));
		$validatorMock->expects($this->any())
			->method('hasSuggestions')
			->will($this->returnValue(true));

		$this->replaceByMock('model', 'eb2caddress/validator', $validatorMock);
		$block = new TrueAction_Eb2cAddress_Block_Override_Customer_Address_Edit();
		$this->assertTrue($block->hasSuggestions());
	}

}