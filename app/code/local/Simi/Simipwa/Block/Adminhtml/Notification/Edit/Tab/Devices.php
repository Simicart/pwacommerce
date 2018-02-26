<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 12/18/17
 * Time: 4:05 PM
 */
class Simi_Simipwa_Block_Adminhtml_Notification_Edit_Tab_Devices extends Mage_Adminhtml_Block_Widget_Grid {

    public $storeview_id;

    public function __construct($arguments = array()) {
        parent::__construct($arguments);
        if ($this->getRequest()->getParam('current_grid_id')) {
            $this->setId($this->getRequest()->getParam('current_grid_id'));
        } else {
            $this->setId('skuChooserGrid_' . $this->getId());
        }
        $form = $this->getJsFormObject();
        $gridId = $this->getId();
        $this->setCheckboxCheckCallback("constructDataDevice($gridId)");
        $this->setDefaultSort('agent_id');
        $this->setDefaultDir('ASC');
        $this->setUseAjax(true);
        if ($this->getRequest()->getParam('collapse')) {
            $this->setIsCollapsed(true);
        }
        $this->setTemplate('simipwa/devicegrid.phtml');
    }

    /**
     * Retrieve quote store object
     * @return Mage_Core_Model_Store
     */
    public function getStore() {
        return Mage::app()->getStore();
    }

    protected function _addColumnFilterToCollection($column) {
        if ($column->getId() == 'in_devices') {
            $selected = $this->_getSelectedDevices();
            if (empty($selected)) {
                $selected = '';
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('agent_id', array('in' => $selected));
            } else {
                $this->getCollection()->addFieldToFilter('agent_id', array('nin' => $selected));
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }

        return $this;
    }

    /**
     * Prepare Catalog Product Collection for attribute SKU in Promo Conditions SKU chooser
     *
     * @return Mage_Adminhtml_Block_Promo_Widget_Chooser_Sku
     */
    protected function _prepareCollection() {
        $collection = Mage::getModel('simipwa/agent')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Define Cooser Grid Columns and filters
     *
     * @return Mage_Adminhtml_Block_Promo_Widget_Chooser_Sku
     */
    protected function _prepareColumns() {

        $this->addColumn('in_devices', array(
            'header_css_class' => 'a-center',
            'type' => 'checkbox',
            'name' => 'in_devices',
            'values' => $this->_getSelectedDevices(),
            'align' => 'center',
            'index' => 'in_devices',
            'use_index' => true,
            'width' => '50px',
            'renderer' => 'simipwa/adminhtml_notification_edit_renderer_devices'
        ));

        $this->addColumn('agent_id', array(
            'header' => Mage::helper('simipwa')->__('ID'),
            'align' => 'right',
            'width' => '50px',
            'index' => 'agent_id',
        ));

        $this->addColumn('user_agent', array(
            'header' => Mage::helper('simipwa')->__('User Agent'),
            'align' => 'right',
            'width' => '400px',
            'index' => 'user_agent',
        ));

        $this->addColumn('city', array(
            'header' => Mage::helper('simipwa')->__('City'),
            'width' => '100px',
            'index' => 'city',
        ));

        $this->addColumn('country', array(
            'header' => Mage::helper('simipwa')->__('Country'),
            'width' => '100px',
            'index' => 'country',
        ));

        return parent::_prepareColumns();
    }

    public function getGridUrl() {
        return $this->getUrl('*/*/chooseDevices', array(
            '_current' => true,
            'current_grid_id' => $this->getId(),
            'selected_ids' => implode(',', $this->_getSelectedDevices()),
            'collapse' => null
        ));
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