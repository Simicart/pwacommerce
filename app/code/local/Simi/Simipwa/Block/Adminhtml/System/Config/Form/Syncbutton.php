<?php

/**
 * Created by PhpStorm.
 * User: scottsimicart
 * Date: 12/12/17
 * Time: 6:14 PM
 */
class Simi_Simipwa_Block_Adminhtml_System_Config_Form_Syncbutton extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('simipwa/button.phtml');
    }

    /**
     * Return element html
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }

    /**
     * Return ajax url for button
     *
     * @return string
     */
    public function getAjaxCheckUrl()
    {
        return Mage::helper('adminhtml')->getUrl('adminhtml/simipwa_pwa/syncSitemaps');
    }

    /**
     * Generate button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(array(
                'id' => 'pwa_button',
                'label' => $this->helper('adminhtml')->__('Sync Sitemaps'),
                'onclick' => 'javascript:check(); return false;'
            ));

        return $button->toHtml();
    }
}