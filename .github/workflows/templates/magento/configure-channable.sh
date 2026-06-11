#!/bin/bash
#
# Copyright Magmodules.eu. All rights reserved.
# See COPYING.txt for license details.
#

set -e

# Enable Channable module
bin/magento module:enable Magmodules_Channable
bin/magento setup:upgrade --keep-generated

# Enable order import
bin/magento config:set magmodules_channable/general/enable 1
bin/magento config:set magmodules_channable_marketplace/general/enable 1

# Set shipping origin to NL
bin/magento config:set shipping/origin/country_id NL
bin/magento config:set shipping/origin/region_id 0
bin/magento config:set shipping/origin/postcode '1000 AA'

# Tax defaults: price includes tax, cross-border trade disabled, tax based on shipping address
bin/magento config:set tax/calculation/price_includes_tax 1
bin/magento config:set tax/calculation/cross_border_trade_enabled 0
bin/magento config:set tax/calculation/based_on shipping
bin/magento config:set tax/calculation/algorithm TOTAL_BASE_CALCULATION

# Default tax destination — must match shipping origin so getTaxPrice() resolves
# the correct rate when no customer is logged in (e.g. feed generation context)
bin/magento config:set tax/defaults/country NL
bin/magento config:set tax/defaults/region 0
bin/magento config:set tax/defaults/postcode '1000 AA'

# Tax display settings
bin/magento config:set tax/display/type 2
bin/magento config:set tax/display/shipping 2
bin/magento config:set tax/cart_display/price 2
bin/magento config:set tax/cart_display/subtotal 2
bin/magento config:set tax/cart_display/shipping 2
bin/magento config:set tax/cart_display/grandtotal 1

# Disable 2FA
if grep -q Magento_TwoFactorAuth "app/etc/config.php"; then
    bin/magento module:disable Magento_TwoFactorAuth -f
fi

# Run PHP script to create tax rates and rules
php /data/setup-tax-rules.php

# Flush cache
bin/magento cache:flush
