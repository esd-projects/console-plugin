<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/5/6
 * Time: 15:59
 */

namespace ESD\Plugins\Console;

use ESD\Core\Plugins\Config\BaseConfig;

class ConsoleConfig extends BaseConfig
{
    const key = "console";
    /**
     * @var string[]
     */
    protected $cmdClassList = [];

    public function __construct()
    {
        parent::__construct(self::key);
    }

    /**
     * @return string[]
     */
    public function getCmdClassList(): array
    {
        return $this->cmdClassList;
    }

    /**
     * @param string[] $cmdClassList
     */
    public function setCmdClassList(array $cmdClassList): void
    {
        $this->cmdClassList = $cmdClassList;
    }

    public function addCmdClass(string $className): void
    {
        $list = explode("\\", $className);
        $this->cmdClassList[$list[count($list) - 1]] = $className;
    }

}