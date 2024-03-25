<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Exception\LocalizedException;
use Magmodules\Channable\Service\Product\InventorySource;

/**
 * Class Source
 *
 * @package Magmodules\Channable\Helper
 */
class Source extends AbstractHelper
{

    const XPATH_LIMIT = 'magmodules_channable/general/limit';
    const XPATH_NAME_SOURCE = 'magmodules_channable/data/name_attribute';
    const XPATH_DESCRIPTION_SOURCE = 'magmodules_channable/data/description_attribute';
    const XPATH_BRAND_SOURCE = 'magmodules_channable/data/brand_attribute';
    const XPATH_EAN_SOURCE = 'magmodules_channable/data/ean_attribute';
    const XPATH_IMAGE_SOURCE = 'magmodules_channable/data/image';
    const XPATH_IMAGE_MAIN = 'magmodules_channable/data/main_image';
    const XPATH_IMAGE_INC_HIDDEN = 'magmodules_channable/data/hidden_images';
    const XPATH_SKU_SOURCE = 'magmodules_channable/data/sku_attribute';
    const XPATH_SIZE_SOURCE = 'magmodules_channable/data/size_attribute';
    const XPATH_COLOR_SOURCE = 'magmodules_channable/data/color_attribute';
    const XPATH_MATERIAL_SOURCE = 'magmodules_channable/data/material_attribute';
    const XPATH_GENDER_SOURCE = 'magmodules_channable/data/gender_attribute';
    const XPATH_EXTRA_FIELDS = 'magmodules_channable/advanced/extra_fields';
    const XPATH_WEIGHT_UNIT = 'general/locale/weight_unit';
    const XPATH_VISBILITY = 'magmodules_channable/filter/visbility_enabled';
    const XPATH_VISIBILITY_OPTIONS = 'magmodules_channable/filter/visbility';
    const XPATH_STOCK = 'magmodules_channable/filter/stock';
    const XPATH_DELIVERY_TIME = 'magmodules_channable/advanced/delivery_time';
    const XPATH_INVENTORY = 'magmodules_channable/advanced/inventory';
    const XPATH_INVENTORY_DATA = 'magmodules_channable/advanced/inventory_fields';
    const XPATH_FORCE_NON_MSI = 'magmodules_channable/advanced/force_non_msi';
    const XPATH_INVENTORY_SOURCE_ITEMS = 'magmodules_channable/advanced/inventory_source_items';
    const XPATH_TAX = 'magmodules_channable/advanced/tax';
    const XPATH_TAX_INCLUDE_BOTH = 'magmodules_channable/advanced/tax_include_both';
    const XPATH_MANAGE_STOCK = 'cataloginventory/item_options/manage_stock';
    const XPATH_MIN_SALES_QTY = 'cataloginventory/item_options/min_sale_qty';
    const XPATH_QTY_INCREMENTS = 'cataloginventory/item_options/qty_increments';
    const XPATH_QTY_INC_ENABLED = 'cataloginventory/item_options/enable_qty_increments';
    const XPATH_ENABLE_BACKORDERS = 'cataloginventory/item_options/backorders';
    const XPATH_CATEGORY_FILTER = 'magmodules_channable/filter/category_enabled';
    const XPATH_CATEGORY_FILTER_TYPE = 'magmodules_channable/filter/category_type';
    const XPATH_CATEGORY_IDS = 'magmodules_channable/filter/category';
    const XPATH_FILTERS = 'magmodules_channable/filter/filters';
    const XPATH_FILTERS_DATA = 'magmodules_channable/filter/filters_data';
    const XPATH_ADD_DISABLED = 'magmodules_channable/filter/add_disabled';
    const XPATH_CONFIGURABLE = 'magmodules_channable/types/configurable';
    const XPATH_CONFIGURABLE_LINK = 'magmodules_channable/types/configurable_link';
    const XPATH_CONFIGURABLE_IMAGE = 'magmodules_channable/types/configurable_image';
    const XPATH_CONFIGURABLE_PARENT_ATTS = 'magmodules_channable/types/configurable_parent_atts';
    const XPATH_CONFIGURABLE_NONVISIBLE = 'magmodules_channable/types/configurable_nonvisible';
    const XPATH_BUNDLE = 'magmodules_channable/types/bundle';
    const XPATH_BUNDLE_LINK = 'magmodules_channable/types/bundle_link';
    const XPATH_BUNDLE_IMAGE = 'magmodules_channable/types/bundle_image';
    const XPATH_BUNDLE_PARENT_ATTS = 'magmodules_channable/types/bundle_parent_atts';
    const XPATH_BUNDLE_NONVISIBLE = 'magmodules_channable/types/bundle_nonvisible';
    const XPATH_GROUPED = 'magmodules_channable/types/grouped';
    const XPATH_GROUPED_LINK = 'magmodules_channable/types/grouped_link';
    const XPATH_GROUPED_IMAGE = 'magmodules_channable/types/grouped_image';
    const XPATH_GROUPED_PARENT_PRICE = 'magmodules_channable/types/grouped_parent_price';
    const XPATH_GROUPED_PARENT_ATTS = 'magmodules_channable/types/grouped_parent_atts';
    const XPATH_GROUPED_NONVISIBLE = 'magmodules_channable/types/grouped_nonvisible';

