# Change Log
All notable changes to this project will be documented in this file.

## [Unreleased][unreleased]
### Added
- Order Price Adjustment event

### Removed
- Unnecessary default configuration values from module etc/config.xml files.

### Fixed
- Ensure trailing whitespace is stripped before saving SFTP Remote Host configuration.
- Customers should only see one error message when trying to cart an understocked item.
- Gift Card from previous order applied when current Gift Card fails.

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
- Ensure trailing whitespace is stripped off before saving SFTP Remote Host configuration in the backend
- Gift Card from previous order applied when current Gift Card fails

[unreleased]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.4.0-alpha-10...HEAD
[1.4.0-alpha-10]: https://github.com/eBayEnterprise/magento-retail-order-management/compare/1.4.0-alpha-9...1.4.0-alpha-10
