<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 11/20/17
 * Time: 5:03 PM
 */
class Simi_Simipwa_Model_Agent extends Mage_Core_Model_Abstract
{
    public function _construct(){
        parent::_construct();
        $this->_init('simipwa/agent');
    }

    public function toOptionArray(){
        $platform = array(
            1 => Mage::helper('simipwa')->__('Product In-app'),
            2 => Mage::helper('simipwa')->__('Category In-app'),
            3 => Mage::helper('simipwa')->__('Website Page'),
        );
        return $platform;
    }

    public function send($device_id){
        $data = array();
        $public_key = 'BFn4qEo_D1R50vPl58oOPfkQgbTgaqmstMhIzWyVgfgbMQPtFk94X-ThjG0hfOTSAQUBcCBXpPHeRMN7cqDDPaE';
        $private_key = 'r2nph41fesUhfHitp1wbldZvIu_I51Aiy-S8w7fpv-U';
        //$api_key = 'AAAAwdkoDtM:APA91bGOxLHjmDeyzCj7Eix-8M1vHOkvhBUxFBUC_XWcUIksOrVtdI2vFYae-d1AlNRAmmb_RFHTCZw9CStzc-z2qJ50B1cCNhlpouO8Wkt_bBxzTq4HYq3IbxTqtolTMGJFBi4DPatv';
        $device = Mage::getModel('simipwa/agent')->load($device_id)->getData();
        $data['subscription'] = array(
            "endpoint" => $device['endpoint'],
            "expirationTime" => null,
            "keys" => array(
                "p256dh" => $device['p256dh_key'],
                "auth" => $device['auth_key']
            )
        );
        $data['applicationKeys'] = array(
            "public" => $public_key,
            "private" => $private_key
        );
        $headers = array
        (
            //'Authorization: key=' . $api_key,
            'Content-Type: application/json'
        );
        $data = json_encode($data);
        //echo $data;die;
        $ch = curl_init();

        curl_setopt( $ch,CURLOPT_URL, 'https://web-push-codelab.glitch.me/api/send-push-msg' );
        curl_setopt( $ch,CURLOPT_POST, true );
        curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
        curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch,CURLOPT_POSTFIELDS, $data);

        $result = curl_exec($ch);

        curl_close ($ch);
        $result = json_decode($result,true);
        if ($result['success']){
            return true;
        }
        return false;
    }
}