<?php
/**
 * Created by PhpStorm.
 * User: Elite
 * Date: 2019/7/14
 * Time: 15:48
 */

namespace ESD\Plugins\Console\Command;

use ESD\Plugins\Console\Model\Logic\SchemaLogic;
use ESD\Core\Context\Context;
use ESD\Core\DI\DI;
use ESD\Core\Plugins\Config\ConfigContext;
use ESD\Plugins\Console\ConsolePlugin;
use ESD\Plugins\Mysql\GetMysql;
use ESD\Plugins\Mysql\MysqlConfig;
use ESD\Plugins\Mysql\MysqliDb;
use ESD\Plugins\Mysql\MysqlOneConfig;
use ESD\Plugins\Mysql\MysqlPlugin;
use ESD\Server\Co\Server;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class EntityCmd
 * @package ESD\Plugins\Console\Command
 */
class EntityCmd extends Command
{
    use GetMysql;

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
        $this->setName('entity')->setDescription("Entity generator");
        $this->addArgument('pool', InputArgument::OPTIONAL, 'database db pool?', 'default');
        $this->addOption('table', 't', InputOption::VALUE_OPTIONAL|InputOption::VALUE_IS_ARRAY, 'database table name?', []);
        $this->addOption('path', 'p', InputOption::VALUE_OPTIONAL, 'generate entity file path?', '@app/Model/Entity');
        $this->addOption('template', 'a', InputOption::VALUE_OPTIONAL, '"generate entity template path?', '@devtool/resources');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     * @throws \ESD\Core\Exception
     * @throws \ESD\Plugins\Mysql\MysqlException
     * @throws \ReflectionException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $pool = $input->getArgument('pool');
        $tables = $input->getOption('table');
        $path = $input->getOption('path');
        $tpl = $input->getOption('template');

        $schemaLogic = new SchemaLogic($pool);
        $schemaLogic->create($path, $tpl, $tables);
//        var_dump($c);


//        if (!$table) {

//        }
//
//        $serverConfig = Server::$instance->getServerConfig();
//        $server_name = $serverConfig->getName();
//        $master_pid = exec("ps -ef | grep $server_name-master | grep -v 'grep ' | awk '{print $2}'");
//        if (!empty($master_pid)) {
//            $io->warning("server $server_name is running");
//            return ConsolePlugin::SUCCESS_EXIT;
//        }
//        if ($input->getOption('clearCache')) {
//            $io->note("清除缓存文件");
//            $serverConfig = Server::$instance->getServerConfig();
//            if (file_exists($serverConfig->getCacheDir() . "/aop")) {
//                clearDir($serverConfig->getCacheDir() . "/aop");
//            }
//            if (file_exists($serverConfig->getCacheDir() . "/di")) {
//                clearDir($serverConfig->getCacheDir() . "/di");
//            }
//            if (file_exists($serverConfig->getCacheDir() . "/proxies")) {
//                clearDir($serverConfig->getCacheDir() . "/proxies");
//            }
//        }
//        //是否是守护进程
//        if ($input->getOption('daemonize')) {
//            $serverConfig = Server::$instance->getServerConfig();
//            $serverConfig->setDaemonize(true);
//            $io->note("Input php Start.php stop to quit. Start success.");
//        } else {
//            $io->note("Press Ctrl-C to quit. Start success.");
//        }
        return ConsolePlugin::SUCCESS_EXIT;
    }

    /**
     * @param string $mapping
     * @param bool   $ucFirst
     *
     * @return string
     */
    private function getSafeMappingName(string $mapping, bool $ucFirst = false)
    {
        $mapping = preg_replace("#[^\w|^\u{4E00}-\u{9FA5}]+#is", '', $mapping);
        $first   = $mapping ? mb_substr($mapping, 0, 1) : '';
        if ($first && !preg_match("#[^[A-Za-z_]|^\u{4E00}-\u{9FA5}]+#is", $first)) {
            $value = str_replace(['-', '_'], ' ', $mapping);
            $mapping = str_replace(' ', '', ucwords($value));
            return $ucFirst ? ucfirst($mapping) : $mapping;
        }
        if (empty($first)) {
            return $ucFirst ? 'Db' . mt_rand(1, 100) : 'db' . mt_rand(100, 1000);
        }
        return $ucFirst ? 'Db' . $mapping : 'db' . $mapping;
    }
}