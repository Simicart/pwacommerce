<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 11/20/17
 * Time: 4:06 PM
 */
class Simi_Simipwa_Block_Adminhtml_Pwa_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct(){
        parent::__construct();
        $this->setId('pwa_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('simipwa')->__('PWA Agent Information'));
    }

    protected function _beforeToHtml(){
        $this->addTab('form_section', array(
            'label'	 => Mage::helper('simipwa')->__('PWA Agent Information'),
            'title'	 => Mage::helper('simipwa')->__('PWA Agent Information'),
            'content'	 => $this->getLayout()->createBlock('simipwa/adminhtml_pwa_edit_tab_form')->toHtml(),
        ));
        return parent::_beforeToHtml();
    }
}