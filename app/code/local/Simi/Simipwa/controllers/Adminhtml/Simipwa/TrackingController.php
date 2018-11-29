<?php
/**
 * Created by PhpStorm.
 * User: macos
 * Date: 11/23/18
 * Time: 11:15 AM
 */
class Simi_Simipwa_Adminhtml_Simipwa_TrackingController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('simipwa');
    }

    public function indexAction()
    {
        $this->loadLayout()->renderLayout();
    }
}