<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Catalog\Model\Product\Gallery\ReadHandler as GalleryReadHandler;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\Media\Config as CatalogProductMediaConfig;
use Magento\Catalog\Helper\Image as ProductImageHelper;
use Magento\Framework\Filter\FilterManager;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableResource;
use Magento\GroupedProduct\Model\ResourceModel\Product\Link as GroupedResource;
use Magento\Bundle\Model\ResourceModel\Selection as BundleResource;
use Magmodules\Channable\Api\Log\RepositoryInterface as LogRepository;
use Magmodules\Channable\Service\Product\InventoryData;
use Magmodules\Channable\Service\Product\MediaData;
use Magmodules\Channable\Service\Product\PriceData;

/**
 * @deprecated These helper classes will be removed in a future release.
 */
class Product extends AbstractHelper
{

    /**
     * @var EavConfig
     */
    private $eavConfig;
    /**
     * @var FilterManager
     */
    private $filter;
    /**
     * @var ConfigurableResource
     */
    private $catalogProductTypeConfigurable;
    /**
     * @var GroupedResource
     */
    private $catalogProductTypeGrouped;
    /**
     * @var BundleResource
     */
    private $catalogProductTypeBundle;
    /**
     * @var AttributeSetRepositoryInterface
     */
    private $attributeSet;
    /**
     * @var ProductImageHelper
     */
    private $productImageHelper;
    /**
     * @var InventoryData
     */
    private $inventoryData;
    /**
     * @var GalleryReadHandler
     */
    private $galleryReadHandler;
    /**
     * @var CatalogProductMediaConfig
     */
    private $catalogProductMediaConfig;
    /**
     * @var MediaData
     */
    private $mediaData;
    /**
     * @var PriceData
     */
    private $priceData;
    /**
     * @var LogRepository
     */
    private $logger;
    /**
     * Array to save attribute options value
     * @var array
     */
    private $attributeOptions = [];

