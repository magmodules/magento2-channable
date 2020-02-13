<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Service\Order\Items;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magmodules\Channable\Model\Config;
use Magmodules\Channable\Exceptions\CouldNotImportOrder;

/**
 * Class Validate
 *
 * @package Magmodules\Channable\Service\Order\Items
 */
class Validate
{

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var Config
     */
    private $config;

    /**
     * Validate constructor.
     *
     * @param Config                     $config
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        Config $config,
        ProductRepositoryInterface $productRepository
    ) {
        $this->config = $config;
        $this->productRepository = $productRepository;
    }

    /**
     * @param array $items
     *
     * @throws CouldNotImportOrder
     */
    public function execute(array $items)
    {
        if (empty($items)) {
            throw new CouldNotImportOrder(__('No products found in order'));
        }

        foreach ($items as $item) {
            $product = $this->getProduct($item);
            $this->isProductConfigurableType($product);
            $this->doesProductHaveRequiredOption($product);
        }
    }

    /**
     * @param $item
     *
     * @return ProductInterface
     * @throws CouldNotImportOrder
     */
    private function getProduct($item)
    {
        $productId = $item['id'];

        try {
            return $this->productRepository->getById($productId);
        } catch (\Exception $exception) {
            throw new CouldNotImportOrder(
                __('Product "%1" not found in catalog (ID: %2)',
                    !empty($item['title']) ? $item['title'] : __('*unknown*'),
                    !empty($item['id']) ? $item['id'] : __('*unknown*')
                )
            );
        }
    }

    /**
     * @param ProductInterface $product
     *
     * @throws CouldNotImportOrder
     */
    private function isProductConfigurableType(ProductInterface $product)
    {
        if ($product->getTypeId() == 'configurable') {
            throw new CouldNotImportOrder(
                __(
                    'Product "%1" can not be ordered, as this is the configurable parent (ID: %2)',
                    $product->getName(),
                    $product->getId()
                )
            );
        }
    }

    /**
     * @param ProductInterface $product
     *
     * @throws CouldNotImportOrder
     */
    private function doesProductHaveRequiredOption(ProductInterface $product)
    {
        $options = $product->getRequiredOptions();
        if (!empty($options)) {
            throw new CouldNotImportOrder(
                __(
                    'Product "%1" has required options, this is not supported (ID: %2)',
                    $product->getName(),
                    $product->getId()
                )
            );
        }
    }

}