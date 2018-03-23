<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Magmodules\Channable\Model\ItemFactory as ItemFactory;
use Magmodules\Channable\Helper\General\Proxy as GeneralHelper;
use Magento\Framework\App\State as AppState;

/**
 * Class ItemUpdate
 *
 * @package Magmodules\Channable\Console\Command
 */
class ItemUpdate extends Command
{

    /**
     *
     */
    const COMMAND_NAME = 'channable:item:update';
    /**
     * @var ItemFactory
     */
    private $itemFactory;
    /**
     * @var GeneralHelper
     */
    private $generalHelper;
    /**
     * @var AppState
     */
    private $appState;

    /**
     * ItemUpdate constructor.
     *
     * @param ItemFactory   $itemFactory
     * @param GeneralHelper $generalHelper
     * @param AppState      $appState
     */
    public function __construct(
        ItemFactory $itemFactory,
        GeneralHelper $generalHelper,
        AppState $appState
    ) {
        $this->itemFactory = $itemFactory;
        $this->generalHelper = $generalHelper;
        $this->appState = $appState;
        parent::__construct();
    }

    /**
     *  {@inheritdoc}
     */
    public function configure()
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription('Push Invalidated Items to Channable');
        $this->addOption(
            'store-id',
            null,
            InputOption::VALUE_OPTIONAL,
            'Store ID, if not specified all enabled stores will be processed'
        );
        parent::configure();
    }

    /**
     *  {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $storeId = $input->getOption('store-id');
        $this->appState->setAreaCode('frontend');
        $itemModel = $this->itemFactory->create();

        if (empty($storeId) || !is_numeric($storeId)) {
            $output->writeln('<info>Running All Stores</info>');
            $storeIds = $this->generalHelper->getEnabledArray('magmodules_channable_marketplace/item/enable');
            foreach ($storeIds as $storeId) {
                $result = $itemModel->updateByStore($storeId);
                $msg = sprintf(
                    'Store ID: %s - %s - Products: %s',
                    $storeId,
                    $result['status'],
                    $result['qty']
                );
                $output->writeln($msg);
            }
        } else {
            $output->writeln('<info>Running Store ' . $storeId . '</info>');
            $result = $itemModel->updateByStore($storeId);
            $msg = sprintf(
                'Store ID: %s - %s - Products: %s',
                $storeId,
                $result['status'],
                $result['qty']
            );
            $output->writeln($msg);
        }
    }
}
