<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Model\Base;

use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class Metadata
 * extender for metadata
 */
class Metadata
{

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;
    /**
     * @var string
     */
    protected $resourceClassName;
    /**
     * @var string
     */
    protected $modelClassName;

    /**
     * Class Constructor
     *
     * @param ObjectManagerInterface $objectManager
     * @param string $resourceClassName
     * @param string $modelClassName
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        $resourceClassName,
        $modelClassName
    ) {
        $this->objectManager = $objectManager;
        $this->resourceClassName = $resourceClassName;
        $this->modelClassName = $modelClassName;
    }

    /**
     * @return AbstractDb
     */
    public function getMapper(): AbstractDb
    {
        return $this->objectManager->get($this->resourceClassName);
    }

    /**
     * @return ExtensibleDataInterface
     */
    public function getNewInstance(): ExtensibleDataInterface
    {
        return $this->objectManager->create($this->modelClassName);
    }
}
