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

class EbayEnterprise_Multishipping_Test_Model_Override_Sales_Service_QuoteTest extends EcomDev_PHPUnit_Test_Case
{
	/** @var Mage_Sales_Model_Quote */
	protected $_quote;
	/** @var Mage_Sales_Model_Order */
	protected $_order;
	/** @var Mage_Sales_Model_Quote_Payment */
	protected $_payment;
	/** @var EbayEnterprise_Multishipping_Helper_Dispatcher_Interface */
	protected $_checkoutDispatcher;
	/** @var EbayEnterprise_Multishipping_Helper_Factory */
	protected $_multishippingFactory;
	/** @var Mage_Sales_Model_Covert_Quote */
	protected $_quoteConvertor;
	/** @var Mage_Core_Model_Resource_Transaction */
	protected $_transaction;
	/** @var Mage_Customer_Model_Session */
	protected $_customerSession;
	/** @var Mage_Customer_Model_Customer */
	protected $_customer;
	/** @var EbayEnterprise_Multishipping_Override_Model_Sales_Service_Quote */
	protected $_serviceQuote;

	protected function setUp()
	{
		$this->_quote = $this->getModelMock(
			'sales/quote',
			['reserveOrderId', 'getAllAddresses', 'getPayment', 'getCustomerId', 'getCustomer']
		);
		$this->_order = $this->getModelMock(
			'sales/order',
			[
				'setId', 'getItemsCollection', 'setBillingAddress',
				'addAddress', 'addItem', 'collectShipmentAmounts', 'setPayment',
				'setQuote', 'addData'
			]
		);
		$this->_quotePayment = $this->getModelMock('sales/quote_payment');
		$this->_orderPayment = $this->getModelMock('sales/order_payment');

		$this->_checkoutDispatcher = $this->getHelperMock(
			'ebayenterprise_multishipping/dispatcher_interface',
			['dispatchBeforeOrderSubmit', 'dispatchOrderSubmitSuccess', 'dispatchOrderSubmitFailure', 'dispatchAfterOrderSubmit',],
			true
		);
		$this->_multishippingFactory = $this->getHelperMock(
			'ebayenterprise_multishipping/factory',
			['createOrderSaveTransaction']
		);
		$this->_quoteConvertor = $this->getModelMock(
			'sales/convert_quote',
			['toOrder', 'addressToOrderAddress', 'itemToOrderItem', 'paymentToOrderPayment',]
		);
		$this->_transaction = $this->getModelMock(
			'core/resource_transaction',
			['save']
		);
		$this->_customerSession = $this->getModelMockBuilder('customer/session')
			->disableOriginalConstructor()
			->setMethods(['isLoggedIn'])
			->getMock();
		$this->_customer = $this->getModelMock('customer/customer', ['setId']);

		$this->_serviceQuote = $this->getModelMock(
			'sales/service_quote',
			['_deleteNominalItems', '_validate', '_inactivateQuote'],
			false,
			[$this->_quote]
		);
		$this->_serviceQuote
			->setMultishippingFactory($this->_multishippingFactory)
			->setCheckoutDispatcher($this->_checkoutDispatcher)
			->setCustomerSession($this->_customerSession)
			->setConvertor($this->_quoteConvertor);
	}

	/**
	 * Stub out dependencies to get through a very basic completion of an order
	 * submit.
	 *
	 * @return self
	 */
	protected function _stubForBasicOrderSubmitCompletion()
	{
		// Stubs to get through submit order. Assertions related to these stubs
		// will be covered in more targeted tests.
		$this->_customerSession->method('isLoggedIn')->will($this->returnValue(false));
		$this->_multishippingFactory->method('createOrderSaveTransaction')->will($this->returnValue($this->_transaction));
		$this->_order->method('addData')->will($this->returnSelf());
		$this->_order->method('collectShipmentAmounts')->will($this->returnSelf());
		$this->_order->method('getItemsCollection')->will($this->returnValue([]));
		$this->_order->method('setId')->will($this->returnSelf());
		$this->_order->method('setPayment')->will($this->returnSelf());
		$this->_order->method('setQuote')->will($this->returnSelf());
		$this->_quote->method('getAllAddresses')->will($this->returnValue([]));
		$this->_quote->method('getCustomer')->will($this->returnValue($this->_customer));
		$this->_quote->method('getPayment')->will($this->returnValue($this->_quotePayment));
		$this->_quoteConvertor->method('paymentToOrderPayment')->will($this->returnValue($this->_orderPayment));
		$this->_quoteConvertor->method('toOrder')->will($this->returnValue($this->_order));
		return $this;
	}

