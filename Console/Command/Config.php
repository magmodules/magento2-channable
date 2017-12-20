<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Magmodules\Channable\Helper\Config\Proxy as ConfigHelper;

/**
 * Class Config
 *
 * @package Magmodules\Channable\Console\Command
 */
class Config extends Command
{

    /**
     *
     */
    const COMMAND_NAME = 'channable:config';
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * Config constructor.
     *
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        ConfigHelper $configHelper
    ) {
        $this->configHelper = $configHelper;
        parent::__construct();
    }

    /**
     *  {@inheritdoc}
     */
    public function configure()
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription('Channable Config Commands');
        $this->addOption(
            'run',
            null,
            InputOption::VALUE_REQUIRED,
            'Run Type'
        );
    }

    /**
     *  {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Running Channable Config Command</info>');
        $run = $input->getOption('run');

        switch ($run) {
            case 'update22':
                $result = $this->configHelper->convertSerializedDataToJson();
                $output->writeln('Update Serialized Fields, result: ' . $result);
                break;
            default:
                $output->writeln('No Command found');
                break;
        }
    }
}
