<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 11/20/17
 * Time: 4:05 PM
 */
class Simi_Simipwa_Block_Adminhtml_Pwa extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct(){
        $this->_controller = 'adminhtml_pwa';
        $this->_blockGroup = 'simipwa';
        $this->_headerText = Mage::helper('simipwa')->__('Pwa Agent Manager');
        parent::__construct();
        $this->_removeButton('add');
    }
}