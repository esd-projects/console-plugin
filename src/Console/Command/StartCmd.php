<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 18-1-22
 * Time: 上午10:59
 */

namespace GoSwoole\BaseServer\Plugins\Console\Command;

use GoSwoole\BaseServer\Plugins\Console\ConsolePlugin;
use GoSwoole\BaseServer\Server\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class StartCmd extends Command
{
    /**
     * @var Context
     */
    private $context;

    /**
     * StartCmd constructor.
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        parent::__construct();
        $this->context = $context;
    }

    protected function configure()
    {
        $this->setName('start')->setDescription("Start server");
        $this->addOption('daemonize', "d", InputOption::VALUE_NONE, 'Who do you want daemonize?');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $serverConfig = $this->context->getServer()->getServerConfig();
        $server_name = $serverConfig->getName();
        $master_pid = exec("ps -ef | grep $server_name-master | grep -v 'grep ' | awk '{print $2}'");
        if (!empty($master_pid)) {
            $io->warning("server $server_name is running");
            return ConsolePlugin::SUCCESS_EXIT;
        }
        //是否是守护进程
        if ($input->getOption('daemonize')) {
            $serverConfig = $this->context->getServer()->getServerConfig();
            $serverConfig->setDaemonize(true);
            $io->note("Input php Start.php stop to quit. Start success.");
        } else {
            $io->note("Press Ctrl-C to quit. Start success.");
        }
        return ConsolePlugin::NOEXIT;
    }
}