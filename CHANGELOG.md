# Changelog

## [1.24.3]

**Improvements:**
* Item-level discounts are now applied before tax calculation, ensuring tax is calculated on the discounted price.
* Added 6 new E2E tests covering item-level discount scenarios.

**Bugfixes:**
* Fixed configurable product prices showing child discounts as the regular price instead of the sale price.

**Minimum requirements:**
* Magento 2.3.x & 2.4.x
* PHP 7.4 to 8.5

## [1.24.2]

**Bugfixes:**
* Fixed `item_id`/`id` column mismatch causing constraint violations when observers trigger item saves.
* Fixed bundle product pricing in feed by using indexed prices instead of `getBaseAmount()`.
* Fixed customer being assigned to wrong store view on order import.
* Fixed credit memo creation being blocked for orders placed via the Channable payment method.
* Added early return guard for missing product ID in item add to prevent exceptions.

**Improvements:**
* Added PHP 8.5 / Magento 2.4.9 to setup-upgrade CI matrix.

**Minimum requirements:**
* Magento 2.3.x & 2.4.x
* PHP 7.4 to 8.5

## [1.24.1]

**Improvements:**
* Added `: int` return types to Console\Command execute() methods (ItemUpdate, OrderSimulate, Selftest) for Magento 2.4.9 compatibility.
* Added a PHP 8.5 linting job.
* Added caching for Playwright browsers in the E2E workflow.

**Bugfixes:**
* Fixed missing currency values for negative prices in feed output.
* Fixed region code mismatches in Channable order imports.

**Minimum requirements:**
* Magento 2.3.x & 2.4.x
* PHP 7.4 to 8.5

## [1.24.0]

**Features:**
* Added multi-currency support for order import (including currency conversion).
* Added end-to-end tests for order import, webhook endpoints, and tax scenarios (see E2E-TESTS.md).

**Improvements:**
* Excluded external video thumbnails from the feed image gallery.
* Added a store filter to Channable order deduplication so identical channel order IDs can be processed per store.

**Bugfixes:**
* Fixed shipping tax calculation by using the product tax class ID.
* Fixed cross-border tax recalculation when Magento cross-border trade is enabled.
* Fixed Increment ID generation so hyphens in channel order IDs are preserved.
* Fixed return matching by falling back to the item ID when GTIN is missing.
* Fixed an undefined array key error when using non-currency actions on rendered price extra fields.
* Fixed Using Min/Max price with base currency #264 (thnx @MatthijsBreed)

**Minimum requirements:**
* Magento 2.3.x & 2.4.x
* PHP 7.4 to 8.4

## [1.23.0]

**Features:**
* Add exclude items from update feature with inline grid editing
* Add order memo update API endpoint for updating memo via channable_id

**Improvements:**
* Add currency conversion support for custom price attributes #258
* Improve phone number fallback handling in order addresses (customer -> address)
* Fix for upcoming PHP 8.5 deprecation, casting to boolean should use 'bool' type #256 (thnx @hostep)
* Returns: Add fallback to match product by entity ID when GTIN match fails (Amazon)

**Bugfixes:**
* Set payment method code to quote so it can be used in sales rules #259
* use max/min price from product price object #260 (thnx @sanderjongsma)

**Minimum requirements:**
* Magento 2.3.x & 2.4.x
* PHP 7.4 to 8.4

## [1.22.3]

**Improvements:**
* Made getProductPrice() public to allow plugin extensions.

**Bugfixes:**
* Fixed fatal error when the Channable pickup location extension attribute is missing.

**Minimum requirements:**
* Magento 2.3.x & 2.4.x
* PHP 7.4 to 8.4

## [1.22.2]

**Bugfixes:**
* Fix AddressHandler sanitize function to accept int  (thnx @JustinElst)

**Minimum requirements:**
* Magento 2.3.x & 2.4.x
* PHP 7.4 to 8.4

## [1.22.1]

**Improvements:**
* Aligned AddressHandler sanitization with Magento validators.

**Bugfixes:**
* Fixed SalesRule ValidatorProcess observer.
* Ensured all ancestors and sub-categories are captured from category paths.

**Minimum requirements:**
* Magento 2.3.x & 2.4.x
* PHP 7.4 to 8.4

## [1.22.0]

**Features:**
* Option to auto process 'complete' and 'accepted' returns (auto creditmemo).

**Improvements:**
* Also support bundle qty for legacy inventory (non-MSI).
* Revised bundle qty calculation to use lowest available simple.

