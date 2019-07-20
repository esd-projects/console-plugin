<?php
/**
 * Created by PhpStorm.
 * User: Elite
 * Date: 2019/7/14
 * Time: 15:48
 */

namespace ESD\Plugins\Console;

use Leuffen\TextTemplate\TextTemplate;

class FileGen
{
    /**
     * @var string
     */
    private $tplDir;
    /**
     * @var string
     */
    private $tplName;
    /**
     * @var string
     */
    private $tplSuffix;

    private $parser;

    public function __construct(string $tplDir, string $tplName, string $tplSuffix = '.stub')
    {
        $this->tplDir = $tplDir;
        $this->tplName = $tplName;
        $this->tplSuffix = $tplSuffix;
        $this->parser = new TextTemplate();
    }

    /**
     * @param array $data
     * @return string
     * @throws \Exception
     */
    public function render (array $data = []) {
        $tplFile = $this->getTplFile();

        $text = $this->parser
            ->loadTemplate(file_get_contents($tplFile))
            ->apply($data);

        return $text;
    }

    /**
     * @param string $file
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function renderAs (string $file, array $data = []) {
        $text = $this->render($data);
        return file_put_contents($file, $text) > 0;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getTplFile()
    {
        $file = rtrim($this->tplDir, '/\\') . '/' . $this->tplName . $this->tplSuffix;
        if (!file_exists($file)) {
            throw new \Exception("Template file not exists! File: {$file}");
        }
        return $file;
    }
}