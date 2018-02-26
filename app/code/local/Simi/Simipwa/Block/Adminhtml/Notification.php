<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 11/22/17
 * Time: 11:00 AM
 */
class Simi_Simipwa_Block_Adminhtml_Notification extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct(){
        $this->_controller = 'adminhtml_notification';
        $this->_blockGroup = 'simipwa';
        $this->_headerText = Mage::helper('simipwa')->__('Notification Manager');
        parent::__construct();
    }
}