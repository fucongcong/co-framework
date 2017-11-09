<?php

namespace app\sql;

use Group\Sync\Dao\SqlMigration;

class Sql20170720164604 extends SqlMigration
{
    public function run()
    {
        $this->addSql("CREATE TABLE `monitor` ( `id` INT UNSIGNED NOT NULL AUTO_INCREMENT , `server` VARCHAR(50) NOT NULL COMMENT '服务分组' , `service` VARCHAR(50) NOT NULL COMMENT '服务名' , `action` VARCHAR(50) NOT NULL COMMENT '方法' , `calltime` INT NOT NULL COMMENT '响应事件' , `ip` VARCHAR(50) NOT NULL , `port` INT(10) UNSIGNED NOT NULL , `error` VARCHAR(255) NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");

        $this->addSql("ALTER TABLE `monitor` ADD INDEX `ip-port` (`ip`, `port`);");

        $this->addSql("ALTER TABLE `monitor` ADD INDEX `server` (`server`);");

        $this->addSql("ALTER TABLE `monitor` ADD INDEX `service` (`service`);");

        $this->addSql("ALTER TABLE `monitor` ADD INDEX `action` (`action`);");

        $this->addSql("ALTER TABLE `monitor` CHANGE `calltime` `calltime` DECIMAL(10,5) NOT NULL COMMENT '响应时间'");

        $this->addSql("ALTER TABLE `monitor` CHANGE `error` `error` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';");

        $this->addSql("CREATE TABLE `consumers` (
  `id` int(11) unsigned NOT NULL,
  `service` varchar(255) NOT NULL COMMENT '需要的服务',
  `address` varchar(50) NOT NULL COMMENT '消费者主机',
  `ctime` int(10) unsigned NOT NULL COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='消费者';
");
        $this->addSql("CREATE TABLE `providers` (
  `id` int(10) unsigned NOT NULL,
  `service` varchar(255) NOT NULL,
  `address` varchar(50) NOT NULL,
  `ctime` int(10) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        $this->addSql("ALTER TABLE `consumers`
  ADD PRIMARY KEY (`id`);");
        $this->addSql("ALTER TABLE `providers`
  ADD PRIMARY KEY (`id`);");
        $this->addSql("ALTER TABLE `consumers`
  MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT;");
        $this->addSql("ALTER TABLE `providers`
  MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;");
        
    }

    public function back()
    {

    }
}
