<?php
namespace ESD\Plugins\Console\Service;

abstract class Grammar
{
    /**
     * @var string
     */
    public const INTEGER  = 'integer';
    public const NUMBER   = 'number';
    public const STRING   = 'string';
    public const FLOAT    = 'float';
    public const DATETIME = 'datetime';
    public const BOOLEAN  = 'boolean';
    public const BOOL     = 'bool';
}