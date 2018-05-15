<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 3/13/18
 * Time: 3:58 PM
 */
class Simi_Simipwa_Block_Adminhtml_System_Config_Form_SyncSW extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('simipwa/button_sw.phtml');
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
        return Mage::helper('adminhtml')->getUrl('adminhtml/simipwa_pwa/syncSw');
    }

    /**
     * Generate button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(
                array(
                'id' => 'sw_button',
                'label' => $this->helper('adminhtml')->__('Sync Service Worker PWA'),
                'onclick' => 'javascript:sync_sw(); return false;'
                )
            );

        return $button->toHtml();
    }
}