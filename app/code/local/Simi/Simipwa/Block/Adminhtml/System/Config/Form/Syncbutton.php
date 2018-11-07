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
        $actionHtml = "";

        if (class_exists('Simi_Simiconnector_Controller_Action')) {

            $buildtype = Mage::getStoreConfig('simipwa/general/pwa_main_url_site');

            $button = $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(
                    array(
                        'id' => 'pwa_button_sandbox',
                        'label' => $this->helper('adminhtml')->__('Build Sandbox PWA'),
                        'onclick' => 'setLocation(\'' . Mage::helper('adminhtml')->getUrl('adminhtml/simipwa_pwa/build',array("build_type"=>"sandbox")) . '\')',

                    )
                );

            $actionHtml .= $button->toHtml();

            $actionJs = 'var r = confirm(\'Are you sure to Build and go Live? This will change your public Website PWA\');
                            if(r){setLocation(\''.Mage::helper('adminhtml')->getUrl('adminhtml/simipwa_pwa/build',array("build_type"=>"live")).'\')}';
            if(!$buildtype){
                $actionJs = "alert('Before Build Live PWA , please change config: Enable Redirection');";
            }
            $buildButton = $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(
                    array(
                        'id' => 'build_pwa',
                        'label' => __('Build Live PWA'),
                        'onclick' => $actionJs,
                        'style' => 'margin-left : 10px;margin-bottom : 10px;'
                    )
                );
            $actionHtml .= $buildButton->toHtml();
            if (Mage::getStoreConfig('simipwa/general/build_time')) {
                $date = date('m/d/Y - H:i:s', Mage::getStoreConfig('simipwa/general/build_time'));
                $html = "Date time : $date";
                $actionHtml .= '<script type="text/javascript">
                    document.getElementById("simipwa_general_build_time").setAttribute("readonly","readonly")
                    document.getElementById("simipwa_general_build_time").nextElementSibling.children[0].innerText = "' . $html . '"
                </script>';
            }
            if(Mage::getStoreConfig('simipwa/general/build_time_sandbox')){
                $date = date('m/d/Y - H:i:s', Mage::getStoreConfig('simipwa/general/build_time_sandbox'));
                $html = "Date time : $date";
                $actionHtml .= '<script type="text/javascript">
                    document.getElementById("simipwa_general_build_time_sandbox").setAttribute("readonly","readonly")
                    document.getElementById("simipwa_general_build_time_sandbox").nextElementSibling.children[0].innerText = "' . $html . '"
                </script>';
            }

        } else
            $actionHtml .= '<script type="text/javascript">
                document.getElementById("simipwa_general-head").parentElement.parentElement.style.display = "none";
                document.getElementById("simipwa_analytics-head").parentElement.parentElement.style.display = "none";
                function addHomeScreenWarning() {
                    simipwa_notification_enable = document.getElementById("simipwa_notification_enable");
                    homescreen_enable = document.getElementById("row_simipwa_manifest_enable");
                    if(simipwa_notification_enable.value == 0) {
                        addToHomeWarning = document.getElementById("add_to_home_warning");
                         homescreen_enable.parentNode.style.display = "none";
                        if (!addToHomeWarning || typeof addToHomeWarning == "undefined") {
                            var addToHomeWarning = document.createElement("div");
                                addToHomeWarning.innerHTML = "Please enable Offline Mode to open Add to Home Screen feature";
                                addToHomeWarning.className = "add_to_home_warning";
                                addToHomeWarning.id = "add_to_home_warning";
                            document.getElementById("simipwa_manifest").appendChild(addToHomeWarning);   
                        } else {
                            addToHomeWarning.style.display = "block";
                        }
                    } else {
                        addToHomeWarning = document.getElementById("add_to_home_warning");
                        if (addToHomeWarning && typeof addToHomeWarning != "undefined")
                            addToHomeWarning.style.display = "none";
                            homescreen_enable.parentNode.style.display = "block";
                    }
                }
                
                document.addEventListener("DOMContentLoaded", function(event) {
                    addHomeScreenWarning();
                    var simipwa_notification_enable = document.getElementById("simipwa_notification_enable");
                    simipwa_notification_enable.addEventListener("change", function() {
                        addHomeScreenWarning();
                    });
                });
            </script>';

        return $actionHtml;
    }
}