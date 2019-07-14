<?php

namespace ESD\Plugins\Console\Service\Impl;

use ESD\Core\Server\Server;
use ESD\Plugins\Mysql\MysqlException;
use ESD\Plugins\Mysql\MysqliDb;
use ESD\Plugins\Mysql\MysqlOneConfig;

class MysqlBuilder
{
    /**
     * @var MysqliDb
     */
    private $mysql;

    /**
     * @var MysqlGrammar
     */
    private $grammar;

    /**
     * @var MysqlOneConfig
     */
    private $mysqlConfig;

    /**
     * MysqlBuilder constructor.
     * @param $pool
     * @throws MysqlException
     * @throws \ReflectionException
     */
    public function __construct($pool)
    {
        $configs = Server::$instance->getConfigContext()->get(MysqlOneConfig::key, []);
        if (!isset($configs[$pool])) {
            throw new MysqlException("The pool[{$pool}] not found!");
        }
        $mysqlOneConfig = new MysqlOneConfig('', '', '', '');
        $mysqlOneConfig->setName($pool);
        $mysqlOneConfig = $mysqlOneConfig->buildFromConfig($configs[$pool]);
        $this->mysql = new MysqliDb($mysqlOneConfig->buildConfig());
        $this->mysqlConfig = $mysqlOneConfig;
        $this->grammar = new MysqlGrammar();
    }

    /**
     * @return string
     */
    public function getDatabaseName () {
        return $this->mysqlConfig->getDb();
    }

    /**
     * @param string $table
     * @return array
     */
    public function getColumnSchema(string $table) {
        $table = trim($table);
        if (strpos($table, $this->mysqlConfig->getPrefix()) !== 0) {
            $table = "`{$this->mysqlConfig->getPrefix()}{$table}`";
        }
        return $this->mysql
            ->where('table_schema', $this->getDatabaseName())
            ->where('table_name', $table)
            ->get('information_schema.columns', null, [
                'COLUMN_NAME as `name`',
                'DATA_TYPE as `type`',
                'COLUMN_DEFAULT as `default`',
                'COLUMN_KEY as `key`',
                'IS_NULLABLE as `nullable`',
                'COLUMN_TYPE as `columnType`',
                'COLUMN_COMMENT as `columnComment`',
                'CHARACTER_MAXIMUM_LENGTH as `length`',
                'EXTRA as extra'
            ]);
    }

    /**
     * @param array $tables
     * @return array
     */
    public function getTableSchema(array $tables) {
        array_walk($tables, function (&$table) {
            $table = trim($table);
            if (strpos($table, $this->mysqlConfig->getPrefix()) !== 0) {
                $table = "`{$this->mysqlConfig->getPrefix()}{$table}`";
            }
        });
        $query = $this->mysql
            ->where('table_schema', $this->getDatabaseName())
            ->where('table_type', 'BASE TABLE');
        if ($tables) {
            $query->where('table_name', $tables, 'in');
        } else {
            $query->where('table_name', $this->mysqlConfig->getPrefix() . '%', 'like');
        }
        return $query->get('information_schema.tables', null, [
            'TABLE_NAME as name', 'TABLE_COMMENT as comment'
        ]);
    }

    /**
     * @return MysqlGrammar
     */
    public function getGrammar(): MysqlGrammar
    {
        return $this->grammar;
    }

    /**
     * @param MysqlGrammar $grammar
     */
    public function setGrammar(MysqlGrammar $grammar): void
    {
        $this->grammar = $grammar;
    }

    /**
     * @return MysqlOneConfig
     */
    public function getMysqlConfig(): MysqlOneConfig
    {
        return $this->mysqlConfig;
    }

    /**
     * @param MysqlOneConfig $mysqlConfig
     */
    public function setMysqlConfig(MysqlOneConfig $mysqlConfig): void
    {
        $this->mysqlConfig = $mysqlConfig;
    }

}