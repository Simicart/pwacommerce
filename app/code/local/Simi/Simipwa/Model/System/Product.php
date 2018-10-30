<?php
/**
 * Created by PhpStorm.
 * User: macos
 * Date: 8/28/18
 * Time: 4:24 PM
 */
class Simi_Simipwa_Model_System_Product
{
    public function toOptionArray()
    {
        $manifestContent = file_get_contents('./pwa/assets-manifest.json');
        if ($manifestContent && $manifestJsFiles = json_decode($manifestContent, true)) {
            $data = array();
            foreach ($manifestJsFiles as $key => $val){
//                $key = explode('.',$key);
                if(strpos($key,'static') !== 0 && strpos($key,'.js') !== false){
                    if(strpos($key,'Product.') !== false
                    || strpos($key,'Match') !== false){
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