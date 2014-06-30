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

class EbayEnterprise_Eb2cOrder_Test_Model_Customer_Order_DetailTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	public static $response = array();
	public $responseText = '<OrderDetailResponse/>';

	public static function setUpBeforeClass()
	{
		self::$response[] = file_get_contents(dirname(__FILE__) . '/DetailTest/fixtures/blah.xml');
	}

	public function setUp()
	{
		$xsdDetail = 'Order-Service-Detail-1.0.xsd';
		$coreHelperMock = $this->getHelperMock('eb2ccore/data', array('getApiUri'));
		$coreHelperMock->expects($this->any())
			->method('getApiUri')
			->with($this->equalTo('orders'), $this->equalTo('get'))
			->will($this->returnValue('http://example.com/orders/get.xml'));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$apiModelMock = $this->getModelMock('eb2ccore/api', array('request'));
		$apiModelMock->expects($this->any())
			->method('request')
			->with(
				$this->isInstanceOf('EbayEnterprise_Dom_Document'),
				$xsdDetail,
				'http://example.com/orders/get.xml'
			)
			->will($this->returnValue($this->responseText));
		$this->replaceByMock('model', 'eb2ccore/api', $apiModelMock);

		$orderHelperMock = $this->getHelperMock('eb2corder/data', array('getConfig'));
		$orderHelperMock->expects($this->any())
			->method('getConfig')
			->will($this->returnValue((object) array(
				'xsdFileDetail' => $xsdDetail,
				'apiService' => 'orders',
				'apiDetailOperation' => 'get',
				'apiXmlNs' => 'http://api.gsicommerce.com/schema/checkout/1.0',
			)));
		$this->replaceByMock('helper', 'eb2corder', $orderHelperMock);
	}

	/**
	 * .
	 * @test
	 */
	public function testRequestOrderDetail()
	{
		$orderDetail = $this->getModelMock('eb2corder/customer_order_detail', array('buildOrderDetailRequest'));
		$requestDoc = Mage::helper('eb2ccore')->getNewDomDocument();
		$testOrderId = '00054000000000000';

		$orderDetail->expects($this->once())
			->method('buildOrderDetailRequest')
			->with($this->identicalTo($testOrderId))
			->will($this->returnValue($requestDoc));
		$result = $orderDetail->requestOrderDetail($testOrderId);
		$this->assertSame($result, $this->responseText);
	}

	/**
	 * make sure the request is built properly
	 * @test
	 */
	public function testBuildOrderDetailRequest()
	{
		$orderDetail = Mage::getModel('eb2corder/customer_order_detail');
		$this->assertSame(
			'<OrderDetailRequest xmlns="http://api.gsicommerce.com/schema/checkout/1.0"><CustomerOrderId>333</CustomerOrderId></OrderDetailRequest>',
			$orderDetail->buildOrderDetailRequest('333')->C14N()
		);
	}

	public function provideCounter()
	{
		return array(array(0));
	}

	/**
	 * verify payment information is extracted properly
	 * @test
	 * @dataProvider provideCounter
	 */
	public function testReadPayment($value)
	{
		$detail = Mage::getModel('eb2corder/customer_order_detail');
		$result = $detail->parseResponse(self::$response[$value]);

		$this->assertSame(1, $detail->getPayments()->count());
		$paymentData = $detail->getPayments()->getFirstItem();
		$this->assertSame(
			array(
				'tender_type' => 'VC',
				'account_unique_id' => '4000007YpA6s0101',
				'account_id_is_token' => true,
				'amount' => 0.0,
				'payment_type_name' => 'CreditCard',
			),
			$paymentData->getData()
		);
	}

	/**
	 * verify the shipping address is extracted properly
	 * @test
	 */
	public function testShippingAddress()
	{
		$detail = Mage::getModel('eb2corder/customer_order_detail');
		$result = $detail->parseResponse($this->_xml);

		$this->assertSame(
			array(
				'id' => 'dest_1',
				'lastname' => 'Boone',
				'firstname' => 'Brian',
				'street1' => '2151 Buchert Rd',
				'city' => 'Pottstown',
				'main_division' => 'PA',
				'country_code' => 'US',
				'postal_code' => '19464-3042',
				'street' => '2151 Buchert Rd',
				'name' => 'Brian Boone',
				'address_type' => 'shipping',
				'charge_type' => 'FLATRATE',
			),
			$detail->getShippingAddress()->getData()
		);
	}

	protected $_xml = '<?xml version="1.0" encoding="UTF-8"?>
