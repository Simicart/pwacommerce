<?php
/**
 * Created by PhpStorm.
 * User: macos
 * Date: 11/22/18
 * Time: 4:55 PM
 */
class Simi_Simipwa_Model_Tracking extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('simipwa/tracking');
    }
}