    /**
     * @var General
     */
    private $generalHelper;
    /**
     * @var Product
     */
    private $productHelper;
    /**
     * @var Item
     */
    private $itemHelper;
    /**
     * @var Category
     */
    private $categoryHelper;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var InventorySource
     */
    private $inventorySource;
    /**
     * @var
     */
    private $storeId = null;

    /**
     * Source constructor.
     *
     * @param Context               $context
     * @param StoreManagerInterface $storeManager
     * @param General               $generalHelper
     * @param Category              $categoryHelper
     * @param Product               $productHelper
     * @param InventorySource       $inventorySource
     * @param Item                  $itemHelper
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        General $generalHelper,
        Category $categoryHelper,
        Product $productHelper,
        InventorySource $inventorySource,
        Item $itemHelper
    ) {
        $this->generalHelper = $generalHelper;
        $this->productHelper = $productHelper;
        $this->itemHelper = $itemHelper;
        $this->categoryHelper = $categoryHelper;
        $this->storeManager = $storeManager;
        $this->inventorySource = $inventorySource;
        parent::__construct($context);
    }

    /**
     * Get config data as array
     *
     * @param int $storeId
     * @param string|null $type
     * @param string|null $currency
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getConfig(int $storeId, ?string $type = 'feed', ?string $currency = null): array
    {
        $config = [
            'flat' => false,
            'type' => $type,
            'store_id' => $this->getStoreId((int)$storeId),
            'website_id' => $this->storeManager->getStore()->getWebsiteId(),
            'timestamp' => $this->generalHelper->getLocaleDate((int)$storeId),
            'date_time' => $this->generalHelper->getDateTime(),
            'filters' => $this->getProductFilters($type),
            'attributes' => $this->getAttributes($type, $this->getProductFilters($type)),
            'price_config' => $this->getPriceConfig($type, $currency),
            'inventory' => $this->getInventoryData($type),
            'inc_hidden_image' => $this->getStoreValue(self::XPATH_IMAGE_INC_HIDDEN)
        ];
        switch ($type) {
            case 'feed':
                $config += [
                    'base_url' => $this->storeManager->getStore()->getBaseUrl(),
                    'weight_unit' => ' ' . $this->getStoreValue(self::XPATH_WEIGHT_UNIT),
                    'categories' => $this->categoryHelper->getCollection(
                        $storeId,
                        '',
                        '',
                        'channable_cat_disable_export'
                    ),
                    'item_updates' =>  $this->itemHelper->isEnabled($storeId),
                    'delivery' => $this->getStoreValue(self::XPATH_DELIVERY_TIME)
                ];
                break;
            case 'api':
                $config['api'] = $this->itemHelper->getApiConfigDetails($storeId);
                break;

        }

        $this->storeId = null;

        return $config;
    }

    /**
     * @param null $storeId
     *
     * @return null
     */
    public function getStoreId($storeId = null)
    {
        if ($this->storeId === null) {
            $this->storeId = $storeId;
        }

        return $this->storeId;
    }

