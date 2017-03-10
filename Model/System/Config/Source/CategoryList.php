<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;

class CategoryList implements ArrayInterface
{

    protected $categoryFactory;
    protected $categoryCollectionFactory;

    /**
     * CategoryList constructor.
     *
     * @param \Magento\Catalog\Model\CategoryFactory                          $categoryFactory
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        CategoryFactory $categoryFactory,
        CollectionFactory $categoryCollectionFactory
    ) {
        $this->categoryFactory = $categoryFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $arr = $this->toArray();
        $ret = [];

        foreach ($arr as $key => $value) {
            $ret[] = [
                'value' => $key,
                'label' => $value
            ];
        }

        return $ret;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $categories = $this->getCategoryCollection();

        $categoryList = [];
        foreach ($categories as $category) {
            $categoryList[$category->getEntityId()] = [
                'name' => $category->getName(),
                'path' => $category->getPath()
            ];
        }

        $catagoryArray = [];
        foreach ($categoryList as $k => $v) {
            if ($path = $this->getCategoryPath($v['path'], $categoryList)) {
                $catagoryArray[$k] = $path;
            }
        }

        asort($catagoryArray);

        return $catagoryArray;
    }

    /**
     * @return mixed
     */
    public function getCategoryCollection()
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect(['path', 'name']);

        return $collection;
    }

    /**
     * @param $path
     * @param $categoryList
     *
     * @return string
     */
    public function getCategoryPath($path, $categoryList)
    {
        $categoryPath = [];
        $rootCats = [1, 2];
        $path = explode('/', $path);

        if ($path) {
            foreach ($path as $catId) {
                if (!in_array($catId, $rootCats)) {
                    if (!empty($categoryList[$catId]['name'])) {
                        $categoryPath[] = $categoryList[$catId]['name'];
                    }
                }
            }
        }

        if (!empty($categoryPath)) {
            return implode(' » ', $categoryPath);
        }

        return false;
    }
}
