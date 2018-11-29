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
        $from = date('Y-m-d',strtotime("last month"));
        $to = date('Y-m-d',strtotime("+1 day"));
        $period = new DatePeriod(
            new DateTime('2010-10-01'),
            new DateInterval('P1D'),
            new DateTime('2010-10-05')
        );
//        var_dump($period);die;
//        echo date("Y-m-d",$from);die;
        $collection = Mage::getModel('simipwa/tracking')->getCollection()
                    ->addFieldToFilter('tracking_pwa',3)
                    ->addFieldToFilter('created_at',array('from'=>$from,'to'=>$to,'date'=>true));
        $collection->getSelect()
                   ->columns('COUNT(tracking_pwa) as total')
                   ->group('created_at');
//        zend_debug::dump($collection->getData());die;
        return $collection;
    }
}