# Change Log
All notable changes to this project will be documented in this file.

## [1.6.0-beta-22] - 2015-09-08
### Fixed
- Fix Bug with Swatch Issue Color Options

## [1.6.0-beta-21] - 2015-09-08
### Fixed
- Fix Swatch Issue for Configurable Product Associated Child Product Options

## [1.6.0-beta-20] - 2015-09-07
### Fixed
- Avoid Re-auth Due to Tax Recalculation

## [1.6.0-beta-19] - 2015-09-06
### Fixed
- Send Correct Region Information in Tax Requests

## [1.6.0-beta-18] - 2015-09-04
### Fixed
- Updating Product Options with New Option Causes Color Swatches to Show as Text

## [1.6.0-beta-17] - 2015-09-04
### Fixed
- Missing dashboard rep id in order create request for admin created orders

## [1.6.0-beta-16] - 2015-09-04
### Fixed
- Error Confirmation and Ack Feeds Reverse Source and Destination in Headers

## [1.6.0-beta-15] - 2015-09-03
### Changed
- Ability to Stop Indexing During Feed Load

## [1.6.0-beta-14] - 2015-09-03
### Fixed
- EDD Data not being sent in ROM Order Create Request Payload

## [1.6.0-beta-13] - 2015-09-03
### Fixed
- Use setTaxes on tax container

## [1.6.0-beta-12] - 2015-09-02
### Changed
- Support MPERF-7650 Patched Magento and Unpatched Magento

## [1.6.0-beta-11] - 2015-09-01
### Fixed
- Feed Import Can Delete Option Information

## [1.6.0-beta-10] - 2015-08-31
### Fixed
- Tax Code for merchandise prices missing from tax request

## [1.6.0-beta-9] - 2015-08-28
### Fix
- EDD not showing during order review step in checkout when used with custom theme

## [1.6.0-beta-8] - 2015-08-26
### Fix
- Giftcard order crash customer order detail and allocation error for out of stock backorderable item.
- MainDivision not showing for address validation suggested address in checkout.

## [1.6.0-beta-7] - 2015-08-25
### Fix
- "Invalid argument" warning on order submit

## [1.6.0-beta-6] - 2015-08-24
### Fix
- display bundle item in customer order detail page in a single line instead of many lines.

## [1.6.0-beta-5] - 2015-08-20
### Added
- Multi-address shipping now supports PayPal payments.

## [1.6.0-beta-4] - 2015-08-19
### Changed
- Make the color/size attribute mapping public, so custom mappings can be written for attribute options.

## [1.6.0-beta-3] - 2015-08-13
### Fixed
- Supply the correct estimated delivery date for configurable products
- Supply the correct tracking number in order details
- Fix CSR Login Redirecting to 404 Page
- Support for multiple shipping address orders
- Fix errors in Address Validation for Foreign Addresses
- Removed dependency on Enterprise GiftCardAccount

## [1.6.0-beta-2] - 2015-07-30
### Fixed
- Inventory Service Failure Blocks Checkout

## [1.6.0-beta-1] - 2015-07-16
### Added
- Support for ordering bundle products
- Product import for size attributes

### Changed
- The gift card module no longer needs manual configuration of bin ranges. Any existent bin range configurations will be ignored.
- `ExtendedAttributes/AllowGiftMessage` mapped to Allow Gift Message (Gift Options) instead of Allow Message (Gift Card Information)

### Fixed
- Error handling shipping information for virtual products with credit card payments
- BIN is not found in range when multiple ranges exist for the same tender type
- Local inventory is checked instead of the ROM inventory service
- Missing order information on order detail
- Order Create Request does not include a product's color or size
- Registered user checkout success page does not link to the rom order detail correctly
- Log masked gift card requests/responses
- "What's PayPal" popup is not populated
- Estimated Delivery Date is not sent in the OCR if there's no template configured
- Credit Card Auth Response Log Message in `/Request/` Log Key

## [1.6.0-alpha-6] - 2015-07-02
### Added
- Support for bundle and group product types for inventory allocation operations
- Support for estimated delivery date for backorderable products that are in stock
- Support importing custom product attributes without explicit import map
- Support sending zero-padding Customer Id in OCR and registered customer Account Pages in order to import customers from other web-stores

### Changed
- Inventory Allocation operations now use the ROM SDK

