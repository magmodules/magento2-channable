<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Test;

use Magmodules\Channable\Helper\Feed as FeedHelper;
use Magmodules\Channable\Helper\General as GeneralHelper;
use Magmodules\Channable\Model\Generate as GenerateModel;
use Magmodules\Channable\Helper\Source as SourceHelper;

/**
 * ProductFeed test class
 */
class ProductFeed
{

    /**
     * Test type
     */
    const TYPE = 'product_feed_test';

    /**
     * Test description
     */
    const TEST = 'Check if product feeds are enabled';

    /**
     * Visibility
     */
    const VISIBLE = true;

    /**
     * Message on test success
     */
    const SUCCESS_MSG = 'Enabled Store-view(s): %s';

    /**
     * Message on test failed
     */
    const FAILED_MSG = 'No Store-views(s) Enabled';

    /**
     * Expected result
     */
    const EXPECTED = true;

    /**
     * Link to get support
     */
    const SUPPORT_URL = 'https://www.magmodules.eu/help/magento2-channable/channable-magento2-selftest-results';

    /**
     * @var FeedHelper
     */
    private $feedHelper;

    /**
     * Feed constructor.
     *
     * @param FeedHelper $feedHelper
     */
    public function __construct(
        FeedHelper $feedHelper
    ) {
        $this->feedHelper = $feedHelper;
    }

    /**
     * @return array
     */
    public function execute(): array
    {
        $result = [
            'type' => self::TYPE,
            'test' => self::TEST,
            'visible' => self::VISIBLE,
        ];

        $names = [];
        $configData = $this->feedHelper->getConfigData();
        foreach ($configData as $feedData) {
            if ($feedData['status']) {
                $names[] = '"' . $feedData['name'] . '"';
            }
        }

        if (!empty($names)) {
            $result +=
                [
                    'result_msg' => sprintf(self::SUCCESS_MSG, implode(', ', $names)),
                    'result_code' => 'success'
                ];
        } else {
            $result['result_msg'] = self::FAILED_MSG;
            $result +=
                [
                    'result_code' => 'failed',
                    'support_link' => self::SUPPORT_URL
                ];
        }

        return $result;
    }
}
