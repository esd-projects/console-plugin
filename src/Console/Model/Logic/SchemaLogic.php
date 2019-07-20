<?php
namespace ESD\Plugins\Console\Model\Logic;

use ESD\Plugins\Console\FileGen;
use ESD\Plugins\Console\Model\Dao\SchemaDao;
use ESD\Core\Exception;
use Symfony\Component\Console\Style\SymfonyStyle;

class SchemaLogic
{
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    /**
     * @var SchemaDao
     */
    protected $schemaDao;
    /**
     * @var SymfonyStyle
     */
    protected $io;
    /**
     * SchemaLogic constructor.
     * @param string $pool
     * @param SymfonyStyle $io
     * @throws \ESD\Plugins\Mysql\MysqlException
     * @throws \ReflectionException
     */
    public function __construct(string $pool, SymfonyStyle $io)
    {
        $this->schemaDao = new SchemaDao($pool);
        $this->io = $io;
    }
    public function alias ($alias) {
        if (strpos($alias, '@app') === 0) {
            return str_replace('@app', ROOT_DIR . '/src', $alias);
        }
        if (strpos($alias, '@elite') === 0) {
            return str_replace('@elite', ROOT_DIR . '/src', $alias);
        }
        if (strpos($alias, '@res') === 0) {
            return str_replace('@res', RES_DIR, $alias);
        }
        if (strpos($alias, '@devtool') === 0) {
            return str_replace('@devtool', dirname(__DIR__, 2), $alias);
        }
        return $alias;
    }

    /**
     * @param string $path
     * @param string $template
     * @param array $tables
     * @param string $extendClass
     * @param bool $confirm
     * @throws Exception
     */
    public function create ($path = '', $template = '', array $tables = [], string $extendClass = '', $confirm = false) {
        $template = $this->alias($template);
        if (!is_dir($template)) {
            throw new Exception("Template[{$template}] not found");
        }
        if ($path{0} === '@') {
            $namespace = ucfirst(substr($path, 1));
            $namespace = str_replace('/', '\\', $namespace);
        } else {
            $namespace = 'App\\Model\\Entity';
        }
        $path = $this->alias($path);
        if (!is_dir($path)) mkdir($path, 0777, true);
        $tables = $this->schemaDao->getTableSchema($tables);
        foreach ($tables as $table) {
            if ($confirm || $this->io->confirm("Generate table {$table['name']}?", true)) {
                $this->generateEntity($path, $template, $table, $namespace, $extendClass);
            }
        }
        $this->io->success('Done!');
    }

    /**
     * @param string $path
     * @param string $template
     * @param array $table
     * @param string $namespace
     * @param string $extendClass
     * @return string
     * @throws \Exception
     */
    public function generateEntity (string $path, string $template, array $table, string $namespace, string $extendClass) {
        $columnSchemas = $this->schemaDao->getColumnSchema($table['name']);
        $genProperties = [];
        $genTranslates = [];
        $primary = '';
        foreach ($columnSchemas as $columnSchema) {
            if ($columnSchema['key'] === 'PRI') $primary = $columnSchema['name'];
            $genProperties[] = $this->generateProperties($columnSchema, $template);
            $genTranslates[] = "            '{$columnSchema['name']}' => '{$columnSchema['columnComment']}',";
        }
        $prefix = $this->schemaDao->getBuilder()->getMysqlConfig()->getPrefix();
        $tableName = substr($table['name'], strlen($prefix));
        $entityName = str_replace(['-', '_'], ' ', $tableName);
        $entityName = str_replace(' ', '', ucwords($entityName));
        $file = sprintf('%s/%s.php', $path, $entityName);
        $data = [
            'entityName' => $entityName,
            'namespace' => $namespace,
            'extendClass' => $extendClass,
            'tableComment' => $table['comment'],
            'tableName' => $tableName,
            'primaryKey' => $primary,
            'properties' => implode(PHP_EOL, $genProperties),
            'translates' => implode(PHP_EOL, $genTranslates),
        ];
        $gen = new FileGen($template, 'entity');
//        return $gen->render($data);
        return $gen->renderAs($file, $data);
    }
    /**
     * @param $columnSchema
     * @param $template
     * @return string
     * @throws \Exception
     */
    public function generateProperties($columnSchema, $template) {
        $validated = [];
        if ($columnSchema['required'] &&
            $columnSchema['extra'] !== 'auto_increment' &&
            !in_array($columnSchema['name'], [
                self::CREATED_AT,
                self::UPDATED_AT
            ])) $validated[] = 'required=true';
        $validated[] = "{$columnSchema['pubType']}=true";
        if ($columnSchema['length']) $validated[] = "max={$columnSchema['length']}";
        $propertyName = $columnSchema['name'];
        for ($i = 0; $i < strlen($propertyName); $i++) {
            if ($propertyName[$i] === '_') {
                $propertyName[$i+1] = strtoupper($propertyName[$i+1]);
            }
        }
        $propertyName = str_replace('_', '', $propertyName);
        $data = [
            'type' => $columnSchema['type'],
            'validated' => implode(', ', $validated),
            'comment' => $columnSchema['columnComment'],
            'propertyName' => $propertyName
        ];
        $gen = new FileGen($template, 'property');
        return $gen->render($data);
    }
}