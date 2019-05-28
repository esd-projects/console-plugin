<?php

use ESD\Core\Server\Config\PortConfig;
use ESD\Core\Server\Config\ServerConfig;
use ESD\Plugins\Console\ConsolePlugin;
use ESD\Server\Co\ExampleClass\DefaultServer;
use ESD\Server\Co\ExampleClass\Port\DefaultPort;
use ESD\Server\Co\ExampleClass\Process\DefaultProcess;

require __DIR__ . '/../vendor/autoload.php';

class MyPort extends DefaultPort
{

}

class MyProcess extends DefaultProcess
{

}

enableRuntimeCoroutine();

//----多端口配置----
$httpPortConfig = new PortConfig();
$httpPortConfig->setHost("0.0.0.0");
$httpPortConfig->setPort(8080);
$httpPortConfig->setSockType(PortConfig::SWOOLE_SOCK_TCP);
$httpPortConfig->setOpenHttpProtocol(true);

$wsPortConfig = new PortConfig();
$wsPortConfig->setHost("0.0.0.0");
$wsPortConfig->setPort(8081);
$wsPortConfig->setSockType(PortConfig::SWOOLE_SOCK_TCP);
$wsPortConfig->setOpenHttpProtocol(true);

//---服务器配置---
$serverConfig = new ServerConfig();
$serverConfig->setWorkerNum(4);
$serverConfig->setRootDir(__DIR__ . "/../");

$server = new DefaultServer($serverConfig);
//添加端口
$server->addPort("http", $httpPortConfig, MyPort::class);//使用自定义实例
$server->addPort("ws", $wsPortConfig);//使用默认实例
//添加插件
$server->getPlugManager()->addPlug(new ConsolePlugin());
//添加进程
$server->addProcess("test1");
$server->addProcess("test2", MyProcess::class);//使用自定义实例
//配置
$server->configure();
//configure后可以获取实例
$test1Process = $server->getProcessManager()->getProcessFromName("test1");
$test2Process = $server->getProcessManager()->getProcessFromName("test2");
$httpPort = $server->getPortManager()->getPortFromName("http");
$wsPort = $server->getPortManager()->getPortFromName("ws");
//启动
$server->start();
