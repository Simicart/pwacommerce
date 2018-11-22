<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 11/21/17
 * Time: 5:35 PM
 */
class Simi_Simipwa_Model_Message extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('simipwa/message');
    }

    public function getMessage()
    {
        $message = Mage::getModel('simipwa/message')->getCollection()
            ->addFieldToFilter('status', 1)
            ->getLastItem();

        return $message;
    }
}