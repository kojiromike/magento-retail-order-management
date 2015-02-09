# Change Log
All notable changes to this project will be documented in this file.

## [1.2.9] - 2015-02-09
### Fixed
- Correctly handle when address validation returns only a single suggestion

## [1.2.8] - 2015-02-05
### Fixed
- Order details page was not displaying country, state or postal code

## [1.2.7] - 2015-01-30
### Fixed
- Magento no longer displays erroneous success message with SUPEE-3345 patch applied. For real this time.

## [1.2.6] - 2015-01-29

### Fixed
- Magento no longer displays erroneous success message with SUPEE-3345 patch applied.

## [1.2.5] - 2015-01-23

### Fixed
- Magento admin can place $0 orders regardless of configured payment types.

## [1.2.4] - 2015-01-07

### Fixed
- Customers should only see one error message when trying to cart an understocked item.

## [1.2.3] - 2014-12-04
### Fixed
- Shipping method not appearing on transactional e-mails

## [1.2.2] - 2014-10-15
### Fixed
- OrderCreateRequest(s) are missing ReservationId element and corresponding Reservation Allocation value

## [1.2.1] - 2014-09-30
### Fixed
- PayPal AmountAuthorized is 0
- Correct rollback allocation message

## [1.2.0] - 2014-09-26

## [1.2.0-rc-11] - 2014-09-22
### Fixed
- 41st Parameter JavaScriptData is empty using PayPal Express Checkout

## [1.2.0-rc-10] - 2014-09-19
### Fixed
- TaxClass is not submitted in TDF Request
- PayPal requests should send the OrderId, not QuoteId

## [1.2.0-rc-9] - 2014-09-17
### Fixed
- PayPal Express is not working when shopping cart price rules result in rounding a product's price

## [1.2.0-rc-8] - 2014-09-15
### Fixed
- Address Validation Suggestions dependent upon Mage_GiftMessage
- PayPal PAYMENTACTION is set using base Magento configuration

## [1.2.0-rc-7] - 2014-09-11
### Fixed
- Do not log index rebuilds
- PayPal Express is not working with shopping cart price rules
- PaymentRequestId is inconsistent between service calls
- TDF service is not called when using PayPal Express

## [1.2.0-rc-6] - 2014-09-04
### Fixed
- Do not throw exception for getPalDetails non-ROM API call
- PayPal authorization response code is not set
- Send corrected PayPal UniqueId in OrderCreateRequest

## [1.2.0-rc-5] - 2014-09-02
### Fixed
- `\EbayEnterprise_Eb2cCore_Helper_Feed::getMessageDate` can return non-object
- PayPalPayerInfo is not sent in the Context node when using PayPal as the payment method

## [1.2.0-rc-4] - 2014-08-28
### Fixed
- OrderCreate PaymentSessionId should use the OrderId
- Estimated Delivery Data MessageType should be "DeliveryDate" not "None"

## [1.2.0-rc-3] - 2014-08-25
### Fixed
- Eb2cFraud JS Collector Breaks Checkout Submit
- Gracefully handle "New" orders with no `eb2c_order_create_request_attribute`
- OrderCreate should submit empty tags in the HTTPAcceptData node instead of the string "null"
- Product export is failing XSD validation

## [1.2.0-rc-2] - 2014-07-31
### Removed
- Order status feed vestiges

### Fixed
- QuantityRequest seems to duplicate line items of parent/child product
- Remove Hierarchy Attributes from tst02 and import environment test data
- TaxCode was not populated for the Configurable (Parent) item

## [1.2.0-rc-1] - 2014-07-11
### Fixed
- Order history and order detail pulling from local, not eb2c
- Gift card PIN is not submitted with the order
- Product import not importing color descriptions

[1.2.9]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.2.8...1.2.9
[1.2.8]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.2.7...1.2.8
[1.2.7]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.2.6...1.2.7
[1.2.6]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.2.5...1.2.6
[1.2.5]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.2.4...1.2.5
[1.2.4]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.2.3...1.2.4
[1.2.3]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.2.2...1.2.3
[1.2.2]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.2.1...1.2.2
[1.2.1]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.2.0...1.2.1
[1.2.0]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.2.0-rc-11...1.2.0
[1.2.0-rc-11]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.2.0-rc-10...1.2.0-rc-11
[1.2.0-rc-10]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.2.0-rc-9...1.2.0-rc-10
[1.2.0-rc-9]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.2.0-rc-8...1.2.0-rc-9
[1.2.0-rc-8]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.2.0-rc-7...1.2.0-rc-8
[1.2.0-rc-7]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.2.0-rc-6...1.2.0-rc-7
[1.2.0-rc-6]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.2.0-rc-5...1.2.0-rc-6
[1.2.0-rc-5]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.2.0-rc-4...1.2.0-rc-5
[1.2.0-rc-4]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.2.0-rc-3...1.2.0-rc-4
[1.2.0-rc-3]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.2.0-rc-2...1.2.0-rc-3
[1.2.0-rc-2]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.2.0-rc-1...1.2.0-rc-2
[1.2.0-rc-1]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.2.0-beta-17...1.2.0-rc-1
