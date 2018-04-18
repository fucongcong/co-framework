<?php

namespace Group\Sync;

use Group\Protocol\Server;

class Sync
{
    protected $argv;

    protected $help = "
\033[34m
  - - - - - -         - - - - -      - - - - - -     \ \         \ \       - - - - - -
 / - - - - - /     \ / / - - -  /  / - - - - -  \     \ \         \ \    \ \- - - - - -\
\ \                 \ \             \ \          \ \   \ \         \ \    \ \           \ \
 \ \        - - -    \ \             \ \          \ \   \ \         \ \    \ \- - - - - / /
  \ \      / - - -/   \ \             \ \          \ \   \ \         \ \    \ \ - - - - -
   \ \        \ \      \ \             \ \          \ \   \ \         \ \    \ \
    \ \ - - -  \ \      \ \             \ \ - - - - - /    \ \ - - - - \      \ \
     \ - -- -  \ /       \ \             \ - - - - -/       \ - - -  - - /     \ \
\033[0m
\033[31m 使用帮助: \033[0m
\033[33m Usage: app/service [server名称] [start|stop|reload|restart] \033[0m
";

    public function __construct($argv)
    {
        $this->argv = $argv;
    }

    /**
     * run the console
     *
     */
    public function run()
    {
        $this->checkArgv();
        die($this->help);
    }

    /**
     * 检查输入的参数与命令
     *
     */
    protected function checkArgv()
    {
        $argv = $this->argv;

        if (!isset($argv[1])) return;

        $config = \Config::get("service::server");

        if (in_array($argv[1], ['stop', 'start', 'reload', 'restart'])) {
          return $this->console($config, $argv[1]);
        }

        $registry_address = \Config::get("service::registry_address");
        if ($registry_address && $registry_address != "") {
            $config[$argv[1]]['registry_address'] = $registry_address;
        }
        $config[$argv[1]]['debug'] = \Config::get("app::debug");

        if (!isset($config[$argv[1]])) return;

        $log = isset($config[$argv[1]]['config']['log_file']) ? $config[$argv[1]]['config']['log_file'] : 'runtime/service/default.log';
        $log = explode("/", $log);
        \FileCache::set(array_pop($log), '', implode("/", $log)."/");
        
        $server = new Server($config[$argv[1]], $argv[1], $this->argv);
        die;
    }

    private function console($config, $status)
    {
        switch ($status) {
            case 'restart':
            case 'reload':
            case 'stop':
                foreach ($config as $serverName => $val) {
                    passthru("app/service {$serverName} {$status}");
                }
                break;
            case 'start':
                foreach ($config as $serverName => $val) {
                    passthru("app/service {$serverName}");
                }
                break;
        }
        die;
    }
}
