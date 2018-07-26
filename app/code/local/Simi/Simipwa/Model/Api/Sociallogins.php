<?php

/**
 * Created by PhpStorm.
 * User: scottsimicart
 * Date: 12/11/17
 * Time: 9:13 PM
 */
class Simi_Simipwa_Model_Api_Sociallogins extends Simi_Simiconnector_Model_Api_Abstract
{
    public function setBuilderQuery()
    {
        $data = $this->getData();
        $customerModel = Mage::getModel('simipwa/customermap');
        $params = $data['params'];
        if (isset($params['hash']) && $params['hash'] !== '' && isset($params['email']) && $params['email'] !== '') {
            $customer = $customerModel->createCustomer($params);
            Mage::helper('simiconnector/customer')->loginByCustomer($customer);
        } else {
            $customer = $customerModel->getCustomerByProviderIdAndUId($params['providerId'], $params['uid']);
            Mage::helper('simiconnector/customer')->loginByCustomer($customer);
        }
        $this->builderQuery = Mage::getSingleton('customer/session')->getCustomer();
    }

    public function index()
    {
        return $this->show();
    }

    public function getDetail($info)
    {
        return array('customer' => $this->motifyFields($info));
    }
}