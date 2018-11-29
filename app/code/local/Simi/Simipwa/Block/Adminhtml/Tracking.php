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

    public function date_range($first, $last, $step = '+1 day', $output_format = 'd/m/Y' ) {

        $dates = array();
        $current = strtotime($first);
        $last = strtotime($last);

        while( $current <= $last ) {

            $dates[] = date($output_format, $current);
            $current = strtotime($step, $current);
        }

        return $dates;
    }

    public function getTotalTrackingCollection($key,$name){
        $from = date('Y-m-d',strtotime("-28 day"));
        $to = date('Y-m-d',strtotime("+1 day"));
        $range_date = $this->date_range($from,$to,'+1 day','Y-m-d');
        $collection = Mage::getModel('simipwa/tracking')->getCollection()
                    ->addFieldToFilter('tracking_pwa',$key)
                    ->addFieldToFilter('created_at',array('from'=>$from,'to'=>$to,'date'=>true));
        $collection->getSelect()
                   ->columns('COUNT(tracking_pwa) as total')
                   ->group('created_at');
        $data = $collection->getData();
        $result = array();
        foreach ($range_date as $date){
            $total_tracking = 0;
            $tracking_data = array('day'=>date('m/d',strtotime($date)));
            foreach ($data as $item){
                if(strpos($item['created_at'],$date) !== false){
                    $total_tracking += $item['total'];
                }
            }
            $tracking_data[$name] = $total_tracking;
            $result[] = $tracking_data;
        }
        return $result;
    }

    public function convertTotalTracking(){
        $pwa_visited = $this->getTotalTrackingCollection(1,'pwa_visited');
        $switch_to_website = $this->getTotalTrackingCollection(2,'switch_to_website');
        $back_to_pwa = $this->getTotalTrackingCollection(3,'back_to_pwa');
        $data = array();
        foreach ($pwa_visited as $key => $item){
            $data[] = array_merge($pwa_visited[$key],$switch_to_website[$key],$back_to_pwa[$key]);
        }
        return $data;
    }
}