### Fixed
- Estimated Delivery Date does not display for both configurable and gift-card physical products in checkout review page
- Fatal Error found when cancelling an order that was not originally created within the current webstore
- Fatal error during checkout with discount
- Order Create Request order items are missing ShippingMethod
- Email and Last Name Fields are case sensitive on Orders and Returns Screen
- PayPal SetExpress totals do not add up
- Address validation fatal error when receiving response code "P"

## [1.6.0-alpha-5] - 2015-06-18
### Added
- Support for bundle and group product types for inventory details and quantity operations
- Support for client-side encryption for multishipping orders when eBay Enterprise Credit Card payment method is used
- Product attributes, import maps and export maps for Catalog Class and Item Status
- Display of Estimated Delivery Date in onepage and multishipping checkout

### Changed
- Inventory details and quantity operations now use the ROM SDK
- Visibility import map uses a custom attribute instead of CatalogClass

### Fixed
- Shipping discounts are missing from tax and order create requests
- Log messages may contain sensitive data
- Unable to search by Zip in guest order lookup
- Visiting order cancel URL directly results in unhandled error

## [1.6.0-alpha-4] - 2015-06-04
### Added
- Support for multishipping checkout to generate a single order create request
- Support for viewing order details and shipments with multiple shipping addresses in one order

### Changed
- Installation and Configuration Guide
- Product Import Guide

### Fixed
- Order create request sends duplicate skus for configurable products
- Order create request includes a shipping amount for all items

## [1.6.0-alpha-3] - 2015-05-21
### Added
- Support for ROM tax, duty and fee service when using multishipping checkout
- Support for ROM web order cancel and reason
- Documentation to extend the order create request
- Magento Enterprise Edition 1.14.2 support

