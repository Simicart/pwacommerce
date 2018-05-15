<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 5/10/18
 * Time: 8:24 AM
 */
class Simi_Simipwa_Block_Adminhtml_System_Config_Form_SyncApi extends Mage_Adminhtml_Block_System_Config_Form_Field
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
     * Generate button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $actionHtml = "";

        if (class_exists('Simi_Simiconnector_Controller_Action')) {
            $button = $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(
                    array(
                    'id' => 'api_button',
                    'label' => $this->helper('adminhtml')->__('Refresh Cache Api'),
                    'onclick' => 'setLocation(\'' . Mage::helper('adminhtml')->getUrl('adminhtml/simipwa_pwa/refreshCache') . '\')'
                    )
                );

            $actionHtml .=  $button->toHtml();
        } else
            $actionHtml.= '<script type="text/javascript">
                document.getElementById("simipwa_cache_api-head").parentElement.parentElement.style.display = "none";
            </script>';
        return $actionHtml;
    }
}