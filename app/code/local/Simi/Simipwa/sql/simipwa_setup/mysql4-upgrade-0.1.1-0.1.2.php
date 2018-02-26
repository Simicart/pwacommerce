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


    ALTER TABLE {$this->getTable('simipwa_agent')}
      ADD city VARCHAR (255) NULL DEFAULT '',
      ADD country VARCHAR (255) NULL  DEFAULT  '';
");
$installer->endSetup();