    public function __construct(
        Context $context,
        GalleryReadHandler $galleryReadHandler,
        CatalogProductMediaConfig $catalogProductMediaConfig,
        ProductImageHelper $productImageHelper,
        EavConfig $eavConfig,
        FilterManager $filter,
        AttributeSetRepositoryInterface $attributeSet,
        GroupedResource $catalogProductTypeGrouped,
        BundleResource $catalogProductTypeBundle,
        ConfigurableResource $catalogProductTypeConfigurable,
        InventoryData $inventoryData,
        MediaData $mediaData,
        PriceData $priceData,
        LogRepository $logger
    ) {
        $this->galleryReadHandler = $galleryReadHandler;
        $this->catalogProductMediaConfig = $catalogProductMediaConfig;
        $this->productImageHelper = $productImageHelper;
        $this->eavConfig = $eavConfig;
        $this->filter = $filter;
        $this->attributeSet = $attributeSet;
        $this->catalogProductTypeConfigurable = $catalogProductTypeConfigurable;
        $this->catalogProductTypeGrouped = $catalogProductTypeGrouped;
        $this->catalogProductTypeBundle = $catalogProductTypeBundle;
        $this->inventoryData = $inventoryData;
        $this->mediaData = $mediaData;
        $this->priceData = $priceData;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Catalog\Model\Product $parent
     * @param                                $config
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getDataRow($product, $parent, $config)
    {
        $dataRow = [];

        $product = $this->inventoryData->addDataToProduct($product, $config);

        if (!$this->validateProduct($product, $parent, $config)) {
            return $dataRow;
        }

        foreach ($config['attributes'] as $type => $attribute) {
            $simple = null;
            $productData = $product;
            if ($parent) {
                $parentTypeId = $parent->getTypeId();
                if (isset($attribute['parent'][$parentTypeId])) {
                    if ($attribute['parent'][$parentTypeId] > 0) {
                        $productData = $parent;
                        $simple = $product;
                    }
                }
            }
            if (($attribute['parent']['simple'] == 2) && !$parent) {
                continue;
            }

            if (empty($attribute['label'])) {
                continue;
            }

            $value = null;

            if (!empty($attribute['source']) || ($type == 'image_link')) {
                $value = $this->getAttributeValue(
                    $type,
                    $attribute,
                    $config,
                    $productData,
                    $simple
                );
            }
            if (!empty($attribute['static'])) {
                $value = $attribute['static'];
            }

            if (!empty($attribute['config'])) {
                $value = $config[$attribute['config']];
            }

            if (!empty($attribute['condition'])) {
                $value = $this->getCondition(
                    $attribute['condition'],
                    $productData,
                    $attribute
                );
            }

            if (!empty($attribute['collection'])) {
                if ($dataCollection = $this->getAttributeCollection($type, $config, $productData)) {
                    $dataRow = array_merge($dataRow, $dataCollection);
                }
            }

            if (!empty($dataRow[$attribute['label']])) {
                if ($value != null) {
                    if (is_array($dataRow[$attribute['label']])) {
                        $dataRow[$attribute['label']][] = $value;
                    } else {
                        $data = [$dataRow[$attribute['label']], $value];
                        unset($dataRow[$attribute['label']]);
                        $dataRow[$attribute['label']] = $data;
                    }
                }
            } else {
                $dataRow[$attribute['label']] = $value;
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
        if (!empty($parent)) {
            if (!empty($filters['stock'])) {
                if (!$this->getIsInStock($parent, $config)) {
                    return false;
                }
            }
        }

        $visibilityFilter = $config['filters']['visibility'] ?? [];
        if (!empty($visibilityFilter) && in_array($product->getVisibility(), $visibilityFilter)) {
            return true;
        }

        if ($product->getVisibility() == Visibility::VISIBILITY_NOT_VISIBLE) {
            if (empty($parent)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \Magento\Catalog\Model\Product $parent
     * @param                                $config
     *
     * @return bool
     */
    public function getIsInStock($parent, $config): bool
    {
        if (!$this->getManageStock($parent, $config)){
            return true;
        }

        return (bool)$parent->getIsInStock();

    }

    /**
     * @param \Magento\Catalog\Model\Product $parent
     * @param                                $config
     *
     * @return bool
     */
    public function getManageStock($parent, $config): bool
    {
        if ($parent->getUseConfigManageStock()) {
            return (bool)$config['inventory']['config_manage_stock'];
        }

        return (bool)$parent->getManageStock();
    }
    /**
     * @param                                $type
     * @param                                $attribute
     * @param                                $config
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Catalog\Model\Product $simple
     *
     * @return mixed|string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getAttributeValue($type, $attribute, $config, $product, $simple)
    {

        if (!empty($attribute['source']) && ($attribute['source'] == 'attribute_set_name')) {
            $type = 'attribute_set_name';
        }
        if (!empty($attribute['source']) && ($attribute['source'] == 'related_skus')) {
            $type = 'related_skus';
        }
        if (!empty($attribute['source']) && ($attribute['source'] == 'upsell_skus')) {
            $type = 'upsell_skus';
        }
        if (!empty($attribute['source']) && ($attribute['source'] == 'crosssell_skus')) {
            $type = 'crosssell_skus';
        }
        if (!empty($attribute['source']) && ($attribute['source'] == 'category_ids')) {
            $type = 'category_ids';
        }
        if (!empty($attribute['source']) && ($attribute['source'] == 'tier_price')) {
            $type = 'tier_price';
        }

        switch ($type) {
            case 'link':
                $value = $this->getProductUrl($product, $simple, $config);
                break;
            case 'image_link':
                $value = $this->getImage($attribute, $config, $product, $simple);
                break;
            case 'attribute_set_name':
                $value = $this->getAttributeSetName($product);
                break;
            case 'qty':
                $value = $this->getQtyValue($product);
                break;
            case 'manage_stock':
                $value = $this->getManageStockValue($product, $config['inventory']);
                break;
            case 'min_sale_qty':
                $value = $this->getMinSaleQtyValue($product, $config['inventory']);
                break;
            case 'backorders':
                $value = $this->getBackordersValue($product, $config['inventory']);
                break;
            case 'qty_increments':
                $value = $this->getQtyIncrementsValue($product, $config['inventory']);
                break;
            case 'availability':
                $value = $this->getAvailability($product);
                break;
            case 'related_skus':
            case 'upsell_skus':
            case 'crosssell_skus':
                $value = $this->getProductRelations($type, $product);
                break;
            case 'category_ids':
                $value = $product->getCategoryIds();
                break;
            case 'tier_price':
                $value = $this->priceData->processTierPrice($product, $config);
                break;
            default:
                $value = $this->getValue($attribute, $product);
                break;
        }

        if (!empty($value)) {
            if ((!empty($attribute['actions']) || !empty($attribute['max'])) && !is_array($value)) {
                $value = $this->getFormat($value, $attribute, $config, $product);
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getProductUrl($product, $simple, $config)
    {
        $url = null;
        if ($requestPath = $product->getRequestPath()) {
            $url = $product->getProductUrl();
        } else {
            $url = $config['base_url'] . 'catalog/product/view/id/' . $product->getEntityId();
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
                if (isset($config['filters']['link']['configurable'])) {
                    if ($config['filters']['link']['configurable'] == 2) {
                        $options = $product->getTypeInstance()->getConfigurableAttributesAsArray($product);
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
     * @param \Magento\Catalog\Model\Product $simple
     *
     * @return string|array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getImage($attribute, $config, $product, $simple)
    {
        if ($simple != null) {
            $typeId = $product->getTypeId();
            if (in_array($product->getTypeId(), ['configurable', 'bundle', 'grouped'])) {
                if ($config['filters']['image'][$typeId] == 2) {
                    $imageSimple = $this->getImageData($attribute, $config, $simple);
                    if (!empty($imageSimple)) {
                        return $imageSimple;
                    }
                }

                if ($config['filters']['image'][$typeId] == 3) {
                    $imageSimple = $this->getImageData($attribute, $config, $simple);
                    $imageParent = $this->getImageData($attribute, $config, $product);

                    $images = [];
                    if (is_array($imageSimple)) {
                        $images = $imageSimple;
                    } else {
                        if (!empty($imageSimple)) {
                            $images[] = $imageSimple;
                        }
                    }
                    if (is_array($imageParent)) {
                        $images = array_merge($images, $imageParent);
                    } else {
                        if (!empty($imageParent)) {
                            $images[] = $imageParent;
                        }
                    }

                    return array_unique($images);
                }
                if ($config['filters']['image'][$typeId] == 4) {
                    $imageParent = $this->getImageData($attribute, $config, $product);
                    $imageSimple = $this->getImageData($attribute, $config, $simple);

                    $images = [];
                    if (is_array($imageParent)) {
                        $images = $imageParent;
                    } else {
                        if (!empty($imageParent)) {
                            $images[] = $imageParent;
                        }
                    }
                    if (is_array($imageSimple)) {
                        $images = array_merge($images, $imageSimple);
                    } else {
                        if (!empty($imageSimple)) {
                            $images[] = $imageSimple;
                        }
                    }

                    return array_unique($images);
                }
            }
        }

        return $this->getImageData($attribute, $config, $product);
    }

    /**
     * @param                                $attribute
     * @param                                $config
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return string|array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getImageData($attribute, $config, $product)
    {
        if (empty($attribute['source']) || ($attribute['source'] == 'all')) {
            $images = [];
            if (!empty($attribute['main']) && ($attribute['main'] != 'last')) {
                if ($url = $product->getData($attribute['main'])) {
                    if ($url != 'no_selection') {
                        $images[] = $this->catalogProductMediaConfig->getMediaUrl($url);
                    }
                }
            }

            $galleryImages = $product->getMediaGallery('images');
            if (!$galleryImages) {
                $this->galleryReadHandler->execute($product);
                $galleryImages = $product->getMediaGallery('images');
            }

            foreach ($galleryImages as $image) {
                if (empty($image['disabled']) || !empty($config['inc_hidden_image'])) {
                    $images[] = $this->catalogProductMediaConfig->getMediaUrl($image['file']);
                }
            }
            if (!empty($attribute['main']) && ($attribute['main'] == 'last')) {
                $imageCount = count($images);
                if ($imageCount > 1) {
                    $mainImage = $images[$imageCount - 1];
                    array_unshift($images, $mainImage);
                }
            }

            return array_unique($images);
        } else {
            $img = null;
            if (!empty($attribute['resize'])) {
                $source = $attribute['source'];
                $size = $attribute['resize'];
                return $this->getResizedImage($product, $source, $size);
            }
            if ($url = $product->getData($attribute['source'])) {
                if ($url != 'no_selection') {
                    $img = $this->catalogProductMediaConfig->getMediaUrl($url);
                }
            }

            if (empty($img)) {
                $source = $attribute['source'];
                if ($source == 'image') {
                    $source = 'small_image';
                }
                if ($url = $product->getData($source)) {
                    if ($url != 'no_selection') {
                        $img = $this->catalogProductMediaConfig->getMediaUrl($url);
                    }
                }
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
        $size = explode('x', (string)$size);
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
     * @return string
     */
    public function getAttributeSetName($product)
    {
        static $attributeSets = [];

        try {
            if (!isset($attributeSets[$product->getAttributeSetId()])) {
                $attributeSetName = $this->attributeSet->get($product->getAttributeSetId())->getAttributeSetName();
                $attributeSets[$product->getAttributeSetId()] = $attributeSetName;
            }

            return $attributeSets[$product->getAttributeSetId()];
        } catch (\Exception $e) {
            $this->logger->addErrorLog('getAttributeSetName', $e->getMessage());
        }

        return false;
    }

    /**
     * Get QTY value from product
     *
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return float|null
     */
    private function getQtyValue($product)
    {
        if (in_array($product->getTypeId(), ['bundle','configurable','grouped'])) {
            return null;
        }

        return $product->getQty();
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param [] $inventory
     *
     * @return boolean
     */
    private function getManageStockValue($product, $inventory)
    {
        if ($product->getData('use_config_manage_stock')) {
            return $inventory['config_manage_stock'];
        }
        return $product->getData('manage_stock');
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param [] $inventory
     *
     * @return boolean
     */
    private function getMinSaleQtyValue($product, $inventory)
    {
        if ($product->getData('use_config_min_sale_qty')) {
            if (is_numeric($inventory['config_min_sale_qty'])) {
                return $inventory['config_min_sale_qty'];
            }
        }
        return $product->getData('min_sale_qty');
    }

    /**
     * @param $product
     * @param $inventory
     * @return string
     */
    private function getBackordersValue($product, $inventory): string
    {
        $value = $product->getData('use_config_backorders')
            ? (int)$inventory['config_backorders']
            : (int)$product->getData('backorders');
        return $value > 0 ? 'true' : 'false';
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param [] $inventory
     *
     * @return mixed
     */
    private function getQtyIncrementsValue($product, $inventory)
    {
        if ($product->getData('use_config_enable_qty_inc')) {
            if (!$inventory['config_enable_qty_inc']) {
                return false;
            }
        } else {
            if (!$product->getData('enable_qty_increments')) {
                return false;
            }
        }
        if ($product->getData('use_config_qty_increments')) {
            return $inventory['config_qty_increments'];
        } else {
            return $product->getData('qty_increments');
        }
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return int
     */
    public function getAvailability($product)
    {
        if ($product->getIsSalable() && $product->getIsInStock()) {
            return 1;
        }

        return 0;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param                                $type
     *
     * @return string
     */
    public function getProductRelations($type, $product)
    {
        $products = [];
        if ($type == 'related_skus') {
            $products = $product->getRelatedProducts();
        }
        if ($type == 'upsell_skus') {
            $products = $product->getUpSellProducts();
        }
        if ($type == 'crosssell_skus') {
            $products = $product->getCrossSellProducts();
        }

        $skus = [];
        foreach ($products as $product) {
            $skus[] = $product->getSku();
        }

        return implode(',', $skus);
    }

    /**
     * @param                                $attribute
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return string
     */
    public function getValue($attribute, $product)
    {
        try {
            if ($attribute['type'] == 'media_image') {
                if ($url = $product->getData($attribute['source'])) {
                    return $this->catalogProductMediaConfig->getMediaUrl($url);
                }
            }
            if ($attribute['type'] == 'select') {
                if ($attr = $product->getResource()->getAttribute($attribute['source'])) {
                    $value = $product->getData($attribute['source']);
                    $key = $attr->getStoreId() . $attribute['source'] . $value;
                    if (!isset($this->attributeOptions[$key]) && !array_key_exists($key, $this->attributeOptions)) {
                        $data = $attr->getSource()->getOptionText($value);
                        if (!is_array($data)) {
                            $this->attributeOptions[$key] = (string)$data;
                        }
                    }
                    return $this->attributeOptions[$key];
                }
            }
            if ($attribute['type'] == 'multiselect') {
                if ($attr = $product->getResource()->getAttribute($attribute['source'])) {
                    $value_text = [];
                    $value = (string)$product->getData($attribute['source']);
                    $key = $attr->getStoreId() . $attribute['source'] . $value;
                    if (!isset($this->attributeOptions[$key]) && !array_key_exists($key, $this->attributeOptions)) {
                        $values = explode(',', (string)$product->getData($attribute['source']));
                        foreach ($values as $value) {
                            $value_text[] = $attr->getSource()->getOptionText($value);
                        }
                        $this->attributeOptions[$key] = implode('/', $value_text);
                    }
                    return $this->attributeOptions[$key];
                }
            }
        } catch (\Exception $e) {
            $this->logger->addErrorLog('getValue', $e->getMessage());
        }

        return $product->getData($attribute['source']);
    }

    /**
     * @param $value
     * @param $attribute
     * @param array $config
     * @param $product
     * @return string
     */
    public function getFormat($value, $attribute, array $config, $product): string
    {
        if (!empty($attribute['actions'])) {
            $breaks = [
                '<br>',
                '<br/>',
                '<br />',
                '</p>',
                '</h1>',
                '</h2>',
                '</h3>',
                '</h4>',
                '</h5>',
                '</h6>',
                '<hr>',
                '</hr>',
                '</li>'
            ];

            $actions = $attribute['actions'];
            if (in_array('striptags', $actions)) {
                $value = str_replace(["\r", "\n"], "", $value);
                $value = str_replace($breaks, " ", $value);
                $value = str_replace('  ', ' ', $value);
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
            if (in_array('replacetags', $actions)) {
                $value = str_replace(["\r", "\n"], "", $value);
                $value = str_replace($breaks, " ", '\\' . '\n', $value);
                $value = str_replace('  ', ' ', $value);
                $value = strip_tags($value);
            }
            if (in_array('replacetagsn', $actions)) {
                $value = str_replace(["\r", "\n"], "", $value);
                $value = str_replace("<li>", "- ", $value);
                $value = str_replace($breaks, " ", '\\' . '\n', $value);
                $value = str_replace('  ', ' ', $value);
                $value = strip_tags($value);
            }

            if (preg_grep('/^currency_/', $actions)) {
                $priceConfig = $config['price_config'];
                $priceConfig['currency'] = explode('_', $actions[0])[1];
                $priceConfig['exchange_rate'] = $priceConfig['exchange_rate_' . $priceConfig['currency']] ?? 1;
                $value = $this->priceData->processPrice($product, (float)$value, $priceConfig);
            }
        }
        if (!empty($attribute['max'])) {
            $value = $this->filter->truncate($value, ['length' => $attribute['max']]);
        }

        return rtrim((string)$value);
    }

    /**
     * @param                                $conditions
     * @param \Magento\Catalog\Model\Product $product
     * @param                                $attribute
     *
     * @return string
     */
    public function getCondition($conditions, $product, $attribute)
    {
        $data = null;
        $value = $product->getData($attribute['source']);
        if ($attribute['source'] == 'is_in_stock') {
            $value = $this->getAvailability($product);
        }

        foreach ($conditions as $condition) {
            $ex = explode(':', (string)$condition);
            if ($ex['0'] == '*') {
                $data = str_replace($ex[0] . ':', '', $condition);
            }
            if ($value == $ex['0']) {
                $data = str_replace($ex[0] . ':', '', $condition);
            }
        }

        if (!empty($attribute['multi'])) {
            $attributes = $attribute['multi'];
            foreach ($attributes as $att) {
                $data = str_replace('{{' . $att['source'] . '}}', $this->getValue($att, $product), $data);
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
            return $this->priceData->execute($config, $product);
        }

        return [];
    }

    /**
     * @param        $attributes
     * @param array  $filters
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function addAttributeData($attributes, $filters)
    {
        foreach ($attributes as $key => $value) {
            if (!empty($value['source'])) {
                try {
                    if (!empty($value['multi'])) {
                        $multipleSources = explode(',', (string)$value['multi']);
                        $sourcesArray = [];
                        foreach ($multipleSources as $source) {
                            if (strlen($source)) {
                                $type = $this->eavConfig->getAttribute('catalog_product', $source)->getFrontendInput();
                                $sourcesArray[] = ['type' => $type, 'source' => $source];
                            }
                        }
                        if (!empty($sourcesArray)) {
                            $attributes[$key]['multi'] = $sourcesArray;
                            if ($attributes[$key]['source'] == 'multi' || $attributes[$key]['source'] == 'conditional') {
                                unset($attributes[$key]['source']);
                            }
                        } else {
                            unset($attributes[$key]);
                        }
                    }
                    if (!empty($value['source'])) {
                        $type = $this->eavConfig->getAttribute('catalog_product', $value['source'])->getFrontendInput();
                        $attributes[$key]['type'] = $type;
                    }
                } catch (\Exception $e) {
                    $this->logger->addErrorLog('addAttributeData', $e->getMessage());
                    unset($attributes[$key]);
                }
            }

            if (isset($attributes[$key]['parent'])) {
                unset($attributes[$key]['parent']);
            }

            $attributes[$key]['parent']['simple'] = (!empty($value['parent']) ? $value['parent'] : 0);

            if (isset($attributes[$key])) {
                if (isset($filters['parent_attributes'])) {
                    foreach ($filters['parent_attributes'] as $k => $v) {
                        if (in_array($key, $v)) {
                            $attributes[$key]['parent'][$k] = 1;
                        } else {
                            $parent = (!empty($value['parent']) ? $value['parent'] : 0);
                            $attributes[$key]['parent'][$k] = $parent;
                        }
                    }
                }
            }
        }
        return $attributes;
    }

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $products
     * @param                                                         $config
     *
     * @return array
     */
    public function getParentsFromCollection($products, $config)
    {
        $ids = [];
        $filters = $config['filters'];
        if (!empty($filters['relations'])) {
            foreach ($products as $product) {
                if ($parentIds = $this->getParentId($product, $filters)) {
                    $ids[$product->getEntityId()] = $parentIds;
                }
            }
        }
        return $ids;
    }

    /**
     * Return Parent ID from Simple.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param                                $filters
     *
     * @return bool|string
     */
    public function getParentId($product, $filters)
    {
        $productId = $product->getEntityId();
        $visibility = $product->getVisibility();
        $parentIds = [];

        if (!in_array($product->getTypeId(), ['simple', 'downloadable', 'virtual'])) {
            return false;
        }

        if (in_array('configurable', $filters['relations'])
            && (($visibility == Visibility::VISIBILITY_NOT_VISIBLE) || !in_array(
                    'configurable',
                    $filters['nonvisible']
                ))
        ) {
            $configurableIds = $this->catalogProductTypeConfigurable->getParentIdsByChild($productId);
            if (!empty($configurableIds)) {
                $parentIds = array_merge($parentIds, $configurableIds);
            }
        }

        if (in_array('grouped', $filters['relations'])
            && (($visibility == Visibility::VISIBILITY_NOT_VISIBLE) || !in_array('grouped', $filters['nonvisible']))
        ) {
            $typeId = GroupedResource::LINK_TYPE_GROUPED;
            $groupedIds = $this->catalogProductTypeGrouped->getParentIdsByChild($productId, $typeId);
            if (!empty($groupedIds)) {
                $parentIds = array_merge($parentIds, $groupedIds);
            }
        }

        if (in_array('bundle', $filters['relations'])
            && (($visibility == Visibility::VISIBILITY_NOT_VISIBLE) || !in_array('bundle', $filters['nonvisible']))
        ) {
            $bundleIds = $this->catalogProductTypeBundle->getParentIdsByChild($productId);
            if (!empty($bundleIds)) {
                $parentIds = array_merge($parentIds, $bundleIds);
            }
        }

        return array_unique($parentIds);
    }

    /**
     * @return InventoryData
     */
    public function getInventoryData()
    {
        return $this->inventoryData;
    }

    /**
     * @return MediaData
     */
    public function getMediaData()
    {
        return $this->mediaData;
    }
}