	/**
	 * Create a stub order address with the provided address type.
	 *
	 * @param string
	 * @return Mage_Sales_Model_Order_Address
	 */
	protected function _stubOrderAddress($type)
	{
		$address = $this->getModelMock('sales/order_address', ['getAddressType']);
		$address->method('getAddressType')->will($this->returnValue($type));
		return $address;
	}

	/**
	 * Create a stub quote address with the provided address type and items.
	 *
	 * @param string
	 * @param Mage_Sales_Model_Quote_Item_Abstract[]
	 * @return Mage_Sales_Model_Quote_Address
	 */
	protected function _stubQuoteAddress($type, array $items = [])
	{
		$address = $this->getModelMock('sales/quote_address', ['getAddressType', 'getAllItems']);
		$address->method('getAddressType')->will($this->returnValue($type));
		$address->method('getAllItems')->will($this->returnValue($items));
		return $address;
	}

	/**
	 * Stub a quote item with the provided product type and related quote item -
	 * quote item that a quote address item originated from.
	 *
	 * @param string|null
	 * @param Mage_Sales_Model_Quote_Item_Abstract|null
	 * @return Mage_Sales_Model_Quote_Item_Abstract
	 */
	protected function _stubQuoteItem(
		$productType = null,
		Mage_Sales_Model_Quote_Item_Abstract $quoteItem = null
	) {
		$item = $this->getModelMock('sales/quote_item_abstract', ['getProductType', 'getQuoteItem'], true);
		$item->method('getProductType')->will($this->returnValue($productType));
		$item->method('getQuoteItem')->will($this->returnValue($quoteItem));
		return $item;
	}

	/**
	 * When an order is submitted, a new order should be created from the quote,
	 * saved with relevant data in a single DB transaction, and finally the new
	 * order should be returned.
	 */
	public function testSubmitOrderSuccess()
	{
		// Stub out dependencies to get through the basic flow of submitOrder.
		$this->_stubForBasicOrderSubmitCompletion();

		// Side-effect tests: assert required behaviors

		// Ensure inherited methods are called - the behavior of these methods
		// is taken as being correct so the methods are not tested, only that
		// interactions with the methods is maintained.
		$this->_serviceQuote->expects($this->once())
			->method('_deleteNominalItems')
			->will($this->returnSelf());
		$this->_serviceQuote->expects($this->once())
			->method('_validate')
			->will($this->returnSelf());
		$this->_serviceQuote->expects($this->once())
			->method('_inactivateQuote')
			->will($this->returnSelf());

		// Ensure proper events are dispatched while submitting the order.
		// Defer to the checkout dispatcher for ensuring the proper events happen,
		// but still need to ensure the correct ones would be triggered when
		// successfully creating an order.
		$this->_checkoutDispatcher->expects($this->once())
			->method('dispatchBeforeOrderSubmit')
			->with($this->identicalTo($this->_quote), $this->identicalTo($this->_order))
			->will($this->returnSelf());
		$this->_checkoutDispatcher->expects($this->once())
			->method('dispatchAfterOrderSubmit')
			->with($this->identicalTo($this->_quote), $this->identicalTo($this->_order))
			->will($this->returnSelf());
		$this->_checkoutDispatcher->expects($this->once())
			->method('dispatchOrderSubmitSuccess')
			->with($this->identicalTo($this->_quote), $this->identicalTo($this->_order))
			->will($this->returnSelf());
		$this->_checkoutDispatcher->expects($this->never())
			->method('dispatchOrderSubmitFailure');

		// Ensure that an order is reserved for the quote.
		$this->_quote->expects($this->once())
			->method('reserveOrderId')
			->will($this->returnSelf());

		// Ensure that the transaction for order creation is saved successfully.
		// This will result in the order being saved and "created".
		$this->_transaction->expects($this->once())
			->method('save')
			->will($this->returnSelf());

		// When all goes successfully, the newly created order object should be
		// returned when submitting the order.
		$this->assertSame($this->_order, $this->_serviceQuote->submitOrder());
	}