### Changed
- Out of stock and limited stock handling match Magento handling
- Order summary has been refactored to use the [RetailOrderManagement-SDK](https://github.com/eBayEnterprise/RetailOrderManagement-SDK)

### Removed
- Vestigial event observer from eb2cOrder

### Fixed
- Order create merchandise amount remainder calculation wrong and unnecessary

## [1.6.0-alpha-2] - 2015-05-07
### Fixed
- Store language codes are not being properly validated in the admin

## [1.6.0-alpha-1] - 2015-04-24
### Fixed
- Invalid tax response does not cause Order Create Request tax header to be set properly
- Error when `ItemDesc` value includes quotation marks

## [1.5.0-beta-1] - 2015-04-09
### Changed
- Handle unacknowledged feeds with a CRIT log instead of resending the feed file

### Fixed
- PayPal module is not receiving the address status from the api
- ProductImageExport generates new files even when no images/products have changed
- Logger Context Helper Undefined Index Error

## [1.5.0-alpha-6] - 2015-03-26
### Added
- Configuration mapping for customer gender
- Exposed events to allow modification or addition of information to the Order Create request

### Changed
- Order create has been refactored to use the [RetailOrderManagement-SDK](https://github.com/eBayEnterprise/RetailOrderManagement-SDK)
- Refactor modules to use events to inject data into the order create request

### Removed
- Order custom attribute mappings

## [1.5.0-alpha-5] - 2015-03-12
### Changed
- Log statements now utilize [eBay Enterprise Magento Logger](https://github.com/eBayEnterprise/magento-log) 2.0
- Log statements now contain meta information

## [1.5.0-alpha-4] - 2015-02-26
### Changed
- Use the Retail Order Management SDK for address validation service requests

### Removed
- Last vestiges of admin suppression

### Fixed
- Guest order lookup gives "Order not found" error even when an order is found
- PayPal Express Checkout fails when using a gift card that covers the order subtotal, but not the eventual order grand total
- Order details page is not displaying the correct item quantity
- Order details page is not displaying promotional discounts
- Blank page upon submitting an order using PayPal payment method

## [1.5.0-alpha-3] - 2015-02-12
### Changed
 - Change "Invalid" to "Incomplete" for products not yet fully imported

### Fixed
- Sprintf-format specifiers not replaced in log messages
- Order details page was not displaying country, state or postal code
- No error message when a product with no inventory is removed from cart

## [1.5.0-alpha-2] - 2015-01-29
### Changed
- Undo prepending catalog id to the custom product attribute `style_id` when importing products

### Fixed
- Unnecessary TDF requests when PayPal Express Checkout used from cart or product page
- No inventory check when adding an item to the cart that was just ordered
- Magento admin can place $0 orders regardless of configured payment types
- Unable to start PayPal Express Checkout
- Test API (Web Services) fails if Address Validation->Maximum Suggestions is not configured

## [1.5.0-alpha-1] - 2015-01-15
### Added
- InStore module to create and import in-store pick-up, ship-to-store and ship-from-store product attribute
- Add in-store pick-up, ship-to-store  and ship-from-store attributes to product export feed

## [1.4.0-rc-1] - 2015-01-15
### Added
- Order Price Adjustment event
- Order Gift Certificate event
- Log warning if API call to allocate inventory fails

### Changed
- Use version "~1.0" of eBayEnterprise/RetailOrderManagement-SDK

### Removed
- Unnecessary default configuration values from module etc/config.xml files

### Fixed
- Multiple error messages in the shopping cart when inventory is out-of-stock
- Gift Card from previous session applied when current Gift Card fails
- File Transfer fails from cron, while SFTP test button succeeds
- Number formats should not be locale-specific
- Address validation shows suggestions after valid address is saved
- Unnecessary override of Cart.php in Eb2cInventory
- Checkout fails when multiple credit card payment methods are enabled
- The import product collection is being saved when there are no changes
- Cron scheduling samples for ProductExport and ProductImageExport modules

## [1.4.0-alpha-10] - 2015-01-05
### Added
- PayPal configuration for Image URLs
- PayPal admin configuration to "Transfer Line Items"
- Order Accepted event
- Order Cancelled event
- Order Confirmed event
- Order Credit Issued event
- Order Return in Transit event

### Removed
- Order Status mapping between ROM Order Statuses and Magento Order Status table
- ROM Order Status importation into the Magento Order Status table

### Fixed
- Orders using gift cards are submitted with PrepaidCreditCard payment node that fails in OMS

## [1.4.0-alpha-9] - 2014-12-18
### Added
- Order Credit Issued event
- Order Shipped event
- Order Backorder event

### Changed
- Order history and order details no longer require the order to exist locally in Magento
- PayPal refactored as a payment method

## [1.4.0-alpha-8] - 2014-12-04
### Added
- Order Rejected event
- AMQP Support
- Shell script to reset the export cutoff date

### Changed
- Active support for Magento Enterprise Edition 1.14.1
- Core resource changed from `eb2cproduct` to `ebayenterprise_catalog`
- Documentation significantly updated

### Removed
- Event handler to explicitly reindex products after feed importation

### Fixed
- Shipping method not appearing on transactional e-mails

## [1.4.0-alpha-7] - 2014-11-20
### Added
- Credit card client-side encryption
- New credit card tenders may be added via configuration

## [1.4.0-alpha-6] - 2014-11-06
### Added
- Customers may pay with multiple gift cards on a single order

### Changed
- Gift cards refactored

### Fixed
- Gift card payment information is not submitted when combined with additional payment methods
- Out-of-range gift card is applied to cart when that BIN already exists as a gift card in Magento
- Gift card with positive balance is not updated when BIN already exists as a gift card in Magento with zero balance
- If the gift card module is not configured, it should block gift card redemption
- Money Order is not available when payments are turned off

## [1.4.0-alpha-5] - 2014-10-23
### Changed
- Product refactored into Catalog, Product Import and Product Export
- Credit card refactored as a payment method

### Removed
- Payment Bridge support

## Fixed
- OrderCreateRequest(s) are missing ReservationId element and corresponding Reservation Allocation value

## [1.4.0-alpha-4] - 2014-10-09
### Changed
- Inventory attribute setup to a data setup script
- PHP support from 5.3 to 5.4
- XSDs and export headers from NGP1.0.0 to NGP1.1.0
- Address Validation refactor
- Product attribute creation from configuration to setup scripts

### Fixed
- PayPal AmountAuthorized is 0
- Correct rollback allocation message
- Address admin config using wrong config path
- Purchasing a Virtual Gift Card produces an error
- Only a single price feed is exported for more than one channel/store
- 41st Parameter JavaScriptData is empty using PayPal Express Checkout
- TaxClass is not submitted in TDF Request
- PayPal requests should send the OrderId, not QuoteId
- Sum of multiple gift wrap charges are not being submitted correctly
- `EbayEnterprise_Eb2cProduct_Model_Feed` does not set `_log` property
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

## [1.4.0-alpha-3] - 2014-08-19
### Added
- Gift card balance check from "My Account"

### Changed
- Order retry to look for orders with status of "unsubmitted" instead of orders with a state of "new"
- Admin configuration labels and help text

### Fixed
- Product export is failing XSD validation
- Stored Value Cards have USD hard coded currency code
- Gracefully handle "New" orders with no `eb2c_order_create_request_attribute`

## [1.4.0-alpha-2] - 2014-07-31
### Removed
- Order status feed vestiges

### Fixed
- Removed Order Status code still being called as observer

## [1.4.0-alpha-1] - 2014-07-17
### Fixed
- QuantityRequest seems to duplicate line items of parent/child product
- TaxCode was not populated for the Configurable (Parent) item
- Order history and order detail pulling from local, not eb2c
- Unable to checkout with PayPal when Gift Wrapping in Order
- Gift card PIN is not submitted with the order
- Product import not importing color descriptions

[1.6.0-beta-22]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.0-beta-21...1.6.0-beta-22
[1.6.0-beta-21]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.0-beta-20...1.6.0-beta-21
[1.6.0-beta-20]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.0-beta-19...1.6.0-beta-20
[1.6.0-beta-19]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.0-beta-18...1.6.0-beta-19
[1.6.0-beta-18]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.0-beta-17...1.6.0-beta-18
[1.6.0-beta-17]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.0-beta-16...1.6.0-beta-17
[1.6.0-beta-16]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.0-beta-15...1.6.0-beta-16
[1.6.0-beta-15]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.0-beta-14...1.6.0-beta-15
[1.6.0-beta-14]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.0-beta-13...1.6.0-beta-14
[1.6.0-beta-13]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.0-beta-12...1.6.0-beta-13
[1.6.0-beta-12]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.0-beta-11...1.6.0-beta-12
[1.6.0-beta-11]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.0-beta-10...1.6.0-beta-11
[1.6.0-beta-10]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.0-beta-9...1.6.0-beta-10
[1.6.0-beta-9]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.0-beta-8...1.6.0-beta-9
[1.6.0-beta-8]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.0-beta-7...1.6.0-beta-8
[1.6.0-beta-7]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.0-beta-6...1.6.0-beta-7
[1.6.0-beta-6]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.0-beta-5...1.6.0-beta-6
[1.6.0-beta-5]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.0-beta-4...1.6.0-beta-5
[1.6.0-beta-4]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.0-beta-3...1.6.0-beta-4
[1.6.0-beta-3]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.0-beta-2...1.6.0-beta-3
[1.6.0-beta-2]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.0-beta-1...1.6.0-beta-2
[1.6.0-beta-1]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.0-alpha-6...1.6.0-beta-1
[1.6.0-alpha-6]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.0-alpha-5...1.6.0-alpha-6
[1.6.0-alpha-5]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.0-alpha-4...1.6.0-alpha-5
[1.6.0-alpha-4]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.0-alpha-3...1.6.0-alpha-4
[1.6.0-alpha-3]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.0-alpha-2...1.6.0-alpha-3
[1.6.0-alpha-2]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.6.0-alpha-1...1.6.0-alpha-2
[1.6.0-alpha-1]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.5.0-beta-1...1.6.0-alpha-1
[1.5.0-beta-1]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.5.0-alpha-6...1.5.0-beta-1
[1.5.0-alpha-6]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.5.0-alpha-5...1.5.0-alpha-6
[1.5.0-alpha-6]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.5.0-alpha-5...1.5.0-alpha-6
[1.5.0-alpha-5]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.5.0-alpha-4...1.5.0-alpha-5
[1.5.0-alpha-4]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.5.0-alpha-3...1.5.0-alpha-4
[1.5.0-alpha-3]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.5.0-alpha-2...1.5.0-alpha-3
[1.5.0-alpha-2]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.5.0-alpha-1...1.5.0-alpha-2
[1.5.0-alpha-1]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.4.0-rc-1...1.5.0-alpha-1
[1.4.0-rc-1]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.4.0-alpha-10...1.4.0-rc-1
[1.4.0-alpha-10]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.4.0-alpha-9...1.4.0-alpha-10
[1.4.0-alpha-9]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.4.0-alpha-8...1.4.0-alpha-9
[1.4.0-alpha-8]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.4.0-alpha-7...1.4.0-alpha-8
[1.4.0-alpha-7]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.4.0-alpha-6...1.4.0-alpha-7
[1.4.0-alpha-6]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.4.0-alpha-5...1.4.0-alpha-6
[1.4.0-alpha-5]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.4.0-alpha-4...1.4.0-alpha-5
[1.4.0-alpha-4]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.4.0-alpha-3...1.4.0-alpha-4
[1.4.0-alpha-3]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.4.0-alpha-2...1.4.0-alpha-3
[1.4.0-alpha-2]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.4.0-alpha-1...1.4.0-alpha-2
[1.4.0-alpha-1]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.3.0...1.4.0-alpha-1
