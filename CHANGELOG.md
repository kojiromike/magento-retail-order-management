# Change Log
All notable changes to this project will be documented in this file.

## [1.3.4] - 2015-02-12
### Fixed
- Correctly handle when address validation returns only a single suggestion
- Order details page was not displaying country, state or postal code

## [1.3.3] - 2015-01-29
### Fixed
- Customers should only see one error message when trying to cart an understocked item
- Magento admin can place $0 orders regardless of configured payment types

## [1.3.2] - 2014-12-04
### Fixed
- Shipping method not appearing on transactional e-mails

## [1.3.1] - 2014-10-24
### Fixed
- OrderCreateRequest(s) are missing ReservationId element and corresponding Reservation Allocation value

## [1.3.0] - 2014-10-01
### Added
- Gift wrap and messaging support
- Promotion detail support to enable better reporting on promotions
- SFTP connection test button
- Web service connection test button

### Changed
- Update XSDs and export headers from NGP1.0.0 to NGP1.1.0

### Fixed
- PayPal AmountAuthorized is 0
- Correct rollback allocation message
- 41st Parameter JavaScriptData is empty using PayPal Express Checkout
- TaxClass is not submitted in TDF Request
- PayPal requests should send the OrderId, not QuoteId
- Sum of multiple gift wrap charges are not being submitted correctly
- Address Validation Suggestions dependent upon Mage_GiftMessage
- PayPal PAYMENTACTION is set using base Magento configuration
- Do not log index rebuilds
- PayPal Express is not working with shopping cart price rules
- PaymentRequestId is inconsistent between service calls
- TDF service is not called when using PayPal Express
- Missing merchandise amount in OrderCreateRequest
- Do not throw exception for getPalDetails non-ROM API call
- PayPal authorization response code is not set
- Send corrected PayPal UniqueId in OrderCreateRequest
- GiftCardTenderCode and MaxGCAmount fields are not exported for gift card products
- `\EbayEnterprise_Eb2cCore_Helper_Feed::getMessageDate` can return non-object
- PayPalPayerInfo is not sent in the Context node when using PayPal as the payment method
- OrderCreate PaymentSessionId should use the OrderId
- Estimated Delivery Data MessageType should be "DeliveryDate" not "None"
- Eb2cFraud JS Collector Breaks Checkout Submit
- OrderCreate should submit empty tags in the HTTPAcceptData node instead of the string "null"

## [1.3.0-beta-3] - 2014-08-19
### Fixed
- Product export is failing XSD validation
- Gracefully handle "New" orders with no `eb2c_order_create_request_attribute`
- Removed Order Status code still being called as observer

## [1.3.0-beta-2] - 2014-07-22
### Removed
- Order status feed vestiges

## [1.3.0-beta-1] - 2014-07-17
- QuantityRequest seems to duplicate line items of parent/child product
- TaxCode was not populated for the Configurable (Parent) item
- Order history and order detail pulling from local, not eb2c
- Unable to checkout with PayPal when Gift Wrapping in Order
- Gift card PIN is not submitted with the order
- Product import not importing color descriptions

[1.3.4]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.3.3...1.3.4
[1.3.3]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.3.2...1.3.3
[1.3.2]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.3.1...1.3.2
[1.3.1]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.3.0...1.3.1
[1.3.0]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.3.0...1.3.0-beta-3
[1.3.0-beta-3]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.3.0-beta-3...1.3.0-beta-2
[1.3.0-beta-2]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.3.0-beta-2...1.3.0-beta-1
[1.3.0-beta-1]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.3.0-beta-1...1.3.0-alpha-14
