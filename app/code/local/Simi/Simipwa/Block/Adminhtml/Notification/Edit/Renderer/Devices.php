<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 12/18/17
 * Time: 4:43 PM
 */
class Simi_Simipwa_Block_Adminhtml_Notification_Edit_Renderer_Devices extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    public function render(Varien_Object $row) {
        $checked = '';
        if (in_array($row->getId(), $this->_getSelectedDevices()))
            $checked = 'checked';
        $html = '<input type="checkbox" ' . $checked . ' name="selected" value="' . $row->getId() . '" class="checkbox" onclick="selectDevice(this)">';
        return sprintf('%s', $html);
    }

    protected function _getSelectedDevices() {
        $devices = $this->getRequest()->getPost('selected', array());
        if (!$devices) {
            if ($this->getRequest()->getParam('selected_ids')) {
                $devices = explode(',', $this->getRequest()->getParam('selected_ids'));
            }
        }
        return $devices;
    }

}