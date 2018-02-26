<?php

$installer = $this;
$installer->startSetup();

$installer->run("
    DROP TABLE IF EXISTS {$installer->getTable('simipwa_agent')};
    DROP TABLE IF EXISTS {$installer->getTable('simipwa_message')};
    
     CREATE TABLE {$installer->getTable('simipwa_agent')} (
        `agent_id` int(11) unsigned NOT NULL auto_increment,
        `user_agent` text NULL default '',
        `endpoint` VARCHAR(255) NULL DEFAULT  '',
        `endpoint_key` text NULL  DEFAULT  '',
        `p256dh_key` text NULL  DEFAULT '',
        `auth_key` text NULL DEFAULT '',
        `created_at` datetime NOT NULL default '0000-00-00 00:00:00' ,
        `status` SMALLINT(2) NOT NULL DEFAULT 2,
        PRIMARY KEY (`agent_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        
    CREATE TABLE {$installer->getTable('simipwa_message')} (
      `message_id` INT (11) unsigned NOT NULL auto_increment,
      `device_id` VARCHAR (255) NOT NULL DEFAULT '',
      `notice_title` varchar(255) NULL default '',    
        `notice_url` varchar(255) NULL default '',    
        `notice_content` text NULL default '', 
        `type` smallint(5) unsigned DEFAULT 1,
        `category_id` int(10) unsigned  NOT NULL,
        `product_id` int(10) unsigned  NOT NULL,
        `image_url` varchar(255) NOT NULL default '',
        `created_time` datetime NOT NULL default '0000-00-00 00:00:00' ,
        `notice_type` smallint(5) unsigned DEFAULT 2,
        `status` smallint(5) unsigned,
        PRIMARY KEY (`message_id`)
    )
");

$installer->endSetup();