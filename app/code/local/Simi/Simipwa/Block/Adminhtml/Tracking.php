<?php
/**
 * Created by PhpStorm.
 * User: macos
 * Date: 11/23/18
 * Time: 11:21 AM
 */
class Simi_Simipwa_Block_Adminhtml_Tracking extends Mage_Core_Block_Template {

    /**
     * prepare block's layout
     *
     * @return Simi_Simipwa_Block_Adminhtml_Tracking
     */
    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    public function getCollection(){
        $collection = Mage::getModel('simipwa/tracking')->getCollection()->getData();
        return $collection;
    }
}