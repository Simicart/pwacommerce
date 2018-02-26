<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 11/20/17
 * Time: 5:04 PM
 */
class Simi_Simipwa_Model_Mysql4_Agent extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct(){
        $this->_init('simipwa/agent', 'agent_id');
    }
}