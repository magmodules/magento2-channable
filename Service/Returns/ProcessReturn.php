<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Returns;

use Exception;
use Magmodules\Channable\Api\Returns\RepositoryInterface as ReturnsRepository;

/**
 * Class ProcessReturn
 */
class ProcessReturn
{

    /**
     * @var ReturnsRepository
     */
    private $returnsRepository;

    /**
     * ProcessReturn constructor.
     * @param ReturnsRepository $returnsRepository
     */
    public function __construct(
        ReturnsRepository $returnsRepository
    ) {
        $this->returnsRepository = $returnsRepository;
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function execute(array $params): array
    {
        $result = [];

        if (empty($params['id'])) {
            $result['status'] = 'error';
            $result['msg'] = __('Id missing');
            return $result;
        }

        if (empty($params['type'])) {
            $result['status'] = 'error';
            $result['msg'] = __('Type missing');
            return $result;
        }

        try {
            $return = $this->returnsRepository->get((int)$params['id']);
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'msg' => $e->getMessage()
            ];
        }

        try {
            $return->setStatus($params['type']);
            $this->returnsRepository->save($return);
            $result['status'] = 'success';
            $result['msg'] = __('Return processed, new status: %1', $params['type']);
        } catch (Exception $e) {
            $result['status'] = 'error';
            $result['msg'] = $e->getMessage();
        }

        return $result;
    }
}
