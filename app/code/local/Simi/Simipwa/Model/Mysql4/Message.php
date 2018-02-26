<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 11/21/17
 * Time: 5:36 PM
 */
class Simi_Simipwa_Model_Mysql4_Message extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct(){
        $this->_init('simipwa/message', 'message_id');
    }
}