<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\Console\Cli;
use Magmodules\Channable\Service\Order\ImportSimulator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State as AppState;

/**
 * CLI command to simulate an order import
 */
class OrderSimulate extends Command
{

    /**
     * Names of input arguments or options
     */
    private const INPUT_KEY_STORE_ID = 'store-id';
    private const INPUT_KEY_PRODUCT_ID = 'product-id';
    private const INPUT_KEY_PRODUCT_QTY = 'qty';
    private const INPUT_KEY_COUNTRY_CODE = 'country-code';
    private const INPUT_KEY_LVB = 'lvb';

    /**
     * Command call name
     */
    private const COMMAND_NAME = 'channable:order:simulate';

    /**
     * @var ImportSimulator
     */
    private $importSimulator;
    /**
     * @var AppState
     */
    private $appState;

    /**
     * @param ImportSimulator $importSimulator
     * @param AppState $appState
     */
    public function __construct(
        ImportSimulator $importSimulator,
        AppState $appState
    ) {
        $this->importSimulator = $importSimulator;
        $this->appState = $appState;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    public function configure()
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription('Simulate an order import');
        $this->addOption(
            self::INPUT_KEY_STORE_ID,
            null,
            InputOption::VALUE_REQUIRED,
            'store id of the store where the order should be imported'
        );
        $this->addOption(
            self::INPUT_KEY_PRODUCT_ID,
            null,
            InputOption::VALUE_OPTIONAL,
            'product id of the product that should be used for the order'
        );
        $this->addOption(
            self::INPUT_KEY_PRODUCT_QTY,
            null,
            InputOption::VALUE_OPTIONAL,
            'product qty that should be used for the order'
        );
        $this->addOption(
            self::INPUT_KEY_COUNTRY_CODE,
            null,
            InputOption::VALUE_OPTIONAL,
            'country code for billing and shipping address'
        );
        $this->addOption(
            self::INPUT_KEY_LVB,
            null,
            InputOption::VALUE_OPTIONAL,
            'should order be treated as lvd (1) or not (0)'
        );
        $this->addOption(
            self::INPUT_KEY_LVB,
            null,
            InputOption::VALUE_OPTIONAL,
            'should order be treated as lvd (1) or not (0)'
        );

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption(self::INPUT_KEY_STORE_ID)) {
            throw new \InvalidArgumentException('Please add ' . self::INPUT_KEY_STORE_ID . ' param.');
        } else {
            $storeId = (int)$input->getOption(self::INPUT_KEY_STORE_ID);
        }

        try {
            $this->appState->setAreaCode(Area::AREA_FRONTEND);
            $order = $this->importSimulator->execute(
                $storeId,
                $this->getOptions($input)
            );

            $output->writeln(
                sprintf('<info>Test order #%s created</info>', $order->getIncrementId())
            );
            return Cli::RETURN_SUCCESS;
        } catch (\Exception $exception) {
            $output->writeln(
                sprintf('<error>%s</error>', $exception->getMessage())
            );
            return Cli::RETURN_FAILURE;
        }
    }

    /**
     * Process input options to array
     *
     * @param InputInterface $input
     * @return array
     */
    private function getOptions(InputInterface $input): array
    {
        $options = [];

        if ($productId = $input->getOption(self::INPUT_KEY_PRODUCT_ID)) {
            $options['product_id'] = $productId;
        }

        if ($qty = $input->getOption(self::INPUT_KEY_PRODUCT_QTY)) {
            $options['qty'] = (float)$qty;
        }

        if ($lvb = $input->getOption(self::INPUT_KEY_LVB)) {
            $options['lvb'] = (boolean)$lvb;
        }

        if ($country = $input->getOption(self::INPUT_KEY_COUNTRY_CODE)) {
            $options['country'] = $country;
        }

        return $options;
    }
}
