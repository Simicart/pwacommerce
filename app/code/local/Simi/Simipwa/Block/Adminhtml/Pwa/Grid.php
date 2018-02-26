<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 11/20/17
 * Time: 4:07 PM
 */
class Simi_Simipwa_Block_Adminhtml_Pwa_Grid extends Mage_Adminhtml_Block_Widget_Grid {
    public function __construct(){
        parent::__construct();
        $this->setId('pwaGrid');
        $this->setDefaultSort('agent_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection(){
        $collection = Mage::getModel('simipwa/agent')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns(){
        $this->addColumn('agent_id', array(
            'header'	=> Mage::helper('simipwa')->__('ID'),
            'align'	 =>'right',
            'width'	 => '50px',
            'index'	 => 'agent_id',
        ));

        $this->addColumn('city', array(
            'header'	=> Mage::helper('simipwa')->__('City'),
            'index'	 => 'city',
        ));

        $this->addColumn('country', array(
            'header'	=> Mage::helper('simipwa')->__('Country'),
            'index'	 => 'country',
        ));

        $this->addColumn('created_at',array(
            'header'    =>  Mage::helper('simipwa')->__('Created Time'),
            'align'     => 'center',
            'index'     =>  'created_at',
            'type'      => 'datetime',
        ));

        $this->addColumn('action',
            array(
                'header'	=>	Mage::helper('simipwa')->__('Action'),
                'width'		=> '150px',
                'type'		=> 'action',
                'getter'	=> 'getId',
                'actions'	=> array(
                    array(
                        'caption'	=> Mage::helper('simipwa')->__('Send Message'),
                        'url'		=> array('base'=> '*/*/edit'),
                        'field'		=> 'id'
                    )),
                'filter'	=> false,
                'sortable'	=> false,
                'index'		=> 'stores',
                'is_system'	=> true,
            ));

        $this->addExportType('*/*/exportCsv', Mage::helper('simipwa')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('simipwa')->__('XML'));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction(){
        $this->setMassactionIdField('agent_id');
        $this->getMassactionBlock()->setFormFieldName('agent');

        $this->getMassactionBlock()->addItem('delete', array(
            'label'		=> Mage::helper('simipwa')->__('Delete'),
            'url'		=> $this->getUrl('*/*/massDelete'),
            'confirm'	=> Mage::helper('simipwa')->__('Are you sure?')
        ));

//        $statuses = array(
//            1 => Mage::helper('simipwa')->__('Enabled'),
//            2 => Mage::helper('simipwa')->__('Disabled')
//        );
//
//        array_unshift($statuses, array('label'=>'', 'value'=>''));
//        $this->getMassactionBlock()->addItem('status', array(
//            'label'=> Mage::helper('simipwa')->__('Change status'),
//            'url'	=> $this->getUrl('*/*/massStatus', array('_current'=>true)),
//            'additional' => array(
//                'visibility' => array(
//                    'name'	=> 'status',
//                    'type'	=> 'select',
//                    'class'	=> 'required-entry',
//                    'label'	=> Mage::helper('simipwa')->__('Status'),
//                    'values'=> $statuses
//                ))
//        ));
        return $this;
    }

    public function getRowUrl($row){
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
}