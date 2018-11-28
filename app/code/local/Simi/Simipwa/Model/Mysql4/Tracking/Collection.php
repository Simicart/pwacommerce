<?php
/**
 * Created by PhpStorm.
 * User: macos
 * Date: 11/22/18
 * Time: 4:57 PM
 */
class Simi_Simipwa_Model_Mysql4_Tracking_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('simipwa/tracking');
    }
}