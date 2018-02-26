<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 11/22/17
 * Time: 11:00 AM
 */
class Simi_Simipwa_Block_Adminhtml_Notification_Grid extends Mage_Adminhtml_Block_Widget_Grid {
    public function __construct(){
        parent::__construct();
        $this->setId('messageGrid');
        $this->setDefaultSort('message_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection(){
        $collection = Mage::getModel('simipwa/message')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns(){
        $this->addColumn('message_id', array(
            'header'	=> Mage::helper('simipwa')->__('ID'),
            'align'	 =>'right',
            'width'	 => '50px',
            'index'	 => 'message_id',
        ));

        $this->addColumn('notice_title', array(
            'header'	=> Mage::helper('simipwa')->__('Message Title'),
            'index'	 => 'notice_title',
        ));

        $this->addColumn('notice_content', array(
            'header'	=> Mage::helper('simipwa')->__('Message Content'),
            'index'	 => 'notice_content',
        ));

        $this->addColumn('created_time',array(
            'header'    =>  Mage::helper('simipwa')->__('Created Time'),
            'align'     => 'center',
            'width'     =>  '200px',
            'index'     =>  'created_time',
            'type'      => 'datetime',
        ));


        $this->addColumn('status', array(
            'header'	=> Mage::helper('simipwa')->__('Status'),
            'align'	 => 'left',
            'width'	 => '80px',
            'index'	 => 'status',
            'type'		=> 'options',
            'options'	 => array(
                1 => 'Enabled',
                2 => 'Disabled',
            ),
        ));

        $this->addColumn('action',
            array(
                'header'	=>	Mage::helper('simipwa')->__('Action'),
                'width'		=> '150px',
                'type'		=> 'action',
                'getter'	=> 'getId',
                'actions'	=> array(
                    array(
                        'caption'	=> Mage::helper('simipwa')->__('Edit'),
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
        $this->setMassactionIdField('message_id');
        $this->getMassactionBlock()->setFormFieldName('message');

        $this->getMassactionBlock()->addItem('delete', array(
            'label'		=> Mage::helper('simipwa')->__('Delete'),
            'url'		=> $this->getUrl('*/*/massDelete'),
            'confirm'	=> Mage::helper('simipwa')->__('Are you sure?')
        ));

        $statuses = array(
            1 => Mage::helper('simipwa')->__('Enabled'),
            2 => Mage::helper('simipwa')->__('Disabled')
        );

        array_unshift($statuses, array('label'=>'', 'value'=>''));
        $this->getMassactionBlock()->addItem('status', array(
            'label'=> Mage::helper('simipwa')->__('Change status'),
            'url'	=> $this->getUrl('*/*/massStatus', array('_current'=>true)),
            'additional' => array(
                'visibility' => array(
                    'name'	=> 'status',
                    'type'	=> 'select',
                    'class'	=> 'required-entry',
                    'label'	=> Mage::helper('simipwa')->__('Status'),
                    'values'=> $statuses
                ))
        ));
        return $this;
    }

    public function getRowUrl($row){
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
}