**Bugfixes:**
* Filtering on 'Channable - Returns' not working (#247).
* Check if there is VAT on business order item.
* Fixed bundleParentSimpleRelation must not be accessed before initialization.

**Minimum requirements:**
* Magento 2.3.x & 2.4.x
* PHP 7.4 to 8.4

## [1.21.2]

**Bugfixes:**
* Fix errors caused by product data being array in certain cases #245 (thnx @indykoning)

**Compatibility**
* Magento: 2.3.x & 2.4.x
* PHP: 7.4 to 8.4 supported

## [1.21.1]

**Improvements:**
* Fire magento events during import #236 (thnx @indykoning)

**Bugfixes:**
* Also load SKUs from bundle simples for inventory data collection
* Scope GetSkuFromGtin to store #242

**Compatibility**
* Magento: 2.3.x & 2.4.x
* PHP: 7.4 to 8.4 supported

## [1.21.0]

**Features:**
* Added support for PHP 8.4 and Magento 2.4.8
* Introduced support for Custom Options in the Product Feed
* Implemented advanced Shipping Method Mapping (marketplace ↔ Magento)
* Added Service Point ID to order information
* Support for 3rd party product types (eg giftcards)

**Improvements:**
* Improved stock calculation for bundled products (#210)
* Removed CSP headers for webhooks (thanks @JustinElst)

**Bugfixes:**
* Fixed inventory filter queries for stock and minimum sale quantity (#238)

**Compatibility**
* Magento: 2.3.x & 2.4.x
* PHP: 7.4 to 8.4 supported

## [1.20.0]

**Features:**
* Added 'updated_at' to product feed

**Improvements:**
* Move price logic to separate service class
* Transfer VatID to shipping address if company is the identical
* Add Channable discount total to the invoice
* Apply channel name to discount label

**Bugfixes:**
* Remove Product Wee Tax (FPT) after recalculate price #231
* Get SKU form GTIN when creating credit memo from grid #233
* Fix loading of parent categories
* Fix tier price calculation when using discount
* Fixed feed issue with 'use_default' values (thnx @VincentBean)

## [1.19.1]

**Bugfixes:**
* Fxed retry order missing some shipment data

## [1.19.0]

**Features:**
* Add urls to category output in product feed

**Improvements:**
* Add 'item_group_id' to exclude list
* Add Customer Tax Class Id to price calculation

## [1.18.3]

**Bugfixes:**
* Fix reservation qty's
* Check if business order has zero tax set before setting tax to zero
* Fix ACL rule for Log Stream
* Add discount info to invoice #227 (thnx @Skullsneeze)

## [1.18.2]

**Bugfixes:**
* Empty parentRelations fallback returns full product collection #222
* Fixed issue with empty inventory on item updates
* Simple products associated with 2 configurables are not exported #200
* Handle empty values for name in Channable order grid

## [1.18.1]

**Bugfixes:**
* Revert use indexed product_id column for order by due to issues on paging
* Skip creditmemo items that are not refunded

**Improvements:**
* Prefetch tier price for better performance

## [1.18.0]

**Features:**
* Added Include Inventory Sources to feed option #172

**Improvements:**
* Do not execute COUNT-query twice #123 (thnx @bertterheide)
* Use GTIN attribute to link returns

**Bugfixes:**
* Fixes issue with pricing #218
* Fixed issue with shipping price currency exchange for free shipment #219
* Refresh on Credit-memo page removes the Return block

## [1.17.0]

**Features:**
* Save returns meta data and add to returns grid
* Added option to set zero tax on business orders
* Added tier prices to output if selected in extra values

**Improvements:**
* Prefetch data in product feed for better performance (thnx @julian-van-drielen)
* Add "Not Visible Individually" products to feed when forced
* Move returns config menu option to bottom
* Added category id to category output
* Improved Return block on Credit-memo page

**Bugfixes:**
* Use referenceBlock instead of referenceContainer for order_totals (thnx @Bogardo)
* Also convert shipping costs if needed

## [1.16.0]

**Features:**
* Added new options for returns handeling
* Updated Admin UI
* Support EE Giftcards
* Added new options for returns

**Changes:**
* The feed purges the Category Name
* Use indexed product_id column for order by (thx @bertterheide)

## [1.15.1]

**Bugfixes:**
* Fixed con frequency config_path for item updates

**Minimum requirements:**
* Magento 2.3.3 & PHP 7.1

## [1.15.0]

**Features:**
* Show Zalando Article Number in order view

**Bugfixes:**
* When stock quantity is 1 order is not created and reporting error that product is out of stock #203
* Accept empty values for customer data in order postdata
* Guest customer should not be saved as `0` on quote (thnx @frank-bokdam)

**Changes:**
* Update CI flow to include Magento 2.4.6 and PHP 8.2

**Minimum requirements:**
* Magento 2.3.3 & PHP 7.1

## [1.14.2]

**Bugfixes:**
* Return int for console command
* Added price_type and fixed bundle price logics for feed
* Make sure discounts are always absolute

**Changes:**
* Change some methods to public to be accessible for plugins (thnx @sanderjongsma)
* Disable composer plugins when using CI

**Minimum requirements:**
* Magento 2.3.3 & PHP 7.1

## [1.14.1]

**Features:**
* Added channable:generate:token CLI to generate and refresh token
* Added price param for order simulate function to test correct final prices

**Bugfixes:**
* Add unmanaged stock check for parent product stock status (thnx @kaplansin)
* Fixed name validator
* Make sure `value` is not array when reformatting extra values #194

**Changes:**
* Use the configured default status for state 'processing' (thnx @markvds)

**Minimum requirements:**
* Magento 2.3.3 & PHP 7.1

## [1.14.0]

**Features:**
* Save new field pickup_location on order import
* Make Title Regex field required in config #160

**Bugfixes:**
* Fixes invalid dynamic bundle prices due floatval() with int comparison #189 (thnx @JonathanBBD)
* Force string return type for getStoreValue (thnx @umizoomi)
* Make sure prices of all shipping methods are set (thnx @markvds)
* Replace deprecated class in data patch

**Changes:**
* Use HTTP client interface instead of curl factory for item updates

**Minimum requirements:**
* Magento 2.3.3 & PHP 7.1

## [1.13.0]

**Features:**
* Added full flush option for items table
* Removed the 50 item limit for item updates
* Added message on grid if item update cron is disabled
* Added backorders true/false to product feed

**Bugfixes:**
* Fixed linkage of returns to orders
* Fixed issue with returns endpoint not using correct identity field
* Use final price for items in order simulator
* Fixed order email to customer is not send for Channable orders #185

**Minimum requirements:**
* Magento 2.3.3 & PHP 7.1

## [1.12.1]

**Bugfixes:**
* Fixed raises issue on empty frontend labels (thnx @wahidnory)
* Fix for bundle products price import (thnx @sanderjongsma)
* Fixed marketplace fee on invoice and creditmemo
* Added name validator parser for order import
* Performance improvement fix to feed generation (thnx @evs-xsarus)

**Minimum requirements:**
* Magento 2.3.3 & PHP 7.1

## [1.12.0]

**Features:**
* Added option to accept orders with bundle/grouped products
* Added check on required company address field #173
* Added environment variables #177

**Bugfixes:**
* Fix missing link to order item in sales_order_tax_item #133
* Empty translatable string causes error when collecting phrases #176
* Unnecessary, but also missing indexes in table definition #175
* Do not process discounts if value is zero
* Fixed priority mapping in getCustomShippingMethodLogic (thnx @markvds)

**Minimum requirements:**
* Magento 2.3.3 & PHP 7.1

## [1.11.0]

**Features:**
* Added multi currency support for product feed

**Bugfixes:**
* Fixed debug log not being fired

**Minimum requirements:**
* Magento 2.3.3 & PHP 7.1

## [1.10.2]

**Bugfixes:**
* Accept empty comment on return import #167
* Fixed issue with order simulation not setting StoreId on product load

**Minimum requirements:**
* Magento 2.3.3 & PHP 7.1

## [1.10.1]

**Bugfixes:**
* Implemented missing store emulation for admin order simulation
* Formatted correct return message of non existing return
* Fixed db_schema for `transaction_fee` fields #164
* Removed unused prototype requirement for buttons js #163
* Fixed PHP 8.1 compatibility on empty cron table when running self test

**Minimum requirements:**
* Magento 2.3.3 & PHP 7.1

## [1.10.0]

**Features:**
* Add currency switch param to product feed #136
* Add option to include both excl. and incl. tax prices
* Add option to subtract WeeeTax before adding product to quote
* Add Magento 2.4.4 and PHP 8.1 to automated tests

**Changes:**
* Use quote submit instead of place order to fix quantity validators
* Update testdata for order simulation

**Bugfixes:**
* Fixed if-statement to include T&T in OrderStatus calls (thnx @florisschreuder)

**Minimum requirements:**
* Magento 2.3.3 & PHP 7.1

## [1.9.1]

**Bugfixes:**
* Add proxy to ImportSimulator CLI command

**Changes:**
* Add setup:upgrade to GitHub actions
* Add Magento 2.4.3 with replacements to setup:di:compile GitHub actions

**Minimum requirements:**
* Magento 2.3.3 & PHP 7.1

## [1.9.0]

**Features:**
* Implemented new selftest
* Added return simulation function
* Order simulation CLI

**Changes:**
* Refactored config
* Refactor order import validation

**Bugfixes:**
* Fixed shipments webhook join
* Fixed returns imports

**Minimum requirements:**
* Magento 2.3.3 & PHP 7.1

## [1.8.3]

**Bugfixes:**
* Missing PaymentBlock if auto invoicing is enabled
* Shipping method from Channel is only shown if invoice is created

**Minimum requirements:**
* Magento 2.3.3 & PHP 7.1

## [1.8.2]

**Bugfixes:**
* Added module enable flag and fixed returns validation request
* Removed deprecated ChannabeLogger to add support for Magento 2.4.4

**Minimum requirements:**
* Magento 2.3.3 & PHP 7.1

## [1.8.1]

**Bugfixes:**
* Fixed shipping method name replace by Channel Name option
* Fixed issue with Amasty Shop By compatibility

**Minimum requirements:**
* Magento 2.3.3 & PHP 7.1

## [1.8.0]

**Features:**
* Refactor Returns
* Added `created_at` to product feed export
* Added compatibility for Amasty Shop By SEO (issue with url parser)

**Bugfixes:**
* Fixed issue with preview function for single product output
* Fixed disregard stock for FBB/LVB Orders

**Minimum requirements:**
* Magento 2.3.3 & PHP 7.1

## [1.7.0]

**Features:**
* Add option to use return labels for Channable

**Bugfixes:**
* Transfer transaction fee from quote to order #146
* Fixed order simulation product

**Minimum requirements:**
* Magento 2.3.3 & PHP 7.1

## [1.6.2]

**Bugfixes:**
* Ensure shipping cost is always a float #144 (thnx @ArjenMiedema)
* Set reserved order id on cart (thnx @JeroenVanLeusden)
* Add package weight to shipping rate request (thnx @markvds)
* Add missing component to date fields in order grid
* Add order subroles for ACL
* Make Logger component compatible with Monolog 2.0
* Refactor filtering on status for product feed export  #137

**Minimum requirements:**
* Magento 2.3.3 & PHP 7.1

## [1.6.1]

**Bugfixes:**
* Return correct data in getOrderIdPrefix #141

**Minimum requirements:**
* Magento 2.3.3 & PHP 7.1

## [1.6.0]

**Features:**
* Refactored order flow, added repo's and UX/UI admin edits
* Added new processed orders gird
* Added Transaction Fee order total for CDiscount Marketplace orders
* Moved to declarative schema

**Minimum requirements:**
* Magento 2.3.3 & PHP 7.1

**Note: This release is a major release, as we moved to declarative schema, refactored the order flow and due to this had to up the minimum requirements to Magento 2.3.3 and PHP 7.1.**

## [1.5.5]

**Features:**
* Save 'Customer Number' and 'Shipment Promise' to Payment Data

**Bugfix:**
* Fixes issue with qty_increments #131

**Minimum requirements:**
* Magento 2.2.x & PHP 7.x

_Please note, this will be one of the last versions to support Magento 2.2.x_

## [1.5.4]

**Changes:**
* Always save/show commission on order import

**Bugfix:**
* Fixes import of customers on order import when enabled
* Fixes missing currency code in feed

**Minimum requirements:**
* Magento 2.2.x & PHP 7.x

_Please note, this will be one of the last versions to support Magento 2.2.x_

## [1.5.3]

**Bugfix:**
* Schedule category is broken (in Magento EE/Commerce) #117
* Force including or excluding tax in product feed configuration option

**Minimum requirements:**
* Magento 2.2.x & PHP 7.x

_Please note, this will be one of the last versions to support Magento 2.2.x_

## [1.5.2]

**Changes:**
* Added support for Magento 2.4.3
* Remove stock qty's for bundle, configurable and grouped product types

**Minimum requirements:**
* Magento 2.2.x & PHP 7.x

_Please note, this will be one of the last versions to support Magento 2.2.x_

## [1.5.1]

**Bugfix:**
* Fixed php 7.4 implode() function for shipping method title (+ added GitHub Actions php linting)

**Minimum requirements:**
* Magento 2.2.x & PHP 7.x

_Please note, this will be one of the last versions to support Magento 2.2.x_

## [1.5.0]

**Features:**
* Option to overwrite Shipping Description with data from platform
* Added option to force the bypass of MSI for QTY in feed

**Minimum requirements:**
* Magento 2.2.x & PHP 7.x

_Please note, this will be one of the last versions to support Magento 2.2.x_

## [1.4.23]

* Fix: category ids to feed output #111
* Fix: Unset discount amount for orders imported by Channable
