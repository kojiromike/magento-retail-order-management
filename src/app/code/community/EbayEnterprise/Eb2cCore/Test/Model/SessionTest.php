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

class EbayEnterprise_Eb2cCore_Test_Model_SessionTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    public function setUp()
    {
        parent::setUp();

        // suppressing the real session from starting
        $session = $this->getModelMockBuilder('core/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->replaceByMock('singleton', 'core/session', $session);
    }

    /**
     * Create a stub quote item scripted to return the given sku and qty
     * @param  string  $sku       Quote item sku
     * @param  int     $qty       Qty of the item in the cart
     * @param  bool $isVirtual Is the item virtual
     * @param  int
     * @return Mage_Sales_Model_Quote_Item The stub quote item
     */
    protected function _stubQuoteItem($sku, $qty, $isVirtual, $itemId)
    {
        $item = $this->getModelMock('sales/quote_item', ['getSku', 'getQty', 'getIsVirtual', 'getId']);
        $item
            ->expects($this->any())
            ->method('getSku')
            ->will($this->returnValue($sku));
        $item
            ->expects($this->any())
            ->method('getQty')
            ->will($this->returnValue($qty));
        $item
            ->expects($this->any())
            ->method('getIsVirtual')
            ->will($this->returnValue($isVirtual));
         $item
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($itemId));
        return $item;
    }
    /**
     * Test extracting quote item sku data from a quote
     */
    public function testExtractQuoteSkuData()
    {
        $quote = $this->getModelMock('sales/quote', ['getAllVisibleItems']);
        $helper = $this->getHelperMock('eb2ccore/quote_item', ['isItemInventoried']);
        $this->replaceByMock('helper', 'eb2ccore/quote_item', $helper);

        // first item is managed, second is not
        $items = [
            $this->_stubQuoteItem('45-123', 3, false, 1),
            $this->_stubQuoteItem('45-234', 1, false, 2),
            $this->_stubQuoteItem('45-345', 1, true, 3),
        ];

        $helper
            ->expects($this->any())
            ->method('isItemInventoried')
            // returnValueMap to return first item as managed, second as nonmanaged
            ->will($this->returnValueMap([
                [$items[0], true],
                [$items[1], false],
                [$items[2], false],
            ]));
        $quote
            ->expects($this->any())
            ->method('getAllVisibleItems')
            ->will($this->returnValue($items));

        $session = $this->getModelMockBuilder('eb2ccore/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->assertSame(
            [
                '45-123' => ['item_id' => 1, 'managed' => true, 'virtual' => false, 'qty' => 3],
                '45-234' => ['item_id' => 2, 'managed' => false, 'virtual' => false, 'qty' => 1],
                '45-345' => ['item_id' => 3, 'managed' => false, 'virtual' => true, 'qty' => 1],
            ],
            EcomDev_Utils_Reflection::invokeRestrictedMethod($session, '_extractQuoteSkuData', [$quote])
        );
    }
    /**
     */
    public function testExtractAddressData()
    {
        $addressData = [
            'street' => ['630 Allendale Rd'],
            'city' => 'King of Prussia',
            'region_code' => 'PA',
            'country_id' => 'US',
            'postcode' => '19406',
        ];
        $address = $this->getModelMock(
            'sales/quote_address',
            ['getStreet', 'getCity', 'getRegionCode', 'getCountryId', 'getPostcode']
        );
        $address
            ->expects($this->any())
            ->method('getStreet')
            ->will($this->returnValue($addressData['street']));
        $address
            ->expects($this->any())
            ->method('getCity')
            ->will($this->returnValue($addressData['city']));
        $address
            ->expects($this->any())
            ->method('getRegionCode')
            ->will($this->returnValue($addressData['region_code']));
        $address
            ->expects($this->any())
            ->method('getCountryId')
            ->will($this->returnValue($addressData['country_id']));
        $address
            ->expects($this->any())
            ->method('getPostcode')
            ->will($this->returnValue($addressData['postcode']));

        $session = $this->getModelMockBuilder('eb2ccore/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->assertSame($addressData, EcomDev_Utils_Reflection::invokeRestrictedMethod($session, '_extractAddressData', [$address]));
    }
    public function providerTestExtractShippingData()
    {
        $quoteWithAddresses = $this->getModelMock('sales/quote', ['getAllShippingAddresses']);
        $address = $this->getModelMock(
            'sales/quote_address',
            ['getShippingMethod']
        );

        $quoteWithAddresses
            ->expects($this->any())
            ->method('getAllShippingAddresses')
            ->will($this->returnValue([$address]));
        $address
            ->expects($this->any())
            ->method('getShippingMethod')
            ->will($this->returnValue('flatrate'));
        $addressData = [
            'street' => ['123 Main St'],
            'city' => 'King of Prussia',
            'region_code' => 'PA',
            'country_id' => 'US',
            'postcode' => '19406',
        ];

        $quoteNoAddresses = $this->getModelMock('sales/quote', ['getAllShippingAddresses']);
        $quoteNoAddresses
            ->expects($this->any())
            ->method('getAllShippingAddresses')
            ->will($this->returnValue([]));

        return [
            [
                $quoteWithAddresses,
                $address,
                $addressData,
                [
                    [
                        'method' => 'flatrate',
                        'address' => $addressData,
                    ]
                ],
            ],
            [
                $quoteNoAddresses,
                null,
                [],
                [],
            ],
        ];
    }
    /**
     * Test extracting shipping data from a quote object. Should return array of data when shipping
     * data exists. Empty array otherwise.
     * @param  Mage_Sales_Model_Quote         $quote           The quote object
     * @param  Mage_Sales_Model_Quote_Address $address         Shipping address for quote
     * @param  array                          $addressData     Data extracted from shipping address
     * @param  array                          $shippingExtract The exptected array of data
     * @dataProvider providerTestExtractShippingData
     */
    public function testExtractShippingData($quote, $address, $addressData, $shippingExtract)
    {
        $session = $this->getModelMockBuilder('eb2ccore/session')
            ->disableOriginalConstructor()
            ->setMethods(['_extractAddressData'])
            ->getMock();
        if ($address && $addressData) {
            $session
                ->expects($this->any())
                ->method('_extractAddressData')
                ->with($this->identicalTo($address))
                ->will($this->returnValue($addressData));
        }
        $this->assertSame($shippingExtract, EcomDev_Utils_Reflection::invokeRestrictedMethod($session, '_extractQuoteShippingData', [$quote]));
    }
    /**
     * Test extracting the current coupon code from a quote - should just get the
     * coupon code from the quote and return it.
     */
    public function testExtractCouponData()
    {
        $couponCode = 'coupon-code-123';
        $session = $this->getModelMockBuilder('eb2ccore/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $quote = $this->getModelMock('sales/quote', ['getCouponCode']);
        $quote->expects($this->once())->method('getCouponCode')->will($this->returnValue($couponCode));
        $this->assertSame($couponCode, EcomDev_Utils_Reflection::invokeRestrictedMethod($session, '_extractQuoteCouponData', [$quote]));
    }
    public function providerExtractBillingData()
    {
        return [
            [$this->getModelMock('sales/quote_address'), ['street' => ['630 Allendale Rd'], 'city' => 'King of Prussia']],
            [null, []],
        ];
    }
    /**
     * Test extracting billing address data from a quote. When the quote has a billing
     * address, should return array of address data (extracted via _extractAddressData).
     * When the quote does not have a billing address, should return an empty array.
     * @param  Mage_Sales_Model_Quote_Address|null $billingAddress Quote billing address object
     * @param  array                               $extractedData  Data exptected to be extracted
     * @dataProvider providerExtractBillingData
     */
    public function testExtractQuoteBillingData($billingAddress, $extractedData)
    {
        $quote = $this->getModelMock('sales/quote', ['getBillingAddress']);
        $session = $this->getModelMockBuilder('eb2ccore/session')
            ->disableOriginalConstructor()
            ->setMethods(['_extractAddressData'])
            ->getMock();

        $quote->expects($this->any())
            ->method('getBillingAddress')
            ->will($this->returnValue($billingAddress));
        if ($billingAddress) {
            $session
                ->expects($this->once())
                ->method('_extractAddressData')
                ->with($this->identicalTo($billingAddress))
                ->will($this->returnValue($extractedData));
        } else {
            $session->expects($this->never())
                ->method('_extractAddressData');
        }

        $this->assertSame($extractedData, EcomDev_Utils_Reflection::invokeRestrictedMethod($session, '_extractQuoteBillingData', [$quote]));
    }
    /**
     * Test extracting quote amounts from a quote object. Should return an array
     * of key/value pairs for amounts to be extracted and the data in the quote.
     */
    public function testExtractQuoteAmounts()
    {
        $subtotal = 50.00;
        $discount = -10.00;
        $shipAmt = 15.00;
        $shipDiscount = 5.00;
        $gwPrice = 5.00;
        $gwItemsPrice = 10.00;

        $quoteAddress = Mage::getModel(
            'sales/quote_address',
            [
                'subtotal' => $subtotal, 'discount_amount' => $discount,
                'shipping_discount_amount' => $shipDiscount, 'shipping_amount' => $shipAmt,
                'gw_price' => $gwPrice, 'gw_items_price' => $gwItemsPrice,
            ]
        );

        $quote = $this->getModelMock('sales/quote', ['getAllShippingAddresses']);
        $quote->expects($this->any())
            ->method('getAllShippingAddresses')
            ->will($this->returnValue([$quoteAddress]));

        $session = $this->getModelMockBuilder('eb2ccore/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $this->assertSame(
            [[
                'subtotal' => $subtotal, 'discount' => $discount,
                'ship_amount' => $shipAmt, 'ship_discount' => $shipDiscount,
                'giftwrap_amount' => $gwPrice + $gwItemsPrice,
            ]],
            EcomDev_Utils_Reflection::invokeRestrictedMethod(
                $session,
                '_extractQuoteAmounts',
                [$quote]
            )
        );
    }
    /**
     * @dataProvider providerExtractBillingData
     */
    public function testExtractQuoteData()
    {
        $quote = $this->getModelMock('sales/quote');
        $session = $this->getModelMockBuilder('eb2ccore/session')
            ->disableOriginalConstructor()
            ->setMethods([
                '_extractQuoteCouponData', '_extractQuoteBillingData', '_extractQuoteShippingData',
                '_extractQuoteSkuData', '_extractQuoteAmounts'
            ])
            ->getMock();
        $couponData = 'coupon-code-123';
        $shipData = [['method' => 'flatrate', 'address' => ['street' => ['630 Allendale Rd']], 'city' => 'King of Prussia']];
        $billData = ['street' => ['1075 1st Ave'], 'city' => 'King of Prussia'];
        $skuData = ['45-123' => ['managed' => true, 'virtual' => false, 'qty' => 3]];
        $amountData = ['grand_total' => 55.00];

        $session->expects($this->once())->method('_extractQuoteBillingData')->with($this->identicalTo($quote))->will($this->returnValue($billData));
        $session->expects($this->once())->method('_extractQuoteCouponData')->with($this->identicalTo($quote))->will($this->returnValue($couponData));
        $session->expects($this->once())->method('_extractQuoteShippingData')->with($this->identicalTo($quote))->will($this->returnValue($shipData));
        $session->expects($this->once())->method('_extractQuoteSkuData')->with($this->identicalTo($quote))->will($this->returnValue($skuData));
        $session->expects($this->once())->method('_extractQuoteAmounts')->with($this->identicalTo($quote))->will($this->returnValue($amountData));

        $this->assertSame(
            [
                'billing' => $billData,
                'coupon' => $couponData,
                'shipping' => $shipData,
                'skus' => $skuData,
                'amounts' => $amountData,
            ],
            EcomDev_Utils_Reflection::invokeRestrictedMethod($session, '_extractQuoteData', [$quote])
        );
    }
    /**
     * Data provider for diffBilling test. Providers array of old address data,
     * new address data and the expected diff.
     * @return array Args array
     */
    public function providerDiffBilling()
    {
        $old = ['street' => ['1075 1st Ave'], 'city' => 'King of Prussia'];
        $new = ['street' => ['630 Allendale Rd'], 'city' => 'King of Prussia'];
        return [
            [$old, $new, ['billing' => $new]],
            [$new, $new, []],
            [[], $new, ['billing' => $new]],
        ];
    }
    /**
     * Test diffing billing data. When data has changed, should return array
     * with "billing" key containing the changed data. Currently, this either returns
     * an empty array or the array with "billing" set to the full address data.
     * @param  array  $old  Old billing address
     * @param  array  $new  New billing address
     * @param  array  $diff Expected diff
     * @dataProvider providerDiffBilling
     */
    public function testDiffBilling($old, $new, $diff)
    {
        $session = $this->getModelMockBuilder('eb2ccore/session')->disableOriginalConstructor()->setMethods(null)->getMock();
        $this->assertSame($diff, EcomDev_Utils_Reflection::invokeRestrictedMethod($session, '_diffBilling', [$old, $new]));
    }
    /**
     * Data provider for the diffCoupon test. Providers old coupon code,
     * new coupon code and expected diff.
     * @return array Args array
     */
    public function providerDiffCoupon()
    {
        return [
            [null, 'free-lewt', ['coupon' => 'free-lewt']],
            ['zomg', 'free-lewt', ['coupon' => 'free-lewt']],
            ['zomg', null, ['coupon' => null]],
            ['free-lewt', 'free-lewt', []],
        ];
    }
    /**
     * Test diffing an old coupon code to a new one. When coupon has changed, should return
     * array with 'coupon' key containing the new code. When no change, should return
     * an empty array.
     * @param  string $old  Exiting coupon code
     * @param  string $new  New coupon code
     * @param  array  $diff Expected diff
     * @dataProvider providerDiffCoupon
     */
    public function testDiffCoupon($old, $new, $diff)
    {
        $session = $this->getModelMockBuilder('eb2ccore/session')->disableOriginalConstructor()->setMethods(null)->getMock();
        $this->assertSame($diff, EcomDev_Utils_Reflection::invokeRestrictedMethod($session, '_diffCoupon', [$old, $new]));
    }
    /**
     * Data provider for the diffShipping test. Providers a set of
     * "old" shipping data, a set of "new" shipping data and what the
     * diff is expected to be.
     * @return array Args array
     */
    public function providerDiffShipping()
    {
        $old = [['method' => 'flatrate', 'address' => ['street' => ['630 Allendale Rd'], 'city' => 'King of Prussia']]];
        $new = [['method' => 'standard', 'address' => ['street' => ['630 Allendale Rd'], 'city' => 'San Jose']]];
        return [
            [null, $new, ['shipping' => $new]],
            [$new, $new, []],
            [$old, $new, ['shipping' => $new]],
            [$new, null, ['shipping' => null]],
        ];
    }
    /**
     * Test diffing old shipping data to new shipping data. This check should
     * be pretty dumb in that if any address data has changed, the address should
     * be considered to have completely changed.
     * @param  array  $old  Old address data
     * @param  array  $new  New address data
     * @param  array  $diff Expected diff of the data
     * @dataProvider providerDiffShipping
     */
    public function testDiffShipping($old, $new, $diff)
    {
        $session = $this->getModelMockBuilder('eb2ccore/session')->disableOriginalConstructor()->setMethods(null)->getMock();
        $this->assertSame($diff, EcomDev_Utils_Reflection::invokeRestrictedMethod($session, '_diffShipping', [$old, $new]));
    }
    /**
     * Data provider for the diffSkus test. Provides set of "old" sku data,
     * set of "new" sku data and what the expected diff should be.
     * @return array Args array
     */
    public function providerDiffSkus()
    {
        $old = [
            '45-123' => ['managed' => true, 'virtual' => false, 'qty' => 3, 'item_id' => 1],
            '45-234' => ['managed' => true, 'virtual' => false, 'qty' => 2, 'item_id' => 2],
        ];
        $update = [
            '45-123' => ['managed' => true, 'virtual' => false, 'qty' => 5, 'item_id' => 1],
            '45-234' => ['managed' => true, 'virtual' => false, 'qty' => 2, 'item_id' => 2],
        ];
        $add = [
            '45-123' => ['managed' => true, 'virtual' => false, 'qty' => 3, 'item_id' => 1],
            '45-234' => ['managed' => true, 'virtual' => false, 'qty' => 2, 'item_id' => 2],
            '45-345' => ['managed' => false, 'virtual' => true, 'qty' => 1, 'item_id' => 3],
        ];
        $delete = [
            '45-234' => ['managed' => true, 'virtual' => false, 'qty' => 2, 'item_id' => 2],
        ];
        return [
            // when no old quote data, diff should be all new data
            [
                [],
                $old,
                ['skus' => $old],
            ],
            // when updated, only items that were updated should be included
            [
                $old,
                $update,
                ['skus' => ['45-123' => $update['45-123']]],
            ],
            // when items are added, only added items should be included
            [
                $old,
                $add,
                ['skus' => ['45-345' => $add['45-345']]],
            ],
            // when deleted, the deleted item should be included with qty of 0
            [
                $old,
                $delete,
                ['skus' => ['45-123' => ['managed' => true, 'virtual' => false, 'qty' => 0, 'item_id' => 1]]],
            ],
        ];
    }
    /**
     * Test diffing arrays of sku/item/quantity data. Any items that are updated
     * should be present with the updated quantity, any items that were added
     * should be present with the current quantity, any items that were deleted
     * should be present with a quantity of 0.
     * @param  array  $old  Old skus/item data
     * @param  array  $new  New skus/item data
     * @param  array  $diff Expected diff
     * @dataProvider providerDiffSkus
     */
    public function testDiffSkus($old, $new, $diff)
    {
        $session = $this->getModelMockBuilder('eb2ccore/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->assertSame($diff, EcomDev_Utils_Reflection::invokeRestrictedMethod($session, '_diffSkus', [$old, $new]));
    }
    /**
     * Provide old and new quote amounts and the expected diff of the two
     * @return array Array with old data, new data and expected diff
     */
    public function provideQuoteAmounts()
    {
        return [
            [[['subtotal' => 22.22]], [['subtotal' => 22.22]], []],
            [[['subtotal' => 10.00]], [['subtotal' => 20.00]], ['amounts' => [['subtotal' => 20.00]]]],
            [[], [['subtotal' => 15.00]], ['amounts' => [['subtotal' => 15.00]]]],
        ];
    }
    /**
     * Test checking for differences in quote amounts. If any amounts are
     * different, the new amount should be included in the results.
     * @param array $old Old quote data
     * @param array $new New quote data
     * @param array $diff Expected diff
     * @dataProvider provideQuoteAmounts
     */
    public function testDiffAmounts($old, $new, $diff)
    {
        $session = $this->getModelMockBuilder('eb2ccore/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->assertSame($diff, EcomDev_Utils_Reflection::invokeRestrictedMethod($session, '_diffAmounts', [$old, $new]));
    }

    public function providerDiffQuoteData()
    {
        $empty = [];
        // Old and new data, just used as a source for mock method matches
        $old = ['billing' => [], 'coupon' => null, 'shipping' => [], 'skus' => [], 'amounts' => 12.23];
        $new = ['billing' => [], 'coupon' => 'free-lewt', 'shipping' => [], 'skus' => [], 'amounts' => 12.23];

        // expected diffs for each diff method
        $billDiff = ['billing' => ['street' => ['630 Allendale Rd']]];
        $couponDiff = ['coupon' => 'zomg'];
        $shipDiff = ['shipping' => [['method' => 'flatrate']]];
        $itemDiff = ['skus' => ['45-123' => ['managed' => true, 'virtual' => false, 'qty' => 3]]];
        $amountsDiff = ['grand_total' => 55.55];

        return [
            //    old     new   bill       coupon       shipping   items      amounts       final
            [$old,   $new, $billDiff, $empty,      $empty,    $empty,    $empty,       $billDiff],
            [$old,   $new, $billDiff, $couponDiff, $empty,    $empty,    $empty,       $billDiff + $couponDiff],
            [$old,   $new, $empty,    $couponDiff, $shipDiff, $empty,    $empty,       $couponDiff + $shipDiff],
            [$old,   $new, $billDiff, $couponDiff, $shipDiff, $itemDiff, $empty,       $billDiff + $couponDiff + $shipDiff + $itemDiff],
            [$old,   $new, $billDiff, $couponDiff, $shipDiff, $itemDiff, $amountsDiff, $billDiff + $couponDiff + $shipDiff + $itemDiff + $amountsDiff],
        ];
    }
    /**
     * Test diffing new quote data to old quote data. This test is only for when both
     * sets of quote data are set and not empty.
     * @param  array $old      Old quote data
     * @param  array $new      New quote data
     * @param  array $billing  Diff of billing data
     * @param  array $coupon   Diff of coupon data
     * @param  array $shipping Diff of shipping data
     * @param  array $items    Diff of item data
     * @param  array $amounts  Diff of the quote amounts
     * @param  array $final    Expected diff of full qoute
     * @dataProvider providerDiffQuoteData
     */
    public function testDiffQuoteData($old, $new, $billing, $coupon, $shipping, $items, $amounts, $final)
    {
        $session = $this->getModelMockBuilder('eb2ccore/session')
            ->disableOriginalConstructor()
            ->setMethods([
                '_hasInventoryExpired', '_diffBilling', '_diffCoupon', '_diffShipping',
                '_diffSkus', '_diffAmounts'
            ])
            ->getMock();
        // assume none of the data has expired for now
        $session->expects($this->any())->method('_hasInventoryExpired')->will($this->returnValue(false));
        $session
            ->expects($this->any())
            ->method('_diffBilling')
            ->with($this->identicalTo($old['billing']), $this->identicalTo($new['billing']))
            ->will($this->returnValue($billing));
        $session
            ->expects($this->any())
            ->method('_diffCoupon')
            ->with($this->identicalTo($old['coupon']), $this->identicalTo($new['coupon']))
            ->will($this->returnValue($coupon));
        $session
            ->expects($this->any())
            ->method('_diffShipping')
            ->with($this->identicalTo($old['shipping']), $this->identicalTo($new['shipping']))
            ->will($this->returnValue($shipping));
        $session
            ->expects($this->any())
            ->method('_diffSkus')
            ->with($this->identicalTo($old['skus']), $this->identicalTo($new['skus']))
            ->will($this->returnValue($items));
        $session
            ->expects($this->any())
            ->method('_diffAmounts')
            ->with($this->identicalTo($old['amounts']), $this->identicalTo($new['amounts']))
            ->will($this->returnValue($amounts));

        $this->assertSame($final, EcomDev_Utils_Reflection::invokeRestrictedMethod($session, '_diffQuoteData', [$old, $new]));
    }
    /**
     * When the "old" quote data is empty, all of the new quote data should be returned
     * as the changes - consider whole quote as having changed.
     */
    public function testDiffQuoteDataReturnNewQuoteDataWhenOldQuoteIsEmpty()
    {
        $session = $this->getModelMockBuilder('eb2ccore/session')->disableOriginalConstructor()->setMethods(null)->getMock();
        $old = [];
        $new = ['all', 'of', 'this', 'should', 'be', 'returned', 'as', 'is'];

        $this->assertSame($new, EcomDev_Utils_Reflection::invokeRestrictedMethod($session, '_diffQuoteData', [$old, $new]));
    }
    /**
     * Test getting the tax update required flag. Should just return the
     * value of the magic data used to store the flag
     */
    public function testIsTaxUpdateRequired()
    {
        $session = $this->getModelMockBuilder('eb2ccore/session')->disableOriginalConstructor()->setMethods(null)->getMock();
        $session->setTaxUpdateRequiredFlag(true);
        $this->assertTrue($session->isTaxUpdateRequired());
        $session->setTaxUpdateRequiredFlag(false);
        $this->assertFalse($session->isTaxUpdateRequired());
    }
    /**
     * Test getting the details update required flag. Should just return the value
     * of the magic data used to store the flag
     */
    public function testIsDetailsUpdateRequired()
    {
        $session = $this->getModelMockBuilder('eb2ccore/session')->disableOriginalConstructor()->setMethods(null)->getMock();
        $session->setDetailsUpdateRequiredFlag(true);
        $this->assertTrue($session->isDetailsUpdateRequired());
        $session->setDetailsUpdateRequiredFlag(false);
        $this->assertFalse($session->isDetailsUpdateRequired());
    }
    /**
     * Test resetting the tax update required flag. Calling this method should force the
     * flag go to be unset.
     */
    public function testResetTaxUpdateRequired()
    {
        $session = $this->getModelMockBuilder('eb2ccore/session')->disableOriginalConstructor()->setMethods(null)->getMock();
        $session->setTaxUpdateRequiredFlag(true);
        $this->assertSame($session, $session->resetTaxUpdateRequired());
        $this->assertNull($session->getTaxUpdateRequiredFlag());
    }
    /**
     * Test resetting the inventory details update required flag. Calling this method should force the
     * flag go to be unset.
     */
    public function testResetDetailsUpdateRequired()
    {
        $session = $this->getModelMockBuilder('eb2ccore/session')->disableOriginalConstructor()->setMethods(null)->getMock();
        $session->setTaxUpdateRequiredFlag(true);
        $this->assertSame($session, $session->resetDetailsUpdateRequired());
        $this->assertNull($session->getDetailsUpdateRequiredFlag());
    }
    /**
     * Test getting the diff of the "old" quote to the new quote. Should just return
     * whatever was set in the "magic" data when the quote data was last updated.
     */
    public function testGetQuoteChanges()
    {
        $session = $this->getModelMockBuilder('eb2ccore/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $changes = ['skus' => ['45-123' => ['qty' => 3]]];
        $session->setQuoteChanges($changes);
        $this->assertSame($changes, $session->getQuoteChanges());
    }
    /**
     * Data provider for the updateWithQuote test method. Provides the set of changes
     * in the quote and whether the set of changes should result in taxes, quantity
     * and/or details being flagged to be updated.
     * @return array Args array of booleans for which flags should be set
     */
    public function providerUpdateWithQuote()
    {
        return [
            //    currFlag newFlag
            [true,    false],
            [false,   true],
            [false,   false],
        ];
    }
    /**
     * Test updating the quote data stored in the session with a new quote. Method should
     * extract data from the new quote (using appropriate methods), and then compare it to
     * the previously checked quote data (again, using appropriate methods). The diff data
     * should then be examined for indicating that an update is needed to tax and/or inventory
     * requests. After running this method, the quote data, diff data and all flags should
     * be updated based on the changes made to the quote.
     *
     * @param  bool $currFlag    Is details already flagged for updates
     * @param  bool $changeFlag  Should these changes require details updates
     * @dataProvider providerUpdateWithQuote
     */
    public function testUpdateWithQuote($currFlag, $changeFlag)
    {
        $quote = $this->getModelMock('sales/quote');
        // Stub 'init' method instead of disabling constructor
        // to avoid headers already sent error.
        $session = $this->getModelMockBuilder('eb2ccore/session')
            ->setMethods([
                'getCurrentQuoteData', 'setCurrentQuoteData', 'setQuoteChanges',
                '_extractQuoteData', '_diffQuoteData', 'init',
                'getTaxUpdateRequiredFlag', 'getDetailsUpdateRequiredFlag',
                'setTaxUpdateRequiredFlag', 'setDetailsUpdateRequiredFlag',
                '_changeRequiresTaxUpdate', '_changeRequiresDetailsUpdate',
            ])
            ->getMock();

        $newData = ['skus' => ['45-123' => ['managed' => true, 'virtual' => false, 'qty' => 3]]];
        $currentData = ['last_updated' => 'timestamp this was last updated'];
        $diffData = ['skus' => ['45-123' => ['managed' => true, 'virtual' => false, 'qty' => 3]]];
        $newDataWithStamp = ['skus' => $newData['skus'], 'last_updated' => $currentData['last_updated']];

        $session
            ->expects($this->any())
            ->method('getCurrentQuoteData')
            ->will($this->returnValue($currentData));
        $session
            ->expects($this->once())
            ->method('_extractQuoteData')
            ->with($this->identicalTo($quote))
            ->will($this->returnValue($newData));
        $session
            ->expects($this->once())
            ->method('_diffQuoteData')
            ->with($this->identicalTo($currentData), $this->identicalTo($newDataWithStamp))
            ->will($this->returnValue($diffData));

        // tax flag
        $session
            ->expects($this->any())
            ->method('getTaxUpdateRequiredFlag')
            ->will($this->returnValue($currFlag));
        $session
            ->expects($this->any())
            ->method('_changeRequiresTaxUpdate')
            ->with($this->identicalTo($newDataWithStamp), $this->identicalTo($diffData))
            ->will($this->returnValue($changeFlag));
        $session
            ->expects($this->once())
            ->method('setTaxUpdateRequiredFlag')
            ->with($this->identicalTo($changeFlag))
            ->will($this->returnSelf());
        // details flag
        $session
            ->expects($this->any())
            ->method('getDetailsUpdateRequiredFlag')
            ->will($this->returnValue($currFlag));
        $session
            ->expects($this->any())
            ->method('_changeRequiresDetailsUpdate')
            ->with($this->identicalTo($newDataWithStamp), $this->identicalTo($diffData))
            ->will($this->returnValue($changeFlag));
        $session
            ->expects($this->once())
            ->method('setDetailsUpdateRequiredFlag')
            ->with($this->identicalTo($changeFlag))
            ->will($this->returnSelf());

        $session
            ->expects($this->once())
            ->method('setCurrentQuoteData')
            ->with($this->identicalTo($newDataWithStamp))
            ->will($this->returnSelf());
        $session
            ->expects($this->once())
            ->method('setQuoteChanges')
            ->with($this->identicalTo($diffData))
            ->will($this->returnSelf());

        $this->assertSame($session, $session->updateWithQuote($quote));
    }
    /**
     * Test checking an array to have any item with the given key set to a truthy value.
     */
    public function testAnyItem()
    {
        $items = [['foo' => true, 'bar' => false], ['foo' => false, 'bar' => false], ['foo' => true]];
        $session = $this->getModelMockBuilder('eb2ccore/session')->disableOriginalConstructor()->setMethods(null)->getMock();
        $this->assertTrue(EcomDev_Utils_Reflection::invokeRestrictedMethod($session, '_anyItem', [$items, 'foo']));
        $this->assertFalse(EcomDev_Utils_Reflection::invokeRestrictedMethod($session, '_anyItem', [$items, 'bar']));
    }
    /**
     * Test checking for any item in a list of items to include a virtual item - an
     * item with a 'virtual' key set to true.
     */
    public function testItemsIncludeVirtualItem()
    {
        $items = ['45-123' => []];
        $session = $this->getModelMockBuilder('eb2ccore/session')
            ->disableOriginalConstructor()
            ->setMethods(['_anyItem'])
            ->getMock();
        // _anyItem will detect item, just make sure it's given the list of items and the right key
        $session
            ->expects($this->once())
            ->method('_anyItem')
            ->with($this->identicalTo($items), $this->identicalTo('virtual'))
            ->will($this->returnValue(true));
        $this->assertTrue(EcomDev_Utils_Reflection::invokeRestrictedMethod($session, '_itemsIncludeVirtualItem', [$items]));
    }
    /**
     * Test checking for any item in a list of items to include a managed stock
     * item - an item with a 'managed' key set to true.
     */
    public function testItemsIncludeManagedItem()
    {
        $items = ['45-123' => []];
        $session = $this->getModelMockBuilder('eb2ccore/session')
            ->disableOriginalConstructor()
            ->setMethods(['_anyItem'])
            ->getMock();
        // _anyItem will detect item, just make sure it's given the list of items and the right key
        $session
            ->expects($this->once())
            ->method('_anyItem')
            ->with($this->identicalTo($items), $this->identicalTo('managed'))
            ->will($this->returnValue(true));
        $this->assertTrue(EcomDev_Utils_Reflection::invokeRestrictedMethod($session, '_itemsIncludeManagedItem', [$items]));
    }
    /**
     * Data provider of sample quote data changes. Providers an array of changes,
     * whether the quote has virtual items and which flags should be set to true based on the
     * diff data.
     * @return array Args array of quote changes, if the quote has virtual items and how each flag should be set
     */
    public function providerQuoteDiffs()
    {
        $quoteData = ['skus' => ['45-123' => ['managed' => true, 'virtual' => false, 'qty' => 3]]];
        // various quote diffs to be tested against
        $coupon = ['coupon' => 'zomg-deelz'];
        $amount = ['amounts' => ['grand_total' => 22.22]];
        $billing = ['billing' => ['street' => ['123 Main St'], 'city' => 'King of Prussia']];
        $shipping = ['shipping' => [['method' => 'flatrate']]];
        $managed = ['skus' => ['45-123' => ['managed' => true, 'virtual' => false, 'qty' => 5]]];
        $noManaged = ['skus' => ['45-234' => ['managed' => false, 'virtual' => true, 'qty' => 2]]];

        return [
            // quoteData diff        has virtual hasManaged tax    deets
            [$quoteData, $coupon,    false,      true,      true,   false],
            [$quoteData, $billing,   false,      true,      false,  false],
            [$quoteData, $billing,   true,       true,      true,   false],
            [$quoteData, $shipping,  false,      true,      true,    true],
            [$quoteData, $shipping,  false,      false,     true,   false],
            [$quoteData, $managed,   false,      true,      true,    true],
            [$quoteData, $noManaged, true,       false,     true,   false],
            [$quoteData, $amount,    false,      false,     true,   false],
        ];
    }
    /**
     * Test checking the quote diff data for requiring tax data to be updated.
     * @param  array   $quoteData  Array of quote data
     * @param  array   $diffData   Array of quote changes
     * @param  bool $hasVirtual Does the quote contain virtual items
     * @param  bool $hasManaged Does the quote contain managed stock items
     * @param  bool $flagTax    Should this flag tax
     * @dataProvider providerQuoteDiffs
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function testChangeRequiresTaxUpdate($quoteData, $diffData, $hasVirtual, $hasManaged, $flagTax)
    {
        $session = $this->getModelMockBuilder('eb2ccore/session')
            ->disableOriginalConstructor()
            ->setMethods(['_itemsIncludeVirtualItem', '_itemsIncludeManagedItem'])
            ->getMock();
        $session
            ->expects($this->any())
            ->method('_itemsIncludeVirtualItem')
            ->will($this->returnValue($hasVirtual));
        $session
            ->expects($this->any())
            ->method('_itemsIncludeManagedItem')
            ->will($this->returnValue($hasManaged));
        $this->assertSame($flagTax, EcomDev_Utils_Reflection::invokeRestrictedMethod($session, '_changeRequiresTaxUpdate', [$quoteData, $diffData]));
    }
    /**
     * Test checking the quote diff data for requiring tax data to be updated.
     * @param  array   $quoteData  Array of quote data
     * @param  array   $diffData   Array of quote changes
     * @param  bool $hasVirtual Does the quote contain virtual items
     * @param  bool $hasManaged Does the quote contain managed stock items
     * @param  bool $flagTax    Should this flag tax
     * @param  bool $flagDeets  Should this flag details
     * @dataProvider providerQuoteDiffs
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function testChangeRequiresDetailsUpdate(
        $quoteData,
        $diffData,
        $hasVirtual,
        $hasManaged,
        $flagTax,
        $flagDeets
    ) {
        $session = $this->getModelMockBuilder('eb2ccore/session')
            ->disableOriginalConstructor()
            ->setMethods(['_itemsIncludeVirtualItem', '_itemsIncludeManagedItem'])
            ->getMock();
        $session
            ->expects($this->any())
            ->method('_itemsIncludeVirtualItem')
            ->will($this->returnValue($hasVirtual));
        $session
            ->expects($this->any())
            ->method('_itemsIncludeManagedItem')
            ->will($this->returnValue($hasManaged));
        $this->assertSame($flagDeets, EcomDev_Utils_Reflection::invokeRestrictedMethod($session, '_changeRequiresDetailsUpdate', [$quoteData, $diffData]));
    }
    /**
     * Test updating quote inventory details with a given qoute. Should
     * extract sku data from the quote and update the "current" quote data in the
     * session with the updated list of items. Calling this method should also
     * update the "last_updated" timestamp on the quote data.
     */
    public function testUpdateQuoteInventory()
    {
        $lastUpdated = '2000-01-01T12:00:00+00:00';
        $skuDataExtract = ['45-123' => ['managed' => true, 'virtual' => false, 'qty' => 3]];
        $oldData = [
            'billing' => [],
            'coupon' => 'free-lewt',
            'shipping' => [],
            'skus' => ['45-123' => ['managed' => true, 'virtual' => false, 'qty' => 2], '45-234' => ['managed' => true, 'virtual' => false, 'qty' => 3]],
            'last_updated' => $lastUpdated,
        ];

        $quote = $this->getModelMock('sales/quote');
        $session = $this->getModelMockBuilder('eb2ccore/session')
            ->disableOriginalConstructor()
            ->setMethods(['getCurrentQuoteData', '_extractQuoteSkuData', 'setCurrentQuoteData'])
            ->getMock();

        $session
            ->expects($this->once())
            ->method('getCurrentQuoteData')
            ->will($this->returnValue($oldData));
        $session
            ->expects($this->once())
            ->method('_extractQuoteSkuData')
            ->with($this->identicalTo($quote))
            ->will($this->returnValue($skuDataExtract));
        $session
            ->expects($this->once())
            ->method('setCurrentQuoteData')
            ->with($this->callback(
                // make sure the new quote data (arg to the callback) has been updated
                // as expected - billing, coupon and shipping data should all remain
                // the same. skus should match data extracted from the quote.
                // last_updated should have been updated to the current time - best
                // test I can think of for that is just making sure the new time is more recent that the old one
                function ($quoteData) use ($oldData, $skuDataExtract) {
                    return $quoteData['billing'] === $oldData['billing'] &&
                        $quoteData['coupon'] === $oldData['coupon'] &&
                        $quoteData['shipping'] === $oldData['shipping'] &&
                        $quoteData['skus'] === $skuDataExtract &&
                        new DateTime($quoteData['last_updated']) > new DateTime($oldData['last_updated']);
                }
            ));

        $this->assertSame($session, $session->updateQuoteInventory($quote));
    }

    public function provideTrueFalseSequence()
    {
        return [
            [true, false, true],
            [false, true, true],
            [false, false, false],
            [null, true, true],
        ];
    }
    /**
     * make sure that once the value becomes true it remains true not matter what is set
     * @param  bool $init    initial flag value
     * @param  bool $current new value
     * @param  bool $result  expected result
     * @dataProvider provideTrueFalseSequence()
     */
    public function testSetTaxUpdateRequired($init, $current, $result)
    {
        $session = $this->getModelMockBuilder('eb2ccore/session')
            ->disableOriginalConstructor()
            ->setMethods(['noMockedMethods'])
            ->getMock();
        $session->setTaxUpdateRequiredFlag($init);
        $session->setTaxUpdateRequired($current);
        $this->assertSame($result, $session->getTaxUpdateRequiredFlag());
    }
    /**
     * make sure that once the value becomes true it remains true not matter what is set
     * @param  bool $init    initial flag value
     * @param  bool $current new value
     * @param  bool $result  expected result
     * @dataProvider provideTrueFalseSequence()
     */
    public function testDetailsTaxUpdateRequired($init, $current, $result)
    {
        $session = $this->getModelMockBuilder('eb2ccore/session')
            ->disableOriginalConstructor()
            ->setMethods(['noMockedMethods'])
            ->getMock();
        $session->setDetailsUpdateRequiredFlag($init);
        $session->setDetailsUpdateRequired($current);
        $this->assertSame($result, $session->getDetailsUpdateRequiredFlag());
    }
}
