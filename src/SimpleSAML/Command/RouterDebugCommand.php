<?php

declare(strict_types=1);

namespace SimpleSAML\Command;

use Closure;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\RouterInterface;

use function get_class;
use function implode;
use function is_object;

class RouterDebugCommand extends Command
{
    /**
     * @var string|null
     */
    protected static $defaultName = 'debug:router';


    /**
     * @param \Symfony\Component\Routing\RouterInterface $router
     *
     * @throws \Symfony\Component\Console\Exception\LogicException When the command name is empty
     */
    public function __construct(
        protected RouterInterface $router,
    ) {
        parent::__construct();
    }


    /**
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Displays current routes for a module')
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</info> displays the configured routes for a module:

  <info>php %command.full_name%</info>
EOF,
            )
        ;
    }


    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $routes = $this->router->getRouteCollection();

        $tableHeaders = ['Name', 'Method', 'Scheme', 'Host', 'Path', 'Controller'];

        $tableRows = [];
        foreach ($routes->all() as $name => $route) {
            $row = [
                $name,
                $route->getMethods() ? implode('|', $route->getMethods()) : 'ANY',
                $route->getSchemes() ? implode('|', $route->getSchemes()) : 'ANY',
                '' !== $route->getHost() ? $route->getHost() : 'ANY',
                $route->getPath(),
            ];

            $controller = $route->getDefault('_controller');
            if ($controller instanceof Closure) {
                $controller = 'Closure';
            } elseif (is_object($controller)) {
                $controller = get_class($controller);
            }
            $row[] = $controller;

            $tableRows[] = $row;
        }

        $table = new Table($io);
        $table->setHeaders($tableHeaders)->setRows($tableRows);
        $table->setStyle('compact');
        $table->render();

        return 0;
    }
}
