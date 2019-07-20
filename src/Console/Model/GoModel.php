<?php
namespace ESD\Plugins\Console\Model;

use ESD\Plugins\Console\Helper\StringHelper;
use ESD\Plugins\Mysql\GetMysql;
use ESD\Plugins\Mysql\MysqlException;
use ESD\Plugins\Mysql\MysqlManyPool;
use ESD\Plugins\Validate\Annotation\Filter;
use ESD\Plugins\Validate\Annotation\Validated;
use ESD\Plugins\Validate\ValidationException;
use ESD\Psr\Tracing\TracingInterface;
use Inhere\Validate\Validation;

abstract class GoModel extends Validation implements TracingInterface
{
    use GetMysql;

    /**
     * @var array
     */
    private static $modelReflectionClass = [];
    /**
     * @var \ReflectionClass
     */
    private $_reflectionClass;

    /**
     * @var bool
     */
    private $isNewRecord = true;

    /**
     * Model constructor.
     * @param array $data
     * @param array $rules
     * @param array $translates
     * @param string $scene
     * @throws \ReflectionException
     */
    public function __construct(array $data = [], array $rules = [], array $translates = [], string $scene = '')
    {
        parent::__construct($data, $rules, $translates, $scene);
        if (array_key_exists(static::class, self::$modelReflectionClass) && self::$modelReflectionClass[static::class] != null) {
            $this->_reflectionClass = self::$modelReflectionClass[static::class];
        } else {
            $this->_reflectionClass = new \ReflectionClass(static::class);
            self::$modelReflectionClass[static::class] = $this->_reflectionClass;
        }
        $this->isNewRecord = !$data;
        $this->load($data);
    }

    /**
     * @return array
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \ReflectionException
     */
    public function rules(): array
    {
        return Validated::buildRole($this->_reflectionClass);
    }

    /**
     * @param $array
     * @throws \ReflectionException
     */
    public function load ($array) {
        if (empty($array)) return;
        $sceneFields = $this->getSceneFields();
        //转换key格式
        $data = [];
        foreach ($array as $key => $value) {
            if ($sceneFields && !in_array($key, $sceneFields)) continue;
            $data[StringHelper::snake2Camel($key)] = $value;
        }
        $filterData = Filter::filter($this->_reflectionClass, $data);
        foreach ($filterData as $key => $value) {
            if ($this->_reflectionClass->hasProperty($key)) {
                $reflectionProperty = $this->_reflectionClass->getProperty($key);
                if ($reflectionProperty->isPublic()) {
                    $this->$key = $value;
                }
            }
        }
    }

    /**
     * @param array $include
     * @throws ValidationException
     */
    public function validated($include = []) {
        if ($this->validate($include)->failed()) {
            throw new ValidationException($this->firstError());
        }
    }

    /**
     * @param bool $ignoreNull
     * @param bool $camel2Snake
     * @param array $include
     * @param array $exclude
     * @return array
     */
    public function toArray($ignoreNull = true, $camel2Snake = true, $include = [], $exclude = [])
    {
        $array = [];
        foreach ($this->_reflectionClass->getProperties() as $reflectionProperty) {
            if ($reflectionProperty->isPublic()) {
                $key = $reflectionProperty->name;
                $value = $this->$key;
                if (is_array($value) || is_object($value)) continue;
                if ($ignoreNull && $value === null) continue;
                if ($include && !in_array($key, $include)) continue;
                if ($exclude && in_array($key, $exclude)) continue;
                if ($camel2Snake) {
                    $array[StringHelper::camel2Snake($key)] = $value;
                } else {
                    $array[$key] = $value;
                }
            }
        }
        return $array;
    }

    /**
     * @param $selectDb
     * @return \ESD\Plugins\Mysql\MysqliDb|mixed
     * @throws MysqlException
     */
    private static function Db($selectDb) {
        $name = $selectDb;
        $db = getContextValue("MysqliDb:$name");
        if ($db == null) {
            $mysqlPool = getDeepContextValueByClassName(MysqlManyPool::class);
            if ($mysqlPool instanceof MysqlManyPool) {
                $db = $mysqlPool->getPool($name)->db();
            } else {
                throw new MysqlException("没有找到名为{$name}的mysql连接池");
            }
        }
        return $db;
    }

