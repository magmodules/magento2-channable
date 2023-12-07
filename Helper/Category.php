<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magmodules\Channable\Helper\General as GeneralHelper;

/**
 * Class Category
 *
 * @package Magmodules\Channable\Helper
 */
class Category extends AbstractHelper
{

    /**
     * @var General
     */
    private $generalHelper;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * Category constructor.
     *
     * @param Context                   $context
     * @param General                   $generalHelper
     * @param StoreManagerInterface     $storeManager
     * @param CategoryCollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        Context $context,
        GeneralHelper $generalHelper,
        StoreManagerInterface $storeManager,
        CategoryCollectionFactory $categoryCollectionFactory
    ) {
        $this->generalHelper = $generalHelper;
        $this->storeManager = $storeManager;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        parent::__construct($context);
    }

    /**
     * @param        $storeId
     * @param string $field
     * @param string $default
     * @param string $exclude
     *
     * @return array
     */
    public function getCollection($storeId, $field = null, $default = null, $exclude = null)
    {
        $data = [];
        $parent = $this->storeManager->getStore($storeId)->getRootCategoryId();
        $attributes = ['name', 'level', 'path', 'is_active'];

        if (!empty($field)) {
            $attributes[] = $field;
        }

        if (!empty($exclude)) {
            $attributes[] = $exclude;
        }

        $collection = $this->categoryCollectionFactory->create()
            ->setStoreId($storeId)
            ->addAttributeToSelect($attributes)
            ->addFieldToFilter('is_active', ['eq' => 1])
            ->addFieldToFilter('path', ['like' => '%/' . $parent . '/%'])
            ->load();

        foreach ($collection as $category) {
            $data[$category->getId()] = [
                'name'    => $category->getName(),
                'level'   => $category->getLevel(),
                'path'    => $category->getPath(),
                'custom'  => (!empty($field) ? $category->getData($field) : ''),
                'exclude' => (!empty($exclude) ? $category->getData($exclude) : 0),
            ];
        }

        $categories = [];
        foreach ($data as $key => $category) {
            $paths = explode('/', (string)$category['path']);
            $pathText = [];
            $custom = $default;
            $level = 0;
            $exclude = 0;
            foreach ($paths as $path) {
                if (!empty($data[$path]['name']) && ($path != $parent)) {
                    $pathText[] = $data[$path]['name'];
                    if (!empty($data[$path]['custom'])) {
                        $custom = $data[$path]['custom'];
                    }
                    if (!empty($data[$path]['exclude'])) {
                        $exclude = 1;
                    }
                    $level++;
                }
            }
            if (!$exclude) {
                $categories[$key] = [
                    'level'  => $level,
                    'path'   => $pathText,
                    'custom' => $custom
                ];
            }
        }

        return $categories;
    }
}