<OrderDetailResponse xmlns="http://api.gsicommerce.com/schema/checkout/1.0" orderType="SALES"
                     testType=""
                     cancellable="true">
   <Order customerOrderId="0005410310018" levelOfService="REGULAR">
      <Customer customerId="00008">
         <Name>
            <LastName>Boone</LastName>
            <FirstName>Brian</FirstName>
         </Name>
         <EmailAddress>test@tester.com</EmailAddress>
         <CustomerTaxId/>
         <TaxExemptFlag>false</TaxExemptFlag>
      </Customer>
      <CreateTime>2014-06-30T15:14:25+00:00</CreateTime>
      <OrderItems>
         <OrderItem id="item2014063015142852434761" webLineId="1">
            <ItemId>45-Purple</ItemId>
            <Quantity>1</Quantity>
            <Description>
               <Description>Purple1</Description>
            </Description>
            <Pricing>
               <Merchandise>
                  <Amount>150.00</Amount>
                  <TaxData>
                     <TaxClass>76687</TaxClass>
                     <Taxes>
                        <Tax taxType="SALES" taxability="TAXABLE">
                           <Situs>ADMINISTRATIVE_ORIGIN</Situs>
                           <Jurisdiction jurisdictionLevel="STATE" jurisdictionId="31152">PENNSYLVANIA</Jurisdiction>
                           <Imposition impositionType="General Sales and Use Tax">Sales and Use Tax</Imposition>
                           <EffectiveRate>0.060</EffectiveRate>
                           <TaxableAmount>150</TaxableAmount>
                           <CalculatedTax>9.00</CalculatedTax>
                        </Tax>
                     </Taxes>
                  </TaxData>
                  <UnitPrice>150.00</UnitPrice>
               </Merchandise>
               <Shipping>
                  <Amount>5.00</Amount>
                  <TaxData>
                     <TaxClass>93000</TaxClass>
                     <Taxes>
                        <Tax taxType="SALES" taxability="TAXABLE">
                           <Situs>ADMINISTRATIVE_ORIGIN</Situs>
                           <Jurisdiction jurisdictionLevel="STATE" jurisdictionId="31152">PENNSYLVANIA</Jurisdiction>
                           <Imposition impositionType="General Sales and Use Tax">Sales and Use Tax</Imposition>
                           <EffectiveRate>0.060</EffectiveRate>
                           <TaxableAmount>5</TaxableAmount>
                           <CalculatedTax>0.30</CalculatedTax>
                        </Tax>
                     </Taxes>
                  </TaxData>
                  <UnitPrice>0.00</UnitPrice>
               </Shipping>
               <Duty>
                  <TaxData/>
               </Duty>
            </Pricing>
            <Carrier mode="STD">ANY</Carrier>
            <FulfillmentChannel>SHIP_TO_HOME</FulfillmentChannel>
            <EstimatedDeliveryDate>
               <DeliveryWindow>
                  <From>2014-07-03T15:15:41+00:00</From>
                  <To>2014-07-06T15:15:41+00:00</To>
               </DeliveryWindow>
               <ShippingWindow>
                  <From>2014-07-01T15:15:41+00:00</From>
                  <To>2014-07-01T15:15:41+00:00</To>
               </ShippingWindow>
               <Mode>LEGACY</Mode>
               <MessageType>NONE</MessageType>
               <Template>You can expect to receive your item(s) between {0,date} and {1,date}.</Template>
               <OriginalExpectedShipmentDate>
                  <From>2014-07-01T15:13:48+00:00</From>
                  <To>2014-07-01T15:13:48+00:00</To>
               </OriginalExpectedShipmentDate>
               <OriginalExpectedDeliveryDate>
                  <From>2014-07-03T15:13:48+00:00</From>
                  <To>2014-07-06T15:13:48+00:00</To>
               </OriginalExpectedDeliveryDate>
            </EstimatedDeliveryDate>
            <VendorId>28001</VendorId>
            <Statuses>
               <Status>
                  <Quantity>1</Quantity>
                  <Status>Scheduled</Status>
                  <StatusDate>2014-06-30T15:15:41+00:00</StatusDate>
                  <ProductAvailabilityDate>2014-06-30</ProductAvailabilityDate>
                  <Warehouse>GSI-DC224</Warehouse>
               </Status>
            </Statuses>
            <OMSLineId>1-1</OMSLineId>
            <GiftRegistryCancelUrl/>
            <ReservationId>MAGTNA-MAGT1-27</ReservationId>
         </OrderItem>
      </OrderItems>
      <Shipping>
         <ShipGroups>
            <ShipGroup id="shipGroup_1" chargeType="FLATRATE">
               <DestinationTarget ref="dest_1"/>
               <OrderItems>
                  <Item ref="item2014063015142852434761"/>
               </OrderItems>
            </ShipGroup>
         </ShipGroups>
         <Destinations>
            <MailingAddress id="dest_1">
               <PersonName>
                  <LastName>Boone</LastName>
                  <FirstName>Brian</FirstName>
               </PersonName>
               <Address>
                  <Line1>2151 Buchert Rd</Line1>
                  <City>Pottstown</City>
                  <MainDivision>PA</MainDivision>
                  <CountryCode>US</CountryCode>
                  <PostalCode>19464-3042</PostalCode>
               </Address>
               <Phone>6102223333</Phone>
            </MailingAddress>
            <MailingAddress>
               <PersonName>
                  <LastName>Boone</LastName>
                  <FirstName>Brian</FirstName>
               </PersonName>
               <Address>
                  <Line1>2151 Buchert Rd</Line1>
                  <City>Pottstown</City>
                  <MainDivision>PA</MainDivision>
                  <CountryCode>US</CountryCode>
                  <PostalCode>19464-3042</PostalCode>
               </Address>
               <Phone>6102223333</Phone>
            </MailingAddress>
            <MailingAddress id="billing_1">
               <PersonName>
                  <LastName>Boone</LastName>
                  <FirstName>Brian</FirstName>
               </PersonName>
               <Address>
                  <Line1>2151 Buchert Rd</Line1>
                  <City>Pottstown</City>
                  <MainDivision>PA</MainDivision>
                  <CountryCode>US</CountryCode>
                  <PostalCode>19464-3042</PostalCode>
               </Address>
               <Phone>6102223333</Phone>
            </MailingAddress>
            <MailingAddress>
               <PersonName>
                  <LastName>Boone</LastName>
                  <FirstName>Brian</FirstName>
               </PersonName>
               <Address>
                  <Line1>2151 Buchert Rd</Line1>
                  <City>Pottstown</City>
                  <MainDivision>PA</MainDivision>
                  <CountryCode>US</CountryCode>
                  <PostalCode>19464-3042</PostalCode>
               </Address>
               <Phone>6102223333</Phone>
            </MailingAddress>
         </Destinations>
      </Shipping>
      <Payment>
         <BillingAddress ref="billing_1"/>
         <Payments>
            <StoredValueCard>
               <PaymentContext>
                  <PaymentSessionId/>
                  <TenderType>SV</TenderType>
                  <PaymentAccountUniqueId isToken="true">980000uhS2Z90003</PaymentAccountUniqueId>
               </PaymentContext>
               <Amount>164.30</Amount>
            </StoredValueCard>
         </Payments>
         <Status>AUTHORIZED</Status>
      </Payment>
      <Currency>USD</Currency>
      <TaxHeader>
         <Error>false</Error>
      </TaxHeader>
      <Locale>en_US</Locale>
      <Status>Scheduled</Status>
      <OrderHistoryUrl>http://int03.mage.gspt.net/import/sales/order/view/order_id/21/</OrderHistoryUrl>
      <ExchangeOrders/>
   </Order>
</OrderDetailResponse>
';
}