    /**
     * @param $condition
     * @param string $selectDb
     * @return GoModel|null
     * @throws MysqlException
     * @throws \ReflectionException
     */
    public static function findOne($condition, $selectDb = 'default') {
        $query = self::Db($selectDb);
        if (!is_array($condition)) {
            $query->where(static::getPrimaryKey(), $condition);
        } else {
            foreach ($condition as $cond) {
                $query->where(...$cond);
            }
        }
        $result = $query->get(static::getTableName(), 1)[0] ?? null;
        if ($result == null) {
            return null;
        } else {
            return new static($result);
        }
    }

    /**
     * @param array $condition
     * @param string $selectDb
     * @return array
     * @throws MysqlException
     * @throws \ReflectionException
     */
    public static function findAll(array $condition, $selectDb = 'default') {
        $query = self::Db($selectDb);
        foreach ($condition as $cond) {
            $query->where(...$cond);
        }
        $result = $query->get(static::getTableName()) ?? [];
        foreach ($result as $key => $one) {
            $result[$key] = new static($one);
        }
        return $result;
    }

    /**
     * @param bool $forcePrimaryKey
     * @return array|\stdClass
     * @throws ValidationException
     */
    public function validatedData($forcePrimaryKey = false)
    {
        $this->data = $this->toArray(true, true);
        if ($forcePrimaryKey) {
            $this->setRules([[static::getPrimaryKey(), 'required']]);
            $sceneFields = $this->getSceneFields();
            if ($sceneFields && !in_array(static::getPrimaryKey(), $sceneFields)) {
                $sceneFields[] = static::getPrimaryKey();
                $this->validated($sceneFields);
            } else {
                $this->validated($sceneFields);
            }
        } else {
            $this->validated();
        }
        return $this->getSafeData();
    }

    /**
     * @param string $selectDb
     * @return bool
     * @throws MysqlException
     * @throws ValidationException
     */
    public function update($selectDb = 'default') {
        // Update load data rather then safe data.
        $this->validatedData(true);
        $pk = $this->getPrimaryKey();
        return $this->mysql($selectDb)
            ->where($pk, $this->$pk)
            ->update($this->getTableName(), $this->data);
    }

    /**
     * @param string $selectDb
     * @return bool
     * @throws MysqlException
     * @throws ValidationException
     */
    public function replace($selectDb = 'default') {
        // Replace load data rather then safe data.
        $this->validatedData(true);
        $pk = $this->getPrimaryKey();
        return $this->mysql($selectDb)
            ->where($pk, $this->$pk)
            ->replace($this->getTableName(), $this->data);
    }

    /**
     * @param string $selectDb
     * @throws MysqlException
     * @throws ValidationException
     */
    public function insert($selectDb = 'default') {
        // Insert load data rather then safe data.
        $this->validatedData(false);
        $id = $this->mysql($selectDb)
            ->insert($this->getTableName(), $this->data);
        if ($id === false) {
            throw new MysqlException($this->mysql($selectDb)->getLastError(), $this->mysql($selectDb)->getLastErrno());
        }
        $pk = $this->getPrimaryKey();
        $this->$pk = $id;
    }

    /**
     * @param string $selectDb
     * @return bool
     * @throws MysqlException
     * @throws ValidationException
     */
    public function save($selectDb = 'default') {
        if (!$this->isNewRecord) {
            return $this->update($selectDb);
        }
        $this->insert($selectDb);
        return true;
    }

    /**
     * @param string $selectDb
     * @return bool
     * @throws MysqlException
     */
    public function delete($selectDb = 'default') {
        $pk = $this->getPrimaryKey();
        return $this->mysql($selectDb)
            ->where($pk, $this->$pk)
            ->delete($this->getTableName());
    }

    /**
     * @return bool
     */
    public function isNewRecord(): bool
    {
        return $this->isNewRecord;
    }

    /**
     * @param bool $isNewRecord
     */
    public function setIsNewRecord(bool $isNewRecord): void
    {
        $this->isNewRecord = $isNewRecord;
    }

    abstract public static function getPrimaryKey(): string;
    abstract public static function getTableName(): string;
}