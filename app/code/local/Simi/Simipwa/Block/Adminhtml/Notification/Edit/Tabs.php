<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 11/22/17
 * Time: 11:01 AM
 */
class Simi_Simipwa_Block_Adminhtml_Notification_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct(){
        parent::__construct();
        $this->setId('notification_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('simipwa')->__('PWA Notification'));
    }

    protected function _beforeToHtml(){
        $this->addTab('form_section', array(
            'label'	 => Mage::helper('simipwa')->__('PWA  Notification'),
            'title'	 => Mage::helper('simipwa')->__('PWA  Notification'),
            'content'	 => $this->getLayout()->createBlock('simipwa/adminhtml_notification_edit_tab_form')->toHtml(),
        ));
        return parent::_beforeToHtml();
    }
}