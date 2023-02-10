<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Console\Command;

use Magento\Framework\Console\Cli;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magmodules\Channable\Service\Token\Generate;

class GenerateToken extends Command
{

    /**
     * Error message is token is already set
     */
    const TOKEN_ALREADY_SET = 'Token is already set, use --force=1 to refresh token.';

    /**
     * Success message if new token is set
     */
    const TOKEN_SET = 'New token set, please re-authenticate connection.';

    /**
     * Command call name
     */
    const COMMAND_NAME = 'channable:generate:token';

    /**
     * @var Generate
     */
    private $generate;
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @param Generate $generate
     * @param ConfigProvider $configProvider
     */
    public function __construct(
        Generate $generate,
        ConfigProvider $configProvider
    ) {
        $this->generate = $generate;
        $this->configProvider = $configProvider;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    public function configure()
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription('Generate token for Channable connection');
        $this->addOption(
            'force',
            null,
            InputOption::VALUE_OPTIONAL,
            'Use force=1 to refresh token'
        );
        parent::configure();
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $force = (bool)$input->getOption('force');
        if (!$force && $this->configProvider->getToken()) {
            $output->writeln(sprintf('<error>%s</error>', self::TOKEN_ALREADY_SET));
            return Cli::RETURN_FAILURE;
        }

        $this->generate->execute(true);
        $output->writeln(sprintf('<info>%s</info>', self::TOKEN_SET));
        return Cli::RETURN_SUCCESS;
    }
}
