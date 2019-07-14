<?php
/**
 * Created by PhpStorm.
 * User: Elite
 * Date: 2019/7/1
 * Time: 12:01
 */

namespace ESD\Plugins\Console\Helper;

/**
 * Class StringHelper
 * @package ESD\Plugins\Console\Helper
 */
class StringHelper
{
    public static function camel2Snake($var)
    {
        if (is_numeric($var)) {
            return $var;
        }
        $result = "";
        for ($i = 0; $i < strlen($var); $i++) {
            $str = ord($var[$i]);
            if ($str > 64 && $str < 91) {
                $result .= "_" . strtolower($var[$i]);
            } else {
                $result .= $var[$i];
            }
        }
        return $result;
    }

    /**
     * "_"连接修改为驼峰
     * @param $var
     * @return mixed
     */
    public static function snake2Camel($var)
    {
        if (is_numeric($var)) {
            return $var;
        }
        $result = "";
        for ($i = 0; $i < strlen($var); $i++) {
            if ($var[$i] == "_") {
                $i = $i + 1;
                $result .= strtoupper($var[$i]);
            } else {
                $result .= $var[$i];
            }
        }
        return $result;
    }
}