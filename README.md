# console-plugin
esd-projects 插件，支持生成实体类

```$xslt
Description:
  Entity generator

Usage:
  entity [options] [--] [<pool>]

Arguments:
  pool                       database db pool? [default: "default"]

Options:
  -t, --table[=TABLE]        database table name? (multiple values allowed)
      --path[=PATH]          generate entity file path? [default: "@app/Model/Entity"]
      --template[=TEMPLATE]  generate entity template path? [default: "@devtool/resources"]
      --extend[=EXTEND]      generate extend class? [default: "\ESD\Plugins\Console\Model\GoModel"]
  -y, --confirm              confirm execution?
  -h, --help                 Display this help message
  -q, --quiet                Do not output any message
  -V, --version              Display this application version
      --ansi                 Force ANSI output
      --no-ansi              Disable ANSI output
  -n, --no-interaction       Do not ask any interactive question
  -v|vv|vvv, --verbose       Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```
例子：
```$xslt
php start_server.php entity -t user -y
```