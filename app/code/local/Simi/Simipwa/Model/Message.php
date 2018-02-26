<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 11/21/17
 * Time: 5:35 PM
 */
class Simi_Simipwa_Model_Message extends Mage_Core_Model_Abstract
{
    public function _construct(){
        parent::_construct();
        $this->_init('simipwa/message');
    }

    public function getMessage($endpoint){
        $device = Mage::getModel('simipwa/agent')->load($endpoint,'endpoint');
        $message = Mage::getModel('simipwa/message')->getCollection()
                    ->addFieldToFilter('device_id',$device->getId())
                    ->getLastItem();
        if(!$message->getId() || $message->getStatus() == 2){
            $message = Mage::getModel('simipwa/message')->getCollection()
                ->addFieldToFilter('notice_type',2)
                ->addFieldToFilter('status',1)
                ->getLastItem();
        }
        return $message;
    }
}