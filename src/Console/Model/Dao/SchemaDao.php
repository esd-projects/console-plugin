<?php
namespace ESD\Plugins\Console\Model\Dao;

use ESD\Plugins\Console\Service\Impl\MysqlBuilder;
use ESD\Plugins\Mysql\MysqlException;

class SchemaDao
{
    /**
     * @var MysqlBuilder
     */
    private $builder;

    /**
     * SchemaDao constructor.
     * @param string $pool
     * @throws MysqlException
     * @throws \ReflectionException
     */
    public function __construct(string $pool)
    {
        $this->builder = new MysqlBuilder($pool);
    }

    /**
     * @param array $tables
     * @return array
     */
    public function getTableSchema($tables = []): array
    {
        $tableSchema = $this->builder->getTableSchema($tables);
        return $tableSchema;
    }

    /**
     * @param string $table
     * @return array
     */
    public function getColumnSchema(string $table): array
    {
        $columnSchema = $this->builder->getColumnSchema($table);
        array_walk($columnSchema, function (&$item) {
            $nullable = $item['nullable'] == 'YES' || !$item['nullable'];
            $required = !$nullable && is_null($item['default']);
            $type = $this->builder->getGrammar()->map[$item['type']];
            $pubType = $type;
            $item['required'] = $required;
            $item['type'] = $type;
            $item['pubType'] = $pubType;
            $item['nullable'] = $nullable;
        });
        return $columnSchema;
    }

    /**
     * @return MysqlBuilder
     */
    public function getBuilder(): MysqlBuilder
    {
        return $this->builder;
    }

    /**
     * @param MysqlBuilder $builder
     */
    public function setBuilder(MysqlBuilder $builder): void
    {
        $this->builder = $builder;
    }
}