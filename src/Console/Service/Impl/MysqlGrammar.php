<?php
namespace ESD\Plugins\Console\Service\Impl;

use ESD\Plugins\Console\Service\Grammar;

class MysqlGrammar extends Grammar
{
    /**
     * @var array
     */
    public $map = [
        'tinyint'    => self::INTEGER,
        'bit'        => self::INTEGER,
        'smallint'   => self::INTEGER,
        'mediumint'  => self::INTEGER,
        'int'        => self::INTEGER,
        'integer'    => self::INTEGER,
        'bigint'     => self::INTEGER,
        'float'      => self::FLOAT,
        'double'     => self::FLOAT,
        'real'       => self::FLOAT,
        'decimal'    => self::FLOAT,
        'numeric'    => self::FLOAT,
        'tinytext'   => self::STRING,
        'mediumtext' => self::STRING,
        'longtext'   => self::STRING,
        'longblob'   => self::STRING,
        'blob'       => self::STRING,
        'text'       => self::STRING,
        'varchar'    => self::STRING,
        'string'     => self::STRING,
        'char'       => self::STRING,
        'datetime'   => self::STRING,
        'year'       => self::STRING,
        'date'       => self::STRING,
        'time'       => self::STRING,
        'timestamp'  => self::STRING,
        'enum'       => self::STRING,
        'varbinary'  => self::STRING,
        'json'       => self::STRING,
    ];

    /**
     * Compile the SQL needed to retrieve all table names.
     *
     * @return string
     */
    public function compileGetAllTables(): string
    {
        return 'SHOW FULL TABLES WHERE table_type = \'BASE TABLE\'';
    }

}