<?php

/**
 * Created by PhpStorm.
 * User: scottsimicart
 * Date: 12/11/17
 * Time: 9:13 PM
 */
class Simi_Simipwa_Model_Api_Sitemaps extends Simi_Simiconnector_Model_Api_Abstract {

    public function setBuilderQuery(){

    }

    public function index(){
        return Mage::helper('simipwa')->getSiteMaps();
    }
}