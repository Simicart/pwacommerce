<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 12/12/17
 * Time: 4:38 PM
 */
$installer = $this;
$installer->startSetup();

$installer->run("

    DROP TABLE IF EXISTS {$installer->getTable('simipwa_social_customer_mapping')};
    CREATE TABLE {$installer->getTable('simipwa_social_customer_mapping')} (
        `id` int(11) unsigned NOT NULL auto_increment,
        `customer_id` int(11) NULL default 0,
        `social_user_id` VARCHAR(255) NULL DEFAULT  '',
        `provider_id` VARCHAR(255) NULL DEFAULT  '',
        PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
"
);
$installer->endSetup();