    /**
     * @param $type
     *
     * @return array
     */
    public function getProductFilters($type)
    {
        $filters = [];
        $filters['type_id'] = ['simple', 'downloadable', 'virtual', 'giftcard'];
        $filters['relations'] = [];
        $filters['exclude_parents'] = [];
        $filters['nonvisible'] = [];
        $filters['parent_attributes'] = [];
        $filters['image'] = [];
        $filters['link'] = [];

        $configurabale = $this->getStoreValue(self::XPATH_CONFIGURABLE);
        switch ($configurabale) {
            case "parent":
                array_push($filters['type_id'], 'configurable');
                break;
            case "simple":
                array_push($filters['relations'], 'configurable');
                array_push($filters['exclude_parents'], 'configurable');

                if ($attributes = $this->getStoreValue(self::XPATH_CONFIGURABLE_PARENT_ATTS)) {
                    $filters['parent_attributes']['configurable'] = explode(',', $attributes);
                }

                if ($nonVisible = $this->getStoreValue(self::XPATH_CONFIGURABLE_NONVISIBLE)) {
                    array_push($filters['nonvisible'], 'configurable');
                }

                if ($link = $this->getStoreValue(self::XPATH_CONFIGURABLE_LINK)) {
                    $filters['link']['configurable'] = $link;
                    if (isset($filters['parent_attributes']['configurable'])) {
                        array_push($filters['parent_attributes']['configurable'], 'link');
                    } else {
                        $filters['parent_attributes']['configurable'] = ['link'];
                    }
                }

                if ($image = $this->getStoreValue(self::XPATH_CONFIGURABLE_IMAGE)) {
                    $filters['image']['configurable'] = $image;
                    if (isset($filters['parent_attributes']['configurable'])) {
                        array_push($filters['parent_attributes']['configurable'], 'image_link');
                    } else {
                        $filters['parent_attributes']['configurable'] = ['image_link'];
                    }
                }

                break;
            case "both":
                array_push($filters['type_id'], 'configurable');
                array_push($filters['relations'], 'configurable');

                if ($attributes = $this->getStoreValue(self::XPATH_CONFIGURABLE_PARENT_ATTS)) {
                    $filters['parent_attributes']['configurable'] = explode(',', $attributes);
                }

                if ($nonVisible = $this->getStoreValue(self::XPATH_CONFIGURABLE_NONVISIBLE)) {
                    array_push($filters['nonvisible'], 'configurable');
                }

                if ($link = $this->getStoreValue(self::XPATH_CONFIGURABLE_LINK)) {
                    $filters['link']['configurable'] = $link;
                    if (isset($filters['parent_attributes']['configurable'])) {
                        array_push($filters['parent_attributes']['configurable'], 'link');
                    } else {
                        $filters['parent_attributes']['configurable'] = ['link'];
                    }
                }

                if ($image = $this->getStoreValue(self::XPATH_CONFIGURABLE_IMAGE)) {
                    $filters['image']['configurable'] = $image;
                    if (isset($filters['parent_attributes']['configurable'])) {
                        array_push($filters['parent_attributes']['configurable'], 'image_link');
                    } else {
                        $filters['parent_attributes']['configurable'] = ['image_link'];
                    }
                }

                break;
        }

        $bundle = $this->getStoreValue(self::XPATH_BUNDLE);
        switch ($bundle) {
            case "parent":
                array_push($filters['type_id'], 'bundle');
                break;
            case "simple":
                array_push($filters['relations'], 'bundle');
                array_push($filters['exclude_parents'], 'bundle');

                if ($attributes = $this->getStoreValue(self::XPATH_BUNDLE_PARENT_ATTS)) {
                    $filters['parent_attributes']['bundle'] = explode(',', $attributes);
                }

                if ($nonVisible = $this->getStoreValue(self::XPATH_BUNDLE_NONVISIBLE)) {
                    array_push($filters['nonvisible'], 'bundle');
                }

                if ($link = $this->getStoreValue(self::XPATH_BUNDLE_LINK)) {
                    $filters['link']['bundle'] = $link;
                    if (isset($filters['parent_attributes']['bundle'])) {
                        array_push($filters['parent_attributes']['bundle'], 'link');
                    } else {
                        $filters['parent_attributes']['bundle'] = ['link'];
                    }
                }

                if ($image = $this->getStoreValue(self::XPATH_BUNDLE_IMAGE)) {
                    $filters['image']['bundle'] = $image;
                    if (isset($filters['parent_attributes']['bundle'])) {
                        array_push($filters['parent_attributes']['bundle'], 'image_link');
                    } else {
                        $filters['parent_attributes']['bundle'] = ['image_link'];
                    }
                }

                break;
            case "both":
                array_push($filters['type_id'], 'bundle');
                array_push($filters['relations'], 'bundle');

                if ($attributes = $this->getStoreValue(self::XPATH_BUNDLE_PARENT_ATTS)) {
                    $filters['parent_attributes']['bundle'] = explode(',', $attributes);
                }

                if ($nonVisible = $this->getStoreValue(self::XPATH_BUNDLE_NONVISIBLE)) {
                    array_push($filters['nonvisible'], 'bundle');
                }

                if ($link = $this->getStoreValue(self::XPATH_BUNDLE_LINK)) {
                    $filters['link']['bundle'] = $link;
                    if (isset($filters['parent_attributes']['bundle'])) {
                        array_push($filters['parent_attributes']['bundle'], 'link');
                    } else {
                        $filters['parent_attributes']['bundle'] = ['link'];
                    }
                }

                if ($image = $this->getStoreValue(self::XPATH_BUNDLE_IMAGE)) {
                    $filters['image']['bundle'] = $image;
                    if (isset($filters['parent_attributes']['bundle'])) {
                        array_push($filters['parent_attributes']['bundle'], 'image_link');
                    } else {
                        $filters['parent_attributes']['bundle'] = ['image_link'];
                    }
                }

                break;
        }

        $grouped = $this->getStoreValue(self::XPATH_GROUPED);
        switch ($grouped) {
            case "parent":
                array_push($filters['type_id'], 'grouped');
                break;
            case "simple":
                array_push($filters['relations'], 'grouped');
                array_push($filters['exclude_parents'], 'grouped');

                if ($attributes = $this->getStoreValue(self::XPATH_GROUPED_PARENT_ATTS)) {
                    $filters['parent_attributes']['grouped'] = explode(',', $attributes);
                }

                if ($nonVisible = $this->getStoreValue(self::XPATH_GROUPED_NONVISIBLE)) {
                    array_push($filters['nonvisible'], 'grouped');
                }

                if ($link = $this->getStoreValue(self::XPATH_GROUPED_LINK)) {
                    $filters['link']['grouped'] = $link;
                    if (isset($filters['parent_attributes']['grouped'])) {
                        array_push($filters['parent_attributes']['grouped'], 'link');
                    } else {
                        $filters['parent_attributes']['grouped'] = ['link'];
                    }
                }

                if ($image = $this->getStoreValue(self::XPATH_GROUPED_IMAGE)) {
                    $filters['image']['grouped'] = $image;
                    if (isset($filters['parent_attributes']['grouped'])) {
                        array_push($filters['parent_attributes']['grouped'], 'image_link');
                    } else {
                        $filters['parent_attributes']['grouped'] = ['image_link'];
                    }
                }

                break;
            case "both":
                array_push($filters['type_id'], 'grouped');
                array_push($filters['relations'], 'grouped');

                if ($attributes = $this->getStoreValue(self::XPATH_GROUPED_PARENT_ATTS)) {
                    $filters['parent_attributes']['grouped'] = explode(',', $attributes);
                }

                if ($nonVisible = $this->getStoreValue(self::XPATH_GROUPED_NONVISIBLE)) {
                    array_push($filters['nonvisible'], 'grouped');
                }

                if ($link = $this->getStoreValue(self::XPATH_GROUPED_LINK)) {
                    $filters['link']['grouped'] = $link;
                    if (isset($filters['parent_attributes']['grouped'])) {
                        array_push($filters['parent_attributes']['grouped'], 'link');
                    } else {
                        $filters['parent_attributes']['grouped'] = ['link'];
                    }
                }

                if ($image = $this->getStoreValue(self::XPATH_GROUPED_IMAGE)) {
                    $filters['image']['grouped'] = $image;
                    if (isset($filters['parent_attributes']['grouped'])) {
                        array_push($filters['parent_attributes']['grouped'], 'image_link');
                    } else {
                        $filters['parent_attributes']['grouped'] = ['image_link'];
                    }
                }

                break;
        }

        $visibilityFilter = $this->getStoreValue(self::XPATH_VISBILITY);
        if ($visibilityFilter) {
            $visibility = $this->getStoreValue(self::XPATH_VISIBILITY_OPTIONS);
            $filters['visibility'] = explode(',', (string)$visibility);
        } else {
            $filters['visibility'] = [];
        }

        $filters['limit'] = (int)$this->getStoreValue(self::XPATH_LIMIT);
        if ($type == 'api') {
            $filters['limit'] = 0;
        }

        $filters['stock'] = $this->getStoreValue(self::XPATH_STOCK);

        $categoryFilter = $this->getStoreValue(self::XPATH_CATEGORY_FILTER);
        if ($categoryFilter) {
            $categoryIds = $this->getStoreValue(self::XPATH_CATEGORY_IDS);
            $filterType = $this->getStoreValue(self::XPATH_CATEGORY_FILTER_TYPE);
            if (!empty($categoryIds) && !empty($filterType)) {
                $filters['category_ids'] = explode(',', $categoryIds);
                $filters['category_type'] = $filterType;
            }
        }

        $filters['advanced'] = [];
        $productFilters = $this->getStoreValue(self::XPATH_FILTERS);
        if ($productFilters) {
            if ($advFilters = $this->getStoreValueArray(self::XPATH_FILTERS_DATA)) {
                foreach ($advFilters as $advFilter) {
                    array_push($filters['advanced'], $advFilter);
                }
            }
        }

        if ($this->getStoreValue(self::XPATH_ADD_DISABLED)) {
            $filters['add_disabled'] = 1;
        }

        return $filters;
    }

