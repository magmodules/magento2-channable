<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Catalog\Model\Product\Visibility;
use Magmodules\Channable\Helper\General as GeneralHelper;
use Magmodules\Channable\Helper\Product as ProductHelper;
use Magmodules\Channable\Helper\Category as CategoryHelper;
use Magmodules\Channable\Helper\Feed as FeedHelper;

class Source extends AbstractHelper
{

    const XML_PATH_LIMIT = 'magmodules_channable/general/limit';

    const XML_PATH_NAME_SOURCE = 'magmodules_channable/data/name_attribute';
    const XML_PATH_DESCRIPTION_SOURCE = 'magmodules_channable/data/description_attribute';
    const XML_PATH_BRAND_SOURCE = 'magmodules_channable/data/brand_attribute';
    const XML_PATH_EAN_SOURCE = 'magmodules_channable/data/ean_attribute';
    const XML_PATH_IMAGE_SOURCE = 'magmodules_channable/data/image';

    const XML_PATH_SKU_SOURCE = 'magmodules_channable/data/sku_attribute';
    const XML_PATH_SIZE_SOURCE = 'magmodules_channable/data/size_attribute';
    const XML_PATH_COLOR_SOURCE = 'magmodules_channable/data/color_attribute';
    const XML_PATH_MATERIAL_SOURCE = 'magmodules_channable/data/material_attribute';
    const XML_PATH_GENDER_SOURCE = 'magmodules_channable/data/gender_attribute';
    const XML_PATH_EXTRA_FIELDS = 'magmodules_channable/advanced/extra_fields';
    const XML_PATH_WEIGHT_UNIT = 'general/locale/weight_unit';

    const XML_PATH_VISBILITY = 'magmodules_channable/filter/visbility_enabled';
    const XML_PATH_VISIBILITY_OPTIONS = 'magmodules_channable/filter/visbility';
    const XML_PATH_STOCK = 'magmodules_channable/filter/stock';
    const XML_PATH_RELATIONS_ENABLED = 'magmodules_channable/advanced/relations';
    const XML_PATH_PARENT_ATTS = 'magmodules_channable/advanced/parent_atts';
    const XML_PATH_DELIVERY_TIME = 'magmodules_channable/advanced/delivery_time';

    const XML_PATH_INVENTORY = 'magmodules_channable/advanced/inventory';
    const XML_PATH_INVENTORY_DATA = 'magmodules_channable/advanced/inventory_fields';
    const XML_PATH_MANAGE_STOCK = 'cataloginventory/item_options/manage_stock';
    const XML_PATH_MIN_SALES_QTY = 'cataloginventory/item_options/min_sale_qty';
    const XML_PATH_QTY_INCREMENTS = 'cataloginventory/item_options/qty_increments';
    const XML_PATH_QTY_INC_ENABLED = 'cataloginventory/item_options/enable_qty_increments';

    const XML_PATH_CATEGORY_FILTER = 'magmodules_channable/filter/category_enabled';
    const XML_PATH_CATEGORY_FILTER_TYPE = 'magmodules_channable/filter/category_type';
    const XML_PATH_CATEGORY_IDS = 'magmodules_channable/filter/category';

    protected $general;
    protected $product;
    protected $category;
    protected $feed;
    protected $storeManager;

    /**
     * Source constructor.
     *
     * @param Context               $context
     * @param StoreManagerInterface $storeManager
     * @param General               $general
     * @param Category              $category
     * @param Product               $product
     * @param Feed                  $feed
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        GeneralHelper $general,
        CategoryHelper $category,
        ProductHelper $product,
        FeedHelper $feed
    ) {
        $this->general = $general;
        $this->product = $product;
        $this->category = $category;
        $this->feed = $feed;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * @param $storeId
     *
     * @return array
     */
    public function getConfig($storeId)
    {
        $config = [];
        $config['store_id'] = $storeId;
        $config['flat'] = false;
        $config['attributes'] = $this->getAttributes($storeId);
        $config['price_config'] = $this->getPriceConfig();
        $config['url_type_media'] = $this->storeManager->getStore($storeId)->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        $config['base_url'] = $this->storeManager->getStore($storeId)->getBaseUrl();
        $config['filters'] = $this->getProductFilters($storeId);
        $config['weight_unit'] = ' ' . $this->general->getStoreValue(self::XML_PATH_WEIGHT_UNIT, $storeId);
        $config['categories'] = $this->category->getCollection($storeId, '', '', 'channable_cat_disable_export');
        $config['inventory'] = $this->getInventoryData($storeId);
        $config['delivery'] = $this->general->getStoreValue(self::XML_PATH_DELIVERY_TIME, $storeId);

        return $config;
    }

