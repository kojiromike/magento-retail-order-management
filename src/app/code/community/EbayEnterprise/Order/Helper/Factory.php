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

use eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderCancelRequest;
use eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderCancelResponse;
use eBayEnterprise\RetailOrderManagement\Payload\Customer\IOrderSummaryRequest;
use eBayEnterprise\RetailOrderManagement\Payload\Customer\IOrderSummaryResponse;
use eBayEnterprise\RetailOrderManagement\Payload\order\Detail\IOrderDetailRequest;
use eBayEnterprise\RetailOrderManagement\Payload\order\Detail\IOrderDetailResponse;
use eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi;

class EbayEnterprise_Order_Helper_Factory
{
	/**
	 * Get a new ebayenterprise_order/search_build_request instance.
	 *
	 * @param  IBidirectionalApi
	 * @param  string
	 * @param  string | null
	 * @return EbayEnterprise_Order_Model_Search_Build_Request
	 */
	public function getNewSearchBuildRequest(IBidirectionalApi $api, $customerId, $orderId=null)
	{
		return Mage::getModel('ebayenterprise_order/search_build_request', [
			'api' => $api,
			'customer_id' => $customerId,
			'order_id' => $orderId,
		]);
	}

	/**
	 * Get a new ebayenterprise_order/search_send_request instance.
	 *
	 * @param  IBidirectionalApi
	 * @param  IOrderSummaryRequest
	 * @return EbayEnterprise_Order_Model_Search_Send_Request
	 */
	public function getNewSearchSendRequest(IBidirectionalApi $api, IOrderSummaryRequest $request)
	{
		return Mage::getModel('ebayenterprise_order/search_send_request', [
			'api' => $api,
			'request' => $request,
		]);
	}

	/**
	 * Get a new ebayenterprise_order/search_process_response instance.
	 *
	 * @param  IOrderSummaryResponse
	 * @return EbayEnterprise_Order_Model_Search_Process_Response
	 */
	public function getNewSearchProcessResponse(IOrderSummaryResponse $response)
	{
		return Mage::getModel('ebayenterprise_order/search_process_response', [
			'response' => $response,
		]);
	}

	/**
	 * Get a new Varien_Object instance.
	 *
	 * @param  array
	 * @return Varien_Object
	 * @codeCoverageIgnore
	 */
	public function getNewVarienObject(array $data)
	{
		return new Varien_Object($data);
	}

	/**
	 * Get a new EbayEnterprise_Order_Model_Search_Process_Response_ICollection instance.
	 *
	 * @return EbayEnterprise_Order_Model_Search_Process_Response_ICollection
	 */
	public function getNewSearchProcessResponseCollection()
	{
		return Mage::getModel('ebayenterprise_order/search_process_response_collection');
	}

	/**
	 * Get a new EbayEnterprise_Order_Model_Search instance.
	 *
	 * @param  string
	 * @return EbayEnterprise_Order_Model_Search_Process_Response_ICollection
	 */
	public function getNewRomOrderSearch($customerId)
	{
		return Mage::getModel('ebayenterprise_order/search', ['customer_id' => $customerId]);
	}

	/**
	 * Get a customer session instance.
	 *
	 * @return Mage_Customer_Model_Customer
	 */
	public function getCustomerSession()
	{
		return Mage::getSingleton('customer/session');
	}

	/**
	 * Get a customer object for the current customer via the customer session.
	 *
	 * @return Mage_Customer_Model_Customer
	 */
	public function getCurrentCustomer()
	{
		return $this->getCustomerSession()->getCustomer();
	}

	/**
	 * Get a new ebayenterprise_order/cancel_build_request instance.
	 *
	 * @param  IBidirectionalApi
	 * @param  Mage_Sales_Model_Order
	 * @return EbayEnterprise_Order_Model_Cancel_Build_Request
	 */
	public function getNewCancelBuildRequest(IBidirectionalApi $api, Mage_Sales_Model_Order $order)
	{
		return Mage::getModel('ebayenterprise_order/cancel_build_request', [
			'api' => $api,
			'order' => $order,
		]);
	}

	/**
	 * Get a new ebayenterprise_order/cancel_send_request instance.
	 *
	 * @param  IBidirectionalApi
	 * @param  IOrderCancelRequest
	 * @return EbayEnterprise_Order_Model_Cancel_Send_Request
	 */
	public function getNewCancelSendRequest(IBidirectionalApi $api, IOrderCancelRequest $request)
	{
		return Mage::getModel('ebayenterprise_order/cancel_send_request', [
			'api' => $api,
			'request' => $request,
		]);
	}