	/**
	 * When an order fails to be submitted - e.g. transaction fails, quote
	 * should be kept active,
	 */
	public function testSubmitOrderFailure()
	{
		// Stub out dependencies to get through the basic flow of submitOrder.
		$this->_stubForBasicOrderSubmitCompletion();

		// Create the exception expected to be thrown while saving the
		// order submit transaction.
		$failureMessage = 'Failure exception from unit test.';
		$failureException = new Exception($failureMessage);

		// Side-effect tests:

		// Ensure the transaction is saved. In the failure case, saving the
		// transaction should throw an exception to prevent the order from being
		// created.
		$this->_transaction->expects($this->once())
			->method('save')
			->will($this->throwException($failureException));

		// Ensure proper events are dispatched while failing to submit an order.
		// Defer to the checkout dispatcher for ensuring the proper Magento
		// events happen, but still need to ensure the correct ones would be
		// triggered when failing to create an order.
		$this->_checkoutDispatcher->expects($this->once())
			->method('dispatchBeforeOrderSubmit')
			->with($this->identicalTo($this->_quote), $this->identicalTo($this->_order))
			->will($this->returnSelf());
		$this->_checkoutDispatcher->expects($this->once())
			->method('dispatchOrderSubmitFailure')
			->with($this->identicalTo($this->_quote), $this->identicalTo($this->_order))
			->will($this->returnSelf());
		$this->_checkoutDispatcher->expects($this->never())
			->method('dispatchAfterOrderSubmit');
		$this->_checkoutDispatcher->expects($this->never())
			->method('dispatchOrderSubmitSuccess');

		// Ensure that failed order cleanup is triggered. More thorough testing
		// of this cleanup handled in a more specific test. This should just
		// make sure that at least some of it has been triggered.
		$this->_order->expects($this->once())
			->method('setId')
			->with($this->isNull())
			->will($this->returnSelf());

		$this->setExpectedException(get_class($failureException), $failureMessage);

		$this->_serviceQuote->submitOrder();
	}

