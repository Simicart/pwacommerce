<?php

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 11/20/17
 * Time: 5:03 PM
 */
class Simi_Simipwa_Model_Customermap extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('simipwa/customermap');
    }

    public function createCustomer($params)
    {
        $customer = Mage::getModel('customer/customer')
            ->setWebsiteId(Mage::app()->getWebsite()->getId())
            ->loadByEmail($params['email']);
        if(!$customer->getId()){
            $customer = Mage::getModel('customer/customer')
                ->setWebsiteId(Mage::app()->getWebsite()->getId())
                ->setFirstname($params['firstname'])
                ->setLastname($params['lastname'])
                ->setEmail($params['email']);

            if (!$params['hash'])
                $params['hash'] = $customer->generatePassword();

            $customer->setPassword($params['hash']);
            $customer->save();
        }
        $dataMap = array(
            'customer_id' => $customer->getId(),
            'social_user_id' => $params['uid'],
            'provider_id' => $params['providerId']
        );

        $this->setData($dataMap)->save();

        return $customer;
    }

    public function getCustomerByProviderIdAndUId($providerId, $uid)
    {
        $customerMap = $this->getCollection()
            ->addFieldToFilter('provider_id', array('eq' => $providerId))
            ->addFieldToFilter('social_user_id', array('eq' => $uid))
            ->getFirstItem();
        if ($customerMap->getId()) {
            return Mage::getModel('customer/customer')->load($customerMap->getCustomerId());
        }
        throw new Exception(Mage::helper('simipwa')->__('Can not find customer'), 4);
    }
}