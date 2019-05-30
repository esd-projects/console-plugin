<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 18-1-22
 * Time: 上午10:59
 */

namespace ESD\Plugins\Console\Command;

use ESD\Core\Context\Context;
use ESD\Plugins\Console\ConsolePlugin;
use ESD\Core\Server\Server;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ReloadCmd extends Command
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
        $this->setName('reload')->setDescription("Reload server");
        $this->addOption('clearCache', "c", InputOption::VALUE_NONE, 'Who do you want to clear cache?');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $serverConfig = Server::$instance->getServerConfig();
        $server_name = $serverConfig->getName();
        $master_pid = exec("ps -ef | grep $server_name-master | grep -v 'grep ' | awk '{print $2}'");
        $manager_pid = exec("ps -ef | grep $server_name-manager | grep -v 'grep ' | awk '{print $2}'");
        if (empty($master_pid)) {
            $io->warning("server $server_name not run");
            return ConsolePlugin::SUCCESS_EXIT;
        }
        if ($input->getOption('clearCache')) {
            $io->note("清除缓存文件");
            $serverConfig = Server::$instance->getServerConfig();
            if (file_exists($serverConfig->getCacheDir() . "/aop")) {
                clearDir($serverConfig->getCacheDir() . "/aop");
            }
            if (file_exists($serverConfig->getCacheDir() . "/di")) {
                clearDir($serverConfig->getCacheDir() . "/di");
            }
            if (file_exists($serverConfig->getCacheDir() . "/proxies")) {
                clearDir($serverConfig->getCacheDir() . "/proxies");
            }
        }
        posix_kill($manager_pid, SIGUSR1);
        $io->success("server $server_name reload");
        return ConsolePlugin::SUCCESS_EXIT;
    }
}