<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 11/21/17
 * Time: 5:36 PM
 */
class Simi_Simipwa_Model_Mysql4_Message_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct(){
        parent::_construct();
        $this->_init('simipwa/message');
    }
}