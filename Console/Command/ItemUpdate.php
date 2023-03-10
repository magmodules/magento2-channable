<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Console\Command;

use Magento\Framework\App\State as AppState;
use Magento\Framework\Console\Cli;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;
use Magmodules\Channable\Model\ItemFactory as ItemFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command to execute item updates
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
     * @var ConfigProvider
     */
    private $configProvider;
    /**
     * @var AppState
     */
    private $appState;

    /**
     * @param ItemFactory $itemFactory
     * @param ConfigProvider $configProvider
     * @param AppState $appState
     */
    public function __construct(
        ItemFactory $itemFactory,
        ConfigProvider $configProvider,
        AppState $appState
    ) {
        $this->itemFactory = $itemFactory;
        $this->configProvider = $configProvider;
        $this->appState = $appState;
        parent::__construct();
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->appState->setAreaCode('frontend');
        $itemModel = $this->itemFactory->create();

        foreach ($this->getSelectedStoreIds($input) as $storeId) {
            $output->writeln("<info>Running Store {$storeId}</info>");
            $result = $itemModel->updateAll((int)$storeId);
            $output->writeln(
                sprintf(
                    'Store ID: %s - %s - Products: %s',
                    $storeId,
                    $result[$storeId]['status'] ?? 'Unknown Status',
                    $result[$storeId]['qty'] ?? 0
                )
            );
        }
        return Cli::RETURN_SUCCESS;
    }

    /**
     * @return void
     */
    private function getSelectedStoreIds(InputInterface $input): array
    {
        $storeId = (int)$input->getOption('store-id');
        return $storeId
            ? [$input->getOption('store-id')]
            : $this->configProvider->getItemUpdateStoreIds();
    }
}