	/**
	 * When creating an order, the quote should be converted to an order; the
	 * quote payment should be converted to an order payment; order shipment
	 * totals should be collected; the order should be given a reference to
	 * the quote.
	 *
	 * This tests only the outermost aspects of creating an order from a quote,
	 * more specific tests will cover converting addresses and items.
	 */
	public function testCreateOrder()
	{
		// Skip over adding addresses for this test, just want to ensure
		// outermost order interactions are happening.
		$this->_quote->method('getAllAddresses')->will($this->returnValue([]));

		// Setup some extra order data expected to be added to the created order.
		$orderData = ['foo' => 'bar'];
		$this->_serviceQuote->setOrderData($orderData);

		// Setup stubs to get quote payment and convert quote and quote payment
		// to an order and order payment.
		$this->_quote->method('getPayment')->will($this->returnValue($this->_quotePayment));
		$this->_quoteConvertor->method('toOrder')
			->with($this->identicalTo($this->_quote))
			->will($this->returnValue($this->_order));
		$this->_quoteConvertor->method('paymentToOrderPayment')
			->with($this->identicalTo($this->_quotePayment))
			->will($this->returnValue($this->_orderPayment));

		// Side-effect tests: ensure that order shipment amounts are collected,
		// order payment is set, quote is set on the order and extra order data
		// set on the service model are added to the order.
		$this->_order->expects($this->once())
			->method('collectShipmentAmounts')
			->will($this->returnSelf());
		$this->_order->expects($this->once())
			->method('setPayment')
			->with($this->identicalTo($this->_orderPayment))
			->will($this->returnSelf());
		$this->_order->expects($this->once())
			->method('setQuote')
			->with($this->identicalTo($this->_quote))
			->will($this->returnSelf());
		$this->_order->expects($this->once())
			->method('addData')
			->with($this->identicalTo($orderData))
			->will($this->returnSelf());

		$this->assertSame(
			$this->_order,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$this->_serviceQuote,
				'_createOrder'
			)
		);
	}

	/**
	 * Provide an address type and the method that should be used to add the
	 * address to a new order.
	 *
	 * @param array
	 */
	public function provideAddressToOrderAddressTypes()
	{
		return [
			[Mage_Sales_Model_Order_Address::TYPE_BILLING, 'setBillingAddress'],
			[Mage_Sales_Model_Order_Address::TYPE_SHIPPING, 'addAddress'],
		];
	}

	/**
	 * When adding an address to an order, billing addresses should be added
	 * as the order billing address, non-billing addresses should just be added.
	 *
	 * @param string
	 * @param string
	 * @dataProvider provideAddressToOrderAddressTypes
	 */
	public function testAddAddressToOrder($addressType, $addAddressMethod)
	{
		$order = $this->getModelMock('sales/order', ['setBillingAddress', 'addAddress']);
		$address = $this->getModelMock('sales/order_address', ['getAddressType']);

		$address->method('getAddressType')->will($this->returnValue($addressType));
		$order->expects($this->once())
			->method($addAddressMethod)
			->with($this->identicalTo($address))
			->will($this->returnSelf());

		$this->assertSame(
			$this->_serviceQuote,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$this->_serviceQuote,
				'_addAddressToOrder',
				[$address, $order]
			)
		);
	}

	/**
	 * When creating order addresses for a set of quote addresses, each address
	 * in the quote should be converted to an order address and added to
	 * the order.
	 */
	public function testCreateOrderAddresses()
	{
		// quote addresses used as source for creating order addresses, only
		// need separate instances to match while going into the quote convertor
		$billingAddress = $this->_stubQuoteAddress(Mage_Sales_Model_Quote_Address::TYPE_BILLING);
		$shippingAddress = $this->_stubQuoteAddress(Mage_Sales_Model_Quote_Address::TYPE_SHIPPING);
		$orderBillingAddress = $this->_stubOrderAddress(Mage_Sales_Model_Order_Address::TYPE_BILLING);
		$orderShippingAddress = $this->_stubOrderAddress(Mage_Sales_Model_Order_Address::TYPE_SHIPPING);

		// Setup the list of addresses such that billing address will be
		// encountered first, then the shipping address.
		$addresses = [$billingAddress, $shippingAddress];

		// Mock the quote converter to expect to be converting 2 addresses -
		// the billing and shipping addresses.
		$this->_quoteConvertor->expects($this->exactly(2))
			->method('addressToOrderAddress')
			->withConsecutive([$billingAddress], [$shippingAddress])
			->will($this->onConsecutiveCalls($orderBillingAddress, $orderShippingAddress));

		// Ensure that billing and shipping address are properly added to the order.
		$this->_order->expects($this->once())
			->method('setBillingAddress')
			->with($this->identicalTo($orderBillingAddress))
			->will($this->returnSelf());
		$this->_order->expects($this->once())
			->method('addAddress')
			->with($this->identicalTo($orderShippingAddress))
			->will($this->returnSelf());

		$this->assertSame(
			$this->_serviceQuote,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$this->_serviceQuote,
				'_createOrderAddresses',
				[$addresses, $this->_order]
			)
		);
	}

	/**
	 * When creating order items from quote items, each item should be converted
	 * to an order item; the address the item was created for should be set on
	 * the created item and the product type of the original item should be
	 * copied over to the item (will not happen automatically for quote address
	 * items).
	 */
	public function testCreateOrderItems()
	{
		$aProductType = 'simple';
		$bProductType = 'configurable';
		$quoteItemA = $this->_stubQuoteItem($aProductType, null);
		$parentQuoteItem = $this->_stubQuoteItem($bProductType, null);
		$quoteItemB = $this->_stubQuoteItem(null, $parentQuoteItem);
		$orderItemA = $this->getModelMock('sales/order_item', ['setOrderAddress', 'setProductType']);
		$orderItemB = $this->getModelMock('sales/order_item', ['setOrderAddress', 'setProductType']);
		$quoteItems = [$quoteItemA, $quoteItemB];

		// The order address the items are being created for.
		$orderAddress = Mage::getModel('sales/order_address');

		// Mock the quote convertor to expect to be converting the two quote
		// items to two order items.
		$this->_quoteConvertor->expects($this->exactly(2))
			->method('itemToOrderItem')
			->withConsecutive([$quoteItemA], [$quoteItemB])
			->will($this->onConsecutiveCalls($orderItemA, $orderItemB));
		// Ensure that both order items have the order address they were created
		// for linked to the item.
		$orderItemA->expects($this->once())
			->method('setOrderAddress')
			->with($this->identicalTo($orderAddress))
			->will($this->returnSelf());
		$orderItemB->expects($this->once())
			->method('setOrderAddress')
			->with($this->identicalTo($orderAddress))
			->will($this->returnSelf());
		// Ensure that both order items have the appropriate product type set.
		// When converting from address items, the item will not have a product
		// type - only available through the associated quote item.
		$orderItemA->expects($this->once())
			->method('setProductType')
			->with($this->identicalTo($aProductType))
			->will($this->returnSelf());
		$orderItemB->expects($this->once())
			->method('setProductType')
			->with($this->identicalTo($bProductType))
			->will($this->returnSelf());
		// Ensure that both order items are added to the order they were
		// created for.
		$this->_order->expects($this->exactly(2))
			->method('addItem')
			->withConsecutive([$orderItemA], [$orderItemB])
			->will($this->returnSelf());

		$this->assertSame(
			$this->_serviceQuote,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$this->_serviceQuote,
				'_createOrderItems',
				[$quoteItems, $orderAddress, $this->_order]
			)
		);
	}
}