    /**
     * @param $path
     *
     * @return mixed
     */
    public function getStoreValue($path)
    {
        return $this->generalHelper->getStoreValue($path, $this->storeId);
    }

    /**
     * @param $path
     *
     * @return mixed
     */
    public function getStoreValueArray($path)
    {
        return $this->generalHelper->getStoreValueArray($path, $this->storeId);
    }

    /**
     * @param       $type
     * @param array $filters
     * @param null  $storeId
     *
     * @return array
     */
    public function getAttributes($type, $filters = [], $storeId = null)
    {
        $this->getStoreId($storeId);

        $inventory = $this->getStoreValue(self::XPATH_INVENTORY);

        $attributes = [];
        $attributes['id'] = [
            'label'                     => 'id',
            'source'                    => 'entity_id',
            'parent_selection_disabled' => 1,
        ];
        $attributes['title'] = [
            'label'  => 'title',
            'source' => $this->getStoreValue(self::XPATH_NAME_SOURCE),
        ];
        $attributes['ean'] = [
            'label'  => 'ean',
            'source' => $this->getStoreValue(self::XPATH_EAN_SOURCE),
        ];
        $attributes['price'] = [
            'label'                     => 'price',
            'collection'                => 'price',
            'parent_selection_disabled' => 1
        ];
        $attributes['type_id'] = [
            'label'                     => 'type_id',
            'source'                    => 'type_id',
            'parent_selection_disabled' => 1,
        ];
        $attributes['status'] = [
            'label'                     => 'status',
            'source'                    => 'status',
            'parent_selection_disabled' => 1,
        ];
        $attributes['visibility'] = [
            'label'  => 'visibility',
            'source' => 'visibility',
        ];
        $attributes['availability'] = [
            'label'  => 'availability',
            'source' => 'is_in_stock'
        ];
        $attributes['price_type'] = [
            'label'  => 'price_type',
            'source' => 'price_type'
        ];

        if ($type != 'api') {
            $attributes['created_at'] = [
                'label'  => 'created_at',
                'source' => 'created_at',
            ];
            $attributes['availability'] = [
                'label'     => 'availability',
                'source'    => 'is_in_stock',
                'condition' => [
                    '1:in stock',
                    '0:out of stock'
                ]
            ];
            $attributes['description'] = [
                'label'  => 'description',
                'source' => $this->getStoreValue(self::XPATH_DESCRIPTION_SOURCE),
            ];
            $attributes['link'] = [
                'label'  => 'link',
                'source' => 'product_url',
            ];
            $attributes['image_link'] = [
                'label'  => 'image_link',
                'source' => $this->getStoreValue(self::XPATH_IMAGE_SOURCE),
                'main'   => $this->getStoreValue(self::XPATH_IMAGE_MAIN),
            ];
            $attributes['brand'] = [
                'label'  => 'brand',
                'source' => $this->getStoreValue(self::XPATH_BRAND_SOURCE),
            ];
            $attributes['sku'] = [
                'label'  => 'sku',
                'source' => $this->getStoreValue(self::XPATH_SKU_SOURCE),
            ];
            $attributes['color'] = [
                'label'  => 'color',
                'source' => $this->getStoreValue(self::XPATH_COLOR_SOURCE),
            ];
            $attributes['gender'] = [
                'label'  => 'gender',
                'source' => $this->getStoreValue(self::XPATH_GENDER_SOURCE)
            ];
            $attributes['material'] = [
                'label'  => 'material',
                'source' => $this->getStoreValue(self::XPATH_MATERIAL_SOURCE),
            ];
            $attributes['size'] = [
                'label'  => 'size',
                'source' => $this->getStoreValue(self::XPATH_SIZE_SOURCE),
            ];

            if ($inventory) {
                $inventoryData = explode(',', (string)$this->getStoreValue(self::XPATH_INVENTORY_DATA));
                if (in_array('min_sale_qty', $inventoryData)) {
                    $attributes['min_sale_qty'] = [
                        'label' => 'min_sale_qty',
                        'source' => 'min_sale_qty',
                        'actions' => ['number'],
                        'default' => '1.00',
                    ];
                }
                if (in_array('qty_increments', $inventoryData)) {
                    $attributes['qty_increments'] = [
                        'label' => 'qty_increments',
                        'source' => 'qty_increments',
                        'actions' => ['number'],
                        'default' => '1.00',
                    ];
                }
                if (in_array('backorders', $inventoryData)) {
                    $attributes['backorders'] = [
                        'label'   => 'backorders',
                        'source'  => 'backorders'
                    ];
                }

                if ($this->getStoreValue(self::XPATH_INVENTORY_SOURCE_ITEMS)) {
                    $attributes['inventory_source_items'] = [
                        'label'   => 'inventory_source_items',
                        'source'  => 'inventory_source_items'
                    ];
                }

            }
            $attributes['weight'] = [
                'label'   => 'shipping_weight',
                'source'  => 'weight',
                'suffix'  => 'weight_unit',
                'actions' => ['number']
            ];
            $attributes['item_group_id'] = [
                'label'  => 'item_group_id',
                'source' => $attributes['id']['source'],
                'parent' => 2
            ];
            $attributes['is_bundle'] = [
                'label'                     => 'is_bundle',
                'source'                    => 'type_id',
                'condition'                 => [
                    '*:false',
                    'bundle:true',
                ],
                'parent_selection_disabled' => 1,
            ];

            if ($extraFields = $this->getExtraFields()) {
                $attributes = array_merge($attributes, $extraFields);
            }
        }

        if ($inventory || $type == 'api') {
            $attributes['manage_stock'] = [
                'label'     => 'manage_stock',
                'source'    => 'manage_stock',
                'condition' => [
                    '0:false',
                    '1:true',
                ],
            ];
            $attributes['qty'] = [
                'label'   => 'qty',
                'source'  => 'qty',
                'actions' => $type == 'api' ? ['round'] : [],
            ];
        }

        if ($type == 'parent') {
            return $attributes;
        } else {
            return $this->productHelper->addAttributeData($attributes, $filters);
        }
    }

