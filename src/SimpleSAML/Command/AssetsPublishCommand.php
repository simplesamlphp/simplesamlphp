<?php

declare(strict_types=1);

namespace SimpleSAML\Command;

use SimpleSAML\Asset\ModuleAssetPublisher;
use SimpleSAML\Configuration;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'assets:publish',
    description: 'Publish module assets into public/assets.',
)]
final class AssetsPublishCommand extends Command
{
    public function __construct(
        private readonly ModuleAssetPublisher $publisher = new ModuleAssetPublisher(),
    ) {
        parent::__construct();
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $published = $this->publisher->publish(Configuration::getInstance()->getBaseDir());

        if (empty($published)) {
            $io->success('No module assets needed publishing.');
        } else {
            $io->success('Published module assets for: ' . implode(', ', $published));
        }

        return Command::SUCCESS;
    }
}
