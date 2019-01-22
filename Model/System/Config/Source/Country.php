<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model\System\Config\Source;

use Magento\Directory\Model\ResourceModel\Country\CollectionFactory as CountryCollectionFactory;
use Magento\Framework\Option\ArrayInterface;

/**
 * Class Country
 *
 * @package Magmodules\Channable\Model\System\Config\Source
 */
class Country implements ArrayInterface
{

    /**
     * @var CountryCollectionFactory
     */
    private $countryCollectionFactory;

    /**
     * Country constructor.
     *
     * @param CountryCollectionFactory $countryCollectionFactory
     */
    public function __construct(
        CountryCollectionFactory $countryCollectionFactory
    ) {
        $this->countryCollectionFactory = $countryCollectionFactory;
    }

    /**
     * @return mixed
     */
    public function toOptionArray()
    {
        return $this->countryCollectionFactory->create()->toOptionArray('-- ');
    }
}
