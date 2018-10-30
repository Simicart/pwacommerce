<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 5/11/18
 * Time: 9:06 AM
 */
class Simi_Simipwa_Model_System_Homepage
{
    public function toOptionArray()
    {
        $manifestContent = file_get_contents('./pwa/assets-manifest.json');
        if ($manifestContent && $manifestJsFiles = json_decode($manifestContent, true)) {
            $data = array();
            foreach ($manifestJsFiles as $key => $val){
//                $key = explode('.',$key);
//                print_r(strpos($key,'.css'));die;
                if(strpos($key,'static') !== 0 && strpos($key,'.js') !== false){
                    if(strpos($key,'Home') !== false
                        ||strpos($key,'Zara') !== false
                        ||strpos($key,'Matrix') !== false
                        ||strpos($key,'Default') !== false
                        ||strpos($key,'Banner') !== false)
                    {
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