    /**
     * @param int $storeId
     *
     * @return array
     */
    public function getAttributes($storeId = 0)
    {
        $attributes = [];
        $attributes['id'] = [
            'label'                     => 'id',
            'source'                    => 'entity_id',
            'parent_selection_disabled' => 1,
        ];
        $attributes['title'] = [
            'label'  => 'title',
            'source' => $this->general->getStoreValue(self::XML_PATH_NAME_SOURCE, $storeId),
        ];
        $attributes['description'] = [
            'label'  => 'description',
            'source' => $this->general->getStoreValue(self::XML_PATH_DESCRIPTION_SOURCE, $storeId),
        ];
        $attributes['link'] = [
            'label'  => 'link',
            'source' => 'product_url',
        ];
        $attributes['image_link'] = [
            'label'  => 'image_link',
            'source' => $this->general->getStoreValue(self::XML_PATH_IMAGE_SOURCE, $storeId),
        ];
        $attributes['price'] = [
            'label'                     => 'price',
            'collection'                => 'price',
            'parent_selection_disabled' => 1
        ];
        $attributes['brand'] = [
            'label'  => 'brand',
            'source' => $this->general->getStoreValue(self::XML_PATH_BRAND_SOURCE, $storeId),
        ];
        $attributes['ean'] = [
            'label'  => 'ean',
            'source' => $this->general->getStoreValue(self::XML_PATH_EAN_SOURCE, $storeId),
        ];
        $attributes['sku'] = [
            'label'  => 'sku',
            'source' => $this->general->getStoreValue(self::XML_PATH_SKU_SOURCE, $storeId),
        ];
        $attributes['color'] = [
            'label'  => 'color',
            'source' => $this->general->getStoreValue(self::XML_PATH_COLOR_SOURCE, $storeId),
        ];
        $attributes['gender'] = [
            'label'  => 'gender',
            'source' => $this->general->getStoreValue(self::XML_PATH_GENDER_SOURCE, $storeId)
        ];
        $attributes['material'] = [
            'label'  => 'material',
            'source' => $this->general->getStoreValue(self::XML_PATH_MATERIAL_SOURCE, $storeId),
        ];
        $attributes['size'] = [
            'label'  => 'size',
            'source' => $this->general->getStoreValue(self::XML_PATH_SIZE_SOURCE, $storeId),
        ];
        $attributes['product_type'] = [
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
        $attributes['manage_stock'] = [
            'label'     => 'manage_stock',
            'source'    => 'manage_stock',
            'condition' => [
                '0:false',
                '1:true',
            ],
        ];
        $attributes['min_sale_qty'] = [
            'label'   => 'min_sale_qty',
            'source'  => 'min_sale_qty',
            'actions' => ['number'],
            'default' => '1.00',
        ];
        $attributes['qty_increments'] = [
            'label'   => 'qty_increments',
            'source'  => 'qty_increments',
            'actions' => ['number'],
            'default' => '1.00',
        ];
        $attributes['qty'] = [
            'label'   => 'qty',
            'source'  => 'qty',
            'actions' => ['number'],
        ];
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
        $attributes['availability'] = [
            'label'     => 'availability',
            'source'    => 'is_in_stock',
            'condition' => [
                '1:in stock',
                '0:out of stock'
            ]
        ];

        if ($extraFields = $this->getExtraFields($storeId)) {
            $attributes = array_merge($attributes, $extraFields);
        }

        $parentAttributes = $this->getParentAttributes($storeId);
        return $this->product->addAttributeData($attributes, $parentAttributes);
    }

    /**
     * @param $storeId
     *
     * @return array
     */
    public function getExtraFields($storeId)
    {
        $extraFields = [];
        if ($attributes = $this->general->getStoreValue(self::XML_PATH_EXTRA_FIELDS, $storeId)) {
            $attributes = @unserialize($attributes);
            foreach ($attributes as $attribute) {
                $label = str_replace(' ', '_', $attribute['name']);
                $extraFields[$attribute['attribute']] = [
                    'label'  => strtolower($label),
                    'source' => $attribute['attribute']
                ];
            }
        }

        return $extraFields;
    }

    /**
     * @param $storeId
     *
     * @return array|bool|mixed
     */
    public function getParentAttributes($storeId)
    {
        $enabled = $this->general->getStoreValue(self::XML_PATH_RELATIONS_ENABLED, $storeId);
        if ($enabled) {
            if ($attributes = $this->general->getStoreValue(self::XML_PATH_PARENT_ATTS, $storeId)) {
                $attributes = explode(',', $attributes);
                return $attributes;
            }
        }

        return [];
    }

    /**
     * @return array
     */
    public function getPriceConfig()
    {
        $priceFields = [];
        $priceFields['price'] = 'price';
        $priceFields['final_price'] = 'price';
        $priceFields['sales_price'] = 'sale_price';
        $priceFields['sales_date_range'] = 'sale_price_effective_date';
        $priceFields['currency'] = ' ' . $this->storeManager->getStore()->getCurrentCurrency()->getCode();

        return $priceFields;
    }

    /**
     * @param $storeId
     *
     * @return array
     */
    public function getProductFilters($storeId)
    {
        $filters = [];

        $visibilityFilter = $this->general->getStoreValue(self::XML_PATH_VISBILITY, $storeId);
        if ($visibilityFilter) {
            $visibility = $this->general->getStoreValue(self::XML_PATH_VISIBILITY_OPTIONS, $storeId);
            $filters['visibility'] = explode(',', $visibility);
            $filters['visibility'] = [
                Visibility::VISIBILITY_IN_CATALOG,
                Visibility::VISIBILITY_IN_SEARCH,
                Visibility::VISIBILITY_BOTH,
            ];
        }
        $relations = $this->general->getStoreValue(self::XML_PATH_RELATIONS_ENABLED, $storeId);
        if ($relations) {
            $filters['relations'] = 1;
            array_push($filters['visibility'], Visibility::VISIBILITY_NOT_VISIBLE);
        } else {
            $filters['relations'] = 0;
        }

        $filters['limit'] = (int)$this->general->getStoreValue(self::XML_PATH_LIMIT, $storeId);

        if ($filters['relations'] == 1) {
            $filters['exclude_parent'] = 1;
        }

        $filters['stock'] = $this->general->getStoreValue(self::XML_PATH_STOCK, $storeId);

        $categoryFilter = $this->general->getStoreValue(self::XML_PATH_CATEGORY_FILTER, $storeId);
        if ($categoryFilter) {
            $categoryIds = $this->general->getStoreValue(self::XML_PATH_CATEGORY_IDS, $storeId);
            $filterType = $this->general->getStoreValue(self::XML_PATH_CATEGORY_FILTER_TYPE, $storeId);
            if (!empty($categoryIds) && !empty($filterType)) {
                $filters['category_ids'] = explode(',', $categoryIds);
                $filters['category_type'] = $filterType;
            }
        }

        return $filters;
    }

    /**
     * @param $storeId
     *
     * @return array
     */
    public function getInventoryData($storeId)
    {
        $invAtt = [];
        $enabled = $this->general->getStoreValue(self::XML_PATH_INVENTORY, $storeId);
        if (!$enabled) {
            return $invAtt;
        }
        if ($fields = $this->general->getStoreValue(self::XML_PATH_INVENTORY_DATA, $storeId)) {
            $invAtt['attributes'] = explode(',', $fields);
            $invAtt['attributes'][] = 'is_in_stock';
            if (in_array('manage_stock', $invAtt['attributes'])) {
                $invAtt['attributes'][] = 'use_config_manage_stock';
                $invAtt['config_manage_stock'] = $this->general->getStoreValue(self::XML_PATH_MANAGE_STOCK, $storeId);
            }
            if (in_array('qty_increments', $invAtt['attributes'])) {
                $invAtt['attributes'][] = 'use_config_qty_increments';
                $invAtt['attributes'][] = 'enable_qty_increments';
                $invAtt['attributes'][] = 'use_config_enable_qty_inc';
                $invAtt['config_qty_increments'] = $this->general->getStoreValue(self::XML_PATH_QTY_INCREMENTS,
                    $storeId);
                $invAtt['config_enable_qty_inc'] = $this->general->getStoreValue(self::XML_PATH_QTY_INC_ENABLED,
                    $storeId);
            }
            if (in_array('min_sale_qty', $invAtt['attributes'])) {
                $invAtt['attributes'][] = 'use_config_min_sale_qty';
                $invAtt['config_min_sale_qty'] = $this->general->getStoreValue(self::XML_PATH_MIN_SALES_QTY, $storeId);
            }

            return $invAtt;
        }
        return [];
    }

    /**
     * @param $dataRow
     * @param $product
     * @param $config
     *
     * @return string
     */
    public function reformatData($dataRow, $product, $config)
    {
        if ($categoryData = $this->getCategoryData($product, $config['categories'])) {
            $dataRow = array_merge($dataRow, $categoryData);
        }
        if ($imageData = $this->getImageData($dataRow)) {
            $dataRow = array_merge($dataRow, $imageData);
        }
        if ($deliveryTime = $this->getDeliveryTime($dataRow, $config)) {
            $dataRow = array_merge($dataRow, $deliveryTime);
        }

        return $dataRow;
    }

    /**
     * @param $product
     * @param $categories
     *
     * @return array
     */
    public function getCategoryData($product, $categories)
    {
        $path = [];
        $level = 0;
        foreach ($product->getCategoryIds() as $catId) {
            if (!empty($categories[$catId])) {
                $category = $categories[$catId];
                if (!empty($category['path'])) {
                    $path[] = ['level' => $category['level'], 'path' => implode(' > ', $category['path'])];
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

        if (empty($dataRow['image_link'])) {
            return [];
        }

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
            $countries = @unserialize($config['delivery']);
            foreach ($countries as $country) {
                if (!empty($country[$stock])) {
                    $deliveryTime['delivery_' . strtolower($country['code'])] = $country[$stock];
                }
            }
            return $deliveryTime;
        }

        return false;
    }
}
