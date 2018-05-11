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
        return array(
            array('value' => 0, 'label'=>Mage::helper('simipwa')->__('No')),
            array('value' => 1,  'label'=>Mage::helper('simipwa')->__('Default Theme')),
            array('value' => 2,  'label'=>Mage::helper('simipwa')->__('Matrix Theme')),
            array('value' => 3,  'label'=>Mage::helper('simipwa')->__('Zara Theme')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            0 => Mage::helper('simipwa')->__('No'),
            1  => Mage::helper('simipwa')->__('Default Theme'),
            2  => Mage::helper('simipwa')->__('Matrix Theme'),
            3  => Mage::helper('simipwa')->__('Zara Theme'),
        );
    }
}