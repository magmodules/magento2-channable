<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Returns;

use Magento\Framework\App\ResourceConnection;

/**
 * Class GetReturnStatus
 */
class GetReturnStatus
{

    /**
     * "No such entity" exception text
     */
    public const NO_SUCH_ENTITY_EXCEPTION = 'The return with id "%1" does not exist.';

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * GetReturnStatus constructor.
     * @param ResourceConnection $resource
     */
    public function __construct(
        ResourceConnection $resource
    ) {
        $this->resource = $resource;
    }

    /**
     * @param int $id
     * @return array
     */
    public function execute(int $id): array
    {
        $connection = $this->resource->getConnection();
        $selectReturn = $connection->select()->from(
            $this->resource->getTableName('channable_returns'),
            'status'
        )->where('entity_id = ?', $id);
        $status = $connection->fetchOne($selectReturn);
        if ($status !== false) {
            return [
                'validated' => 'true',
                'return_id' => $id,
                'status' => $status
            ];
        }
        return [
            'validated' => 'false',
            'errors' => __(self::NO_SUCH_ENTITY_EXCEPTION, $id)->render()
        ];
    }
}