    /**
     * @return array
     */
    public function getExtraFields(): array
    {
        $extraFields = [];
        if ($attributes = $this->getStoreValueArray(self::XPATH_EXTRA_FIELDS)) {
            foreach ($attributes as $attribute) {
                $label = strtolower(str_replace(' ', '_', $attribute['name']));
                if (preg_match('/^rendered_price__/', $attribute['attribute'])) {
                    $extraFields['rendered_price__' . $label] = [
                        'label'  => $label,
                        'price_source' => explode('__', $attribute['attribute'])[1],
                        'actions' => !empty($attribute['actions']) ? [$attribute['actions']] : null,
                    ];
                } else {
                    $extraFields[$label] = [
                        'label'  => $label,
                        'source' => $attribute['attribute'],
                        'actions' => !empty($attribute['actions']) ? [$attribute['actions']] : null,
                    ];
                }
            }
        }

        return $extraFields;
    }

    /**
     * @param string|null $type
     * @param string|null $currency
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getPriceConfig(?string $type, ?string $currency = null): array
    {
        $store = $this->storeManager->getStore();

        $priceFields = [];
        $priceFields['price'] = 'price';
        $priceFields['sales_price'] = 'sale_price';
        $priceFields['min_price'] = 'min_price';
        $priceFields['max_price'] = 'max_price';

        $priceFields['sales_date_range'] = 'sale_price_effective_date';
        $priceFields['currency'] = $currency === null
            ? $store->getDefaultCurrency()->getCode()
            : strtoupper($currency);
        $priceFields['exchange_rate'] = $store->getBaseCurrency()->getRate($priceFields['currency']);

        foreach ($store->getAvailableCurrencyCodes() as $currencyCode) {
            $priceFields['exchange_rate_' . $currencyCode] = $store->getBaseCurrency()->getRate($currencyCode);
        }

        $priceFields['grouped_price_type'] = $this->getStoreValue(self::XPATH_GROUPED_PARENT_PRICE);

        if ($this->getStoreValue(self::XPATH_TAX)) {
            $priceFields['incl_vat'] = true;
        }

        if ($type != 'api') {
            $priceFields['use_currency'] = true;
        } else {
            $priceFields['use_currency'] = false;
        }

        if ($this->getStoreValue(self::XPATH_TAX_INCLUDE_BOTH)) {
            $priceFields['tax_include_both'] = true;
            $priceFields['price_excl'] = 'price_excl';
            $priceFields['price_incl'] = 'price_incl';
            $priceFields['sales_price_excl'] = 'sales_price_excl';
            $priceFields['sales_price_incl'] = 'sales_price_incl';
        }

        return $priceFields;
    }

    /**
     * @param $type
     *
     * @return array
     * @throws LocalizedException
     */
    public function getInventoryData($type)
    {
        $invAtt = [];
        $enabled = $this->getStoreValue(self::XPATH_INVENTORY);
        $fields = $this->getStoreValue(self::XPATH_INVENTORY_DATA);
        $invAtt['attributes'][] = 'is_in_stock';
        $invAtt['attributes'][] = 'manage_stock';
        $invAtt['attributes'][] = 'use_config_manage_stock';
        $invAtt['config_manage_stock'] = $this->getStoreValue(self::XPATH_MANAGE_STOCK);

        if (!$enabled || empty($fields)) {

            if ($type == 'api') {
                $invAtt['attributes'][] = 'qty';
            }
            return $invAtt;
        }

        $invAtt['attributes'] = array_merge($invAtt['attributes'], explode(',', (string)$fields));
        $invAtt['attributes'][] = 'qty';

        if (in_array('qty_increments', $invAtt['attributes'])) {
            $invAtt['attributes'][] = 'use_config_qty_increments';
            $invAtt['attributes'][] = 'enable_qty_increments';
            $invAtt['attributes'][] = 'use_config_enable_qty_inc';
            $invAtt['config_qty_increments'] = $this->getStoreValue(self::XPATH_QTY_INCREMENTS);
            $invAtt['config_enable_qty_inc'] = $this->getStoreValue(self::XPATH_QTY_INC_ENABLED);
        }

        if (in_array('min_sale_qty', $invAtt['attributes'])) {
            $invAtt['attributes'][] = 'use_config_min_sale_qty';
            $invAtt['config_min_sale_qty'] = $this->getStoreValue(self::XPATH_MIN_SALES_QTY);
        }

        if (in_array('backorders', $invAtt['attributes'])) {
            $invAtt['attributes'][] = 'use_config_backorders';
            $invAtt['config_backorders'] = $this->getStoreValue(self::XPATH_ENABLE_BACKORDERS);
        }

        $websiteCode = $this->storeManager->getWebsite()->getCode();

        if ($this->getStoreValue(self::XPATH_FORCE_NON_MSI)) {
            $invAtt['stock_id'] = null;
        } else {
            $invAtt['stock_id'] = $this->inventorySource->execute($websiteCode);
            $invAtt['inventory_source_items'] = (bool)$this->getStoreValue(self::XPATH_INVENTORY_SOURCE_ITEMS);
        }

        return $invAtt;
    }

