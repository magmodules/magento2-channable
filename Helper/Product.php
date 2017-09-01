<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Catalog\Model\Product\Gallery\ReadHandler as GalleryReadHandler;
use Magento\Catalog\Helper\Image as ProductImageHelper;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Framework\Filter\FilterManager;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Bundle\Model\Product\Type as Bundle;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;

class Product extends AbstractHelper
{

    private $eavConfig;
    private $filter;
    private $catalogProductTypeConfigurable;
    private $catalogProductTypeGrouped;
    private $catalogProductTypeBundle;
    private $attributeSet;
    private $productImageHelper;
    private $galleryReadHandler;

    /**
     * Product constructor.
     *
     * @param Context                         $context
     * @param GalleryReadHandler              $galleryReadHandler
     * @param ProductImageHelper              $productImageHelper
     * @param EavConfig                       $eavConfig
     * @param FilterManager                   $filter
     * @param AttributeSetRepositoryInterface $attributeSet
     * @param Grouped                         $catalogProductTypeGrouped
     * @param Bundle                          $catalogProductTypeBundle
     * @param Configurable                    $catalogProductTypeConfigurable
     */
    public function __construct(
        Context $context,
        GalleryReadHandler $galleryReadHandler,
        ProductImageHelper $productImageHelper,
        EavConfig $eavConfig,
        FilterManager $filter,
        AttributeSetRepositoryInterface $attributeSet,
        Grouped $catalogProductTypeGrouped,
        Bundle $catalogProductTypeBundle,
        Configurable $catalogProductTypeConfigurable
    ) {
        $this->galleryReadHandler = $galleryReadHandler;
        $this->productImageHelper = $productImageHelper;
        $this->eavConfig = $eavConfig;
        $this->filter = $filter;
        $this->attributeSet = $attributeSet;
        $this->catalogProductTypeConfigurable = $catalogProductTypeConfigurable;
        $this->catalogProductTypeGrouped = $catalogProductTypeGrouped;
        $this->catalogProductTypeBundle = $catalogProductTypeBundle;
        parent::__construct($context);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Catalog\Model\Product $parent
     * @param                                $config
     *
     * @return array
     */
    public function getDataRow($product, $parent, $config)
    {
        $dataRow = [];

        if (!$this->validateProduct($product, $parent, $config)) {
            return $dataRow;
        }

        foreach ($config['attributes'] as $type => $attribute) {
            $simple = '';
            $productData = $product;
            if ($attribute['parent'] && $parent) {
                $productData = $parent;
                $simple = $product;
            }
            if (($attribute['parent'] == 2) && !$parent) {
                continue;
            }
            if (!empty($attribute['source']) || ($type == 'image_link')) {
                $dataRow[$attribute['label']] = $this->getAttributeValue(
                    $type,
                    $attribute,
                    $config,
                    $productData,
                    $simple
                );
            }
            if (!empty($attribute['static'])) {
                $dataRow[$attribute['label']] = $attribute['static'];
            }
            if (!empty($attribute['config'])) {
                $dataRow[$attribute['label']] = $config[$attribute['config']];
            }
            if (!empty($attribute['condition'])) {
                $dataRow[$attribute['label']] = $this->getCondition(
                    $dataRow[$attribute['label']],
                    $attribute['condition']
                );
            }
            if (!empty($attribute['collection'])) {
                if ($dataCollection = $this->getAttributeCollection($type, $config, $productData)) {
                    $dataRow = array_merge($dataRow, $dataCollection);
                }
            }
        }

        return $dataRow;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Catalog\Model\Product $parent
     * @param                                $config
     *
     * @return bool
     */
    public function validateProduct($product, $parent, $config)
    {
        $filters = $config['filters'];
        if (!empty($filters['exclude_parent'])) {
            if ($product->getTypeId() == 'configurable') {
                return false;
            }
        }
        if (!empty($parent)) {
            if ($parent->getStatus() == Status::STATUS_DISABLED) {
                return false;
            }
            if ($parent->getVisibility() == Visibility::VISIBILITY_NOT_VISIBLE) {
                return false;
            }
            if (!empty($filters['stock'])) {
                if ($parent->getIsInStock() == 0) {
                    return false;
                }
            }
        }

        if ($product->getVisibility() == Visibility::VISIBILITY_NOT_VISIBLE) {
            if (empty($parent)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param                                $type
     * @param                                $attribute
     * @param                                $config
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Catalog\Model\Product $simple
     *
     * @return mixed|string
     */
    public function getAttributeValue($type, $attribute, $config, $product, $simple)
    {
        switch ($type) {
            case 'link':
                $value = $this->getProductUrl($product, $simple, $config);
                break;
            case 'image_link':
                $value = $this->getImage($attribute, $config, $product);
                break;
            case 'attribute_set_id':
                $value = $this->getAttributeSetName($product);
                break;
            case 'manage_stock':
            case 'min_sale_qty':
            case 'qty_increments':
                $value = $this->getStockValue($type, $product, $config['inventory']);
                break;
            default:
                $value = $this->getValue($attribute, $product);
                break;
        }

        if (!empty($value)) {
            if (!empty($attribute['actions']) || !empty($attribute['max'])) {
                $value = $this->getFormat($value, $attribute);
            }
            if (!empty($attribute['suffix'])) {
                if (!empty($config[$attribute['suffix']])) {
                    $value .= $config[$attribute['suffix']];
                }
            }
        } else {
            if (!empty($attribute['default'])) {
                $value = $attribute['default'];
            }
        }
        return $value;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Catalog\Model\Product $simple
     * @param                                $config
     *
     * @return string
     */
    public function getProductUrl($product, $simple, $config)
    {
        $url = '';
        if ($requestPath = $product->getRequestPath()) {
            $url = $config['base_url'] . $requestPath;
        }
        if (!empty($config['utm_code'])) {
            if ($config['utm_code'][0] != '?') {
                $url .= '?' . $config['utm_code'];
            } else {
                $url .= $config['utm_code'];
            }
        }
        if (!empty($simple)) {
            if ($product->getTypeId() == 'configurable') {
                $options = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
                foreach ($options as $option) {
                    if ($id = $simple->getResource()->getAttributeRawValue(
                        $simple->getId(),
                        $option['attribute_code'],
                        $config['store_id']
                    )
                    ) {
                        $urlExtra[] = $option['attribute_id'] . '=' . $id;
                    }
                }
            }
            if (!empty($urlExtra) && !empty($url)) {
                $url = $url . '#' . implode('&', $urlExtra);
            }
        }

        return $url;
    }

    /**
     * @param                                $attribute
     * @param                                $config
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return string
     */
    public function getImage($attribute, $config, $product)
    {
        if (empty($attribute['source'])) {
            $images = [];
            $this->galleryReadHandler->execute($product);
            $galleryImages = $product->getMediaGalleryImages();

            foreach ($galleryImages as $image) {
                if (empty($image['disabled'])) {
                    $images[] = $image['url'];
                }
            }

            return $images;
        } else {
            $img = '';
            if (!empty($attribute['resize'])) {
                $source = $attribute['source'];
                $size = $attribute['resize'];
                return $this->getResizedImage($product, $source, $size);
            }
            if ($url = $product->getData($attribute['source'])) {
                $img = $config['url_type_media'] . 'catalog/product' . $url;
            }

            return $img;
        }
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param                                $source
     * @param                                $size
     *
     * @return string
     */
    public function getResizedImage($product, $source, $size)
    {
        $size = explode('x', $size);
        $width = $size[0];
        $height = end($size);

        $imageId = [
            'image'       => 'product_base_image',
            'thumbnail'   => 'product_thumbnail_image',
            'small_image' => 'product_small_image'
        ];

        if (isset($imageId[$source])) {
            $source = $imageId[$source];
        }

        $resizedImage = $this->productImageHelper->init($product, $source)
            ->constrainOnly(true)
            ->keepAspectRatio(true)
            ->keepTransparency(true)
            ->keepFrame(false);

        if ($height > 0 && $width > 0) {
            $resizedImage->resize($width, $height);
        } else {
            $resizedImage->resize($width);
        }

        return $resizedImage->getUrl();
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return mixed
     */
    public function getAttributeSetName($product)
    {
        $attributeSetRepository = $this->attributeSet->get($product->getAttributeSetId());
        return $attributeSetRepository->getAttributeSetName();
    }

    /**
     * @param                                $attribute
     * @param \Magento\Catalog\Model\Product $product
     * @param                                $inventory
     *
     * @return bool
     */
    public function getStockValue($attribute, $product, $inventory)
    {
        if ($attribute == 'manage_stock') {
            if ($product->getData('use_config_manage_stock')) {
                return $inventory['config_manage_stock'];
            } else {
                return $product->getData('manage_stock');
            }
        }
        if ($attribute == 'min_sale_qty') {
            if ($product->getData('use_config_min_sale_qty')) {
                return $inventory['config_min_sale_qty'];
            } else {
                return $product->getData('min_sale_qty');
            }
        }
        if ($attribute == 'qty_increments') {
            if ($product->getData('use_config_enable_qty_inc')) {
                if (!$inventory['config_enable_qty_inc']) {
                    return false;
                }
            } else {
                if (!$product->getData('enable_qty_inc')) {
                    return false;
                }
            }
            if ($product->getData('use_config_qty_increments')) {
                return $inventory['config_qty_increments'];
            } else {
                return $product->getData('qty_increments');
            }
        }

        return '';
    }

    /**
     * @param                                $attribute
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return string
     */
    public function getValue($attribute, $product)
    {
        if ($attribute['type'] == 'select') {
            if ($attr = $product->getResource()->getAttribute($attribute['source'])) {
                $value = $product->getData($attribute['source']);
                $data = $attr->getSource()->getOptionText($value);
                if (!is_array($data)) {
                    return (string)$data;
                }
            }
        }
        if ($attribute['type'] == 'multiselect') {
            if ($attr = $product->getResource()->getAttribute($attribute['source'])) {
                $value_text = [];
                $values = explode(',', $product->getData($attribute['source']));
                foreach ($values as $value) {
                    $value_text[] = $attr->getSource()->getOptionText($value);
                }
                return implode('/', $value_text);
            }
        }
        return $product->getData($attribute['source']);
    }

    /**
     * @param $value
     * @param $attribute
     *
     * @return mixed|string
     */
    public function getFormat($value, $attribute)
    {
        if (!empty($attribute['actions'])) {
            $actions = $attribute['actions'];
            if (in_array('striptags', $actions)) {
                $value = str_replace(["\r", "\n"], "", $value);
                $value = strip_tags($value);
            }
            if (in_array('number', $actions)) {
                if (is_numeric($value)) {
                    $value = number_format($value, 2);
                }
            }
            if (in_array('round', $actions)) {
                if (is_numeric($value)) {
                    $value = round($value);
                }
            }
        }
        if (!empty($attribute['max'])) {
            $value = $this->filter->truncate($value, ['length' => $attribute['max']]);
        }
        return $value;
    }

    /**
     * @param $value
     * @param $conditions
     *
     * @return bool
     */
    public function getCondition($value, $conditions)
    {
        $data = '';
        foreach ($conditions as $condition) {
            $ex = explode(':', $condition);
            if ($ex['0'] == '*') {
                $data = $ex['1'];
            }
            if ($value == $ex['0']) {
                $data = $ex[1];
            }
        }

        return $data;
    }

    /**
     * @param                                $type
     * @param                                $config
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return array
     */
    public function getAttributeCollection($type, $config, $product)
    {
        if ($type == 'price') {
            return $this->getPriceCollection($config, $product);
        }

        return [];
    }

    /**
     * @param                                $config
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return array
     */
    public function getPriceCollection($config, $product)
    {
        $config = $config['price_config'];

        $price = floatval($product->getPriceInfo()->getPrice('regular_price')->getValue());
        $finalPrice = floatval($product->getPriceInfo()->getPrice('final_price')->getValue());
        $specialPrice = floatval($product->getPriceInfo()->getPrice('special_price')->getValue());

        $prices = [];
        $prices[$config['price']] = $this->formatPrice($price, $config);

        if (!empty($config['final_price'])) {
            $prices[$config['final_price']] = $this->formatPrice($finalPrice, $config);
        }

        if (($price > $finalPrice) && !empty($config['sales_price'])) {
            $prices[$config['sales_price']] = $this->formatPrice($finalPrice, $config);
        }

        if (($specialPrice < $price) && !empty($config['sales_date_range'])) {
            if ($product->getSpecialFromDate() && $product->getSpecialToDate()) {
                $from = date('Y-m-d', strtotime($product->getSpecialFromDate()));
                $to = date('Y-m-d', strtotime($product->getSpecialToDate()));
                $prices[$config['sales_date_range']] = $from . '/' . $to;
            }
        }

        if ($price <= 0) {
            if (!empty($product['min_price'])) {
                $prices[$config['price']] = $this->formatPrice($product['min_price'], $config);
            }
        }

        if (!empty($product['min_price']) && !empty($config['min_price'])) {
            $prices[$config['min_price']] = $this->formatPrice($product['min_price'], $config);
        }
        if (!empty($product['max_price']) && !empty($config['max_price'])) {
            $prices[$config['max_price']] = $this->formatPrice($product['max_price'], $config);
        }

        if (!empty($config['discount_perc']) && isset($prices[$config['sales_price']])) {
            $discount = ($prices[$config['sales_price']] - $prices[$config['price']]) / $prices[$config['price']];
            $discount = $discount * -100;
            if ($discount > 0) {
                $prices[$config['discount_perc']] = round($discount, 1) . '%';
            }
        }

        return $prices;
    }

    /**
     * @param $data
     * @param $config
     *
     * @return string
     */
    public function formatPrice($data, $config)
    {
        $price = number_format($data, 2, '.', '');
        if (!empty($config['use_currency']) && ($price >= 0)) {
            $price .= ' ' . $config['currency'];
        }
        return $price;
    }

    /**
     * @param        $attributes
     * @param array  $parentAttributes
     *
     * @return array
     */
    public function addAttributeData($attributes, $parentAttributes)
    {
        foreach ($attributes as $key => $value) {
            if (!empty($value['source'])) {
                $attribute = $this->eavConfig->getAttribute('catalog_product', $value['source']);
                $frontendInput = $attribute->getFrontendInput();
                $attributes[$key]['type'] = $frontendInput;
            }
            if (in_array($key, $parentAttributes)) {
                $attributes[$key]['parent'] = 1;
            } else {
                $parent = (!empty($value['parent']) ? $value['parent'] : 0);
                $attributes[$key]['parent'] = $parent;
            }
        }

        return $attributes;
    }

    /**
     * Return Parent ID from Simple.
     *
     * @param $productId
     *
     * @return bool
     */
    public function getParentId($productId)
    {
        $configIds = $this->catalogProductTypeConfigurable->getParentIdsByChild($productId);
        if (isset($configIds[0])) {
            return $configIds[0];
        }

        $groupedIds = $this->catalogProductTypeGrouped->getParentIdsByChild($productId);
        if (isset($groupedIds[0])) {
            return $groupedIds[0];
        }

        $bundleIds = $this->catalogProductTypeBundle->getParentIdsByChild($productId);
        if (isset($bundleIds[0])) {
            return $bundleIds[0];
        }

        return false;
    }
}
