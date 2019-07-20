<?php
/**
 * Created by PhpStorm.
 * User: administrato
 * Date: 2019/4/20
 * Time: 10:38
 */

namespace ESD\Plugins\Console;

use ESD\Core\Context\Context;
use ESD\Core\PlugIn\AbstractPlugin;
use ESD\Plugins\Console\Command\EntityCmd;
use ESD\Plugins\Console\Command\ReloadCmd;
use ESD\Plugins\Console\Command\RestartCmd;
use ESD\Plugins\Console\Command\StartCmd;
use ESD\Plugins\Console\Command\StopCmd;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class ConsolePlugin extends AbstractPlugin
{
    const SUCCESS_EXIT = 0;
    const FAIL_EXIT = 1;
    const NOEXIT = -255;
    /**
     * @var Application
     */
    private $application;

    /**
     * @var ConsoleConfig
     */
    private $config;

    /**
     * 获取插件名字
     * @return string
     */
    public function getName(): string
    {
        return "Console";
    }

    /**
     * ConsolePlugin constructor.
     * @param ConsoleConfig|null $config
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \ReflectionException
     */
    public function __construct(?ConsoleConfig $config = null)
    {
        parent::__construct();
        if ($config == null) {
            $config = new ConsoleConfig();
        }
        $this->config = $config;
        $this->application = new Application("EasySwooleDistributed");
        $this->application->setAutoExit(false);
    }

    public function init(Context $context)
    {
        parent::init($context);
        $input = new ArgvInput();
        $output = new ConsoleOutput();
        $this->config->addCmdClass(ReloadCmd::class);
        $this->config->addCmdClass(RestartCmd::class);
        $this->config->addCmdClass(StartCmd::class);
        $this->config->addCmdClass(StopCmd::class);
        $this->config->addCmdClass(EntityCmd::class);
        $this->config->merge();
        $cmds = [];
        foreach ($this->config->getCmdClassList() as $value) {
            $cmd = new $value($context);
            if ($cmd instanceof Command) {
                $cmds[$cmd->getName()] = $cmd;
            }
        }
        $this->application->addCommands($cmds);
        $exitCode = $this->application->run($input, $output);
        if ($exitCode >= 0) {
            \swoole_event_exit();
            exit();
        }
    }

    /**
     * 在服务启动前
     * @param Context $context
     * @return mixed
     * @throws \Exception
     */
    public function beforeServerStart(Context $context)
    {
        return;
    }

    /**
     * 在进程启动前
     * @param Context $context
     * @return mixed
     */
    public function beforeProcessStart(Context $context)
    {
        $this->ready();
    }

    /**
     * @return Application
     */
    public function getApplication(): Application
    {
        return $this->application;
    }
}