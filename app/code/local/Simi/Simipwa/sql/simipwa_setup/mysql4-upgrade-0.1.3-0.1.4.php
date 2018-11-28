<?php
/**
 * Created by PhpStorm.
 * User: macos
 * Date: 11/22/18
 * Time: 4:36 PM
 */
$installer = $this;
$installer->startSetup();

$installer->run("

    DROP TABLE IF EXISTS {$installer->getTable('simipwa_tracking')};
    CREATE TABLE {$installer->getTable('simipwa_tracking')} (
        `id` int(11) unsigned NOT NULL auto_increment,
        `tracking_pwa` SMALLINT(2) NOT NULL DEFAULT 1 ,
        `user_agent` text NOT NULL DEFAULT  '',
        `device` VARCHAR(255) NOT NULL DEFAULT '',
        `browser` VARCHAR(255) NOT NULL DEFAULT '',
        `city` VARCHAR(255) NOT NULL DEFAULT '',
        `country` VARCHAR(255) NOT NULL DEFAULT '',
        `created_at` datetime NOT NULL default '0000-00-00 00:00:00',
        PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
"
);
$installer->endSetup();