    /**
     * @param                                $dataRow
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Catalog\Model\Product $parent
     * @param                                $config
     *
     * @return array
     */
    public function reformatData($dataRow, $product, $parent, $config)
    {
        if (!empty($config['categories'])) {
            if ($categoryData = $this->getCategoryData($product, $parent, $config['categories'])) {
                $dataRow = array_merge($dataRow, $categoryData);
            }
        }
        if (!empty($dataRow['image_link'])) {
            if ($imageData = $this->getImageData($dataRow)) {
                $dataRow = array_merge($dataRow, $imageData);
            }
        }
        if ($deliveryTime = $this->getDeliveryTime($dataRow, $config)) {
            $dataRow = array_merge($dataRow, $deliveryTime);
        }

        return $dataRow;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Catalog\Model\Product $parent
     * @param                                $categories
     *
     * @return array
     */
    public function getCategoryData($product, $parent, $categories)
    {
        $path = [];

        if ($parent) {
            $catgoryIds = $parent->getCategoryIds();
        } else {
            $catgoryIds = $product->getCategoryIds();
        }

        foreach ($catgoryIds as $catId) {
            if (!empty($categories[$catId])) {
                $category = $categories[$catId];
                if (!empty($category['path'])) {
                    $path[] = [
                        'level' => $category['level'],
                        'id' => $catId,
                        'path' => implode(' > ', $category['path'])
                    ];
                }
            }
        }
        if (!empty($path)) {
            foreach ($path as $key => $row) {
                $temp[$key] = $row['level'];
            }
            array_multisort($temp, SORT_DESC, $path);
            $data['categories'] = $path;
            return $data;
        }
        return [];
    }

    /**
     * @param $dataRow
     *
     * @return array
     */
    public function getImageData($dataRow)
    {
        $i = 0;
        $imageData = [];

        if (is_array($dataRow['image_link'])) {
            $imageLinks = $dataRow['image_link'];
            foreach ($imageLinks as $link) {
                if ($i == 0) {
                    $imageData['image_link'] = $link;
                } else {
                    $imageData['additional_imagelinks'][] = $link;
                }
                $i++;
            }
        } else {
            $imageData['image_link'] = $dataRow['image_link'];
        }

        return $imageData;
    }

    /**
     * @param $dataRow
     * @param $config
     *
     * @return array|bool
     */
    public function getDeliveryTime($dataRow, $config)
    {
        if (!empty($config['delivery'])) {
            $deliveryTime = [];
            $stock = 'in_stock';
            if (!empty($dataRow['availability'])) {
                if ($dataRow['availability'] == 'out of stock') {
                    $stock = 'out_of_stock';
                }
            }
            $countries = $this->generalHelper->getValueArray($config['delivery']);
            if (is_array($countries)) {
                foreach ($countries as $country) {
                    if (!empty($country[$stock])) {
                        $deliveryTime['delivery_period_' . strtolower($country['code'])] = $country[$stock];
                    }
                }
            }
            return $deliveryTime;
        }

        return false;
    }
}
