<?php
/**
 * Created by PhpStorm.
 * User: macos
 * Date: 8/29/18
 * Time: 11:03 AM
 */
class Simi_Simipwa_Model_System_Main
{
    public function toOptionArray()
    {
        $manifestContent = file_get_contents('./pwa/assets-manifest.json');
        if ($manifestContent && $manifestJsFiles = json_decode($manifestContent, true)) {
            $data = array();
            foreach ($manifestJsFiles as $key => $val){
//                $key = explode('.',$key);
                if(strpos($key,'static') !== 0){
                    if(strpos($key,'main') !== false
                        ||strpos($key,'vendors-main') !== false
                        ||strpos($key,'vendor.') !== false){
                        $data[] = array(
                            'value' => $val,
                            'label' => $key,
                        );
                    }

                }

            }
            return $data;
        }
        return array(
            array('value' => 0, 'label'=>Mage::helper('simipwa')->__('None')),
        );
    }
}