	/**
	 * Get a new ebayenterprise_order/cancel_process_response instance.
	 *
	 * @param  IOrderCancelResponse
	 * @param  Mage_Sales_Model_Order
	 * @return EbayEnterprise_Order_Model_Cancel_Process_Response
	 */
	public function getNewCancelProcessResponse(IOrderCancelResponse $response, Mage_Sales_Model_Order $order)
	{
		return Mage::getModel('ebayenterprise_order/cancel_process_response', [
			'order' => $order,
			'response' => $response,
		]);
	}

	/**
	 * Get a new ebayenterprise_order/detail_build_request instance.
	 *
	 * @param  IBidirectionalApi
	 * @param  string
	 * @return EbayEnterprise_Order_Model_Detail_Build_Request
	 */
	public function getNewDetailBuildRequest(IBidirectionalApi $api, $orderId)
	{
		return Mage::getModel('ebayenterprise_order/detail_build_request', [
			'api' => $api,
			'order_id' => $orderId,
		]);
	}

	/**
	 * Get a new ebayenterprise_order/detail_send_request instance.
	 *
	 * @param  IBidirectionalApi
	 * @param  IOrderDetailRequest
	 * @return EbayEnterprise_Order_Model_Detail_Send_Request
	 */
	public function getNewDetailSendRequest(IBidirectionalApi $api, IOrderDetailRequest $request)
	{
		return Mage::getModel('ebayenterprise_order/detail_send_request', [
			'api' => $api,
			'request' => $request,
		]);
	}

	/**
	 * Get a new ebayenterprise_order/detail_process_response instance.
	 *
	 * @param  IOrderDetailResponse
	 * @return EbayEnterprise_Order_Model_Detail_Process_Response
	 */
	public function getNewDetailProcessResponse(IOrderDetailResponse $response)
	{
		return Mage::getModel('ebayenterprise_order/detail_process_response', [
			'response' => $response,
		]);
	}

	/**
	 * Get a new ebayenterprise_order/detail_process_response_address instance.
	 *
	 * @param  array
	 * @param  EbayEnterprise_Order_Model_Detail_Process_IResponse
	 * @return EbayEnterprise_Order_Model_Detail_Process_Response_Address
	 */
	public function getNewDetailProcessResponseAddress(array $data, EbayEnterprise_Order_Model_Detail_Process_IResponse $order)
	{
		return Mage::getModel('ebayenterprise_order/detail_process_response_address', $data)->setOrder($order);
	}

	/**
	 * Get a new ebayenterprise_order/detail_process_response_item instance.
	 *
	 * @param  array
	 * @param  EbayEnterprise_Order_Model_Detail_Process_IResponse
	 * @return EbayEnterprise_Order_Model_Detail_Process_Response_Item
	 */
	public function getNewDetailProcessResponseItem(array $data, EbayEnterprise_Order_Model_Detail_Process_IResponse $order)
	{
		return Mage::getModel('ebayenterprise_order/detail_process_response_item', $data)->setOrder($order);
	}

	/**
	 * Get a new ebayenterprise_order/detail_process_response_payment instance.
	 *
	 * @param  array
	 * @return EbayEnterprise_Order_Model_Detail_Process_Response_Payment
	 */
	public function getNewDetailProcessResponsePayment(array $data)
	{
		return Mage::getModel('ebayenterprise_order/detail_process_response_payment', $data);
	}

	/**
	 * Get a new ebayenterprise_order/detail_process_response_shipment instance.
	 *
	 * @param  array
	 * @return EbayEnterprise_Order_Model_Detail_Process_Response_Shipment
	 */
	public function getNewDetailProcessResponseShipment(array $data)
	{
		return Mage::getModel('ebayenterprise_order/detail_process_response_shipment', $data);
	}

	/**
	 * Get a new ebayenterprise_order/detail_process_response_shipment instance.
	 *
	 * @param  array
	 * @return EbayEnterprise_Order_Model_Detail_Process_Response_Shipgroup
	 */
	public function getNewDetailProcessResponseShipGroup(array $data)
	{
		return Mage::getModel('ebayenterprise_order/detail_process_response_shipgroup', $data);
	}

	/**
	 * Get a new ebayenterprise_order/detail instance.
	 *
	 * @param  string
	 * @return EbayEnterprise_Order_Model_Detail
	 */
	public function getNewRomOrderDetailModel($orderId)
	{
		return Mage::getModel('ebayenterprise_order/detail', ['order_id' => $orderId]);
	}

	/**
	 * Get a singleton core/session object.
	 *
	 * @return Mage_Core_Model_Session
	 */
	public function getCoreSessionModel()
	{
		return Mage::getSingleton('core/session');
	}

	/**
	 * Get a singleton adminhtml/session_quote object.
	 *
	 * @return Mage_Adminhtml_Model_Session_Quote
	 */
	public function getAdminQuoteSessionModel()
	{
		return Mage::getSingleton('adminhtml/session_quote');
	}
}
