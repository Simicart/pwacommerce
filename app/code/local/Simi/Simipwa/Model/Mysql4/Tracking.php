<?php
/**
 * Created by PhpStorm.
 * User: macos
 * Date: 11/22/18
 * Time: 4:56 PM
 */
class Simi_Simipwa_Model_Mysql4_Tracking extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('simipwa/tracking', 'id');
    }
}