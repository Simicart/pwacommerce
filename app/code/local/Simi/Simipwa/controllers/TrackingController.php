<?php
/**
 * Created by PhpStorm.
 * User: macos
 * Date: 11/22/18
 * Time: 5:02 PM
 */
class Simi_Simipwa_TrackingController extends Mage_Core_Controller_Front_Action
{
    public function indexAction(){
        $data = $this->getRequest()->getRawBody();
        $data = (array) json_decode($data);
        $model = Mage::getModel('simipwa/tracking');
        if(!isset($data['tracking_pwa']) || !$data['tracking_pwa']){
            throw new Exception(Mage::helper('simipwa')->__('Cannot resource able!'), 4);
        }

        try{
            $user_agent = '';
            if ($_SERVER["HTTP_USER_AGENT"]) {
                $user_agent = $_SERVER["HTTP_USER_AGENT"];
            }
            $ip = $_SERVER['REMOTE_ADDR'];
            $location = json_decode(file_get_contents("http://ipinfo.io/{$ip}/json"));
            if($_SERVER['SERVER_NAME'] == 'localhost'){
                $location->city = 'Simi';
                $location->country = 'Ha Noi';
            }

            $browser = Mage::helper('simipwa')->getBrowser();
            $browser_name = $browser['name'];
            $browser_platform = $browser['platform'];

            $model->setTrackingPwa($data['tracking_pwa'])
                    ->setUserAgent($user_agent)
                    ->setBrowser($browser_name)
                    ->setDevice($browser_platform)
                    ->setCity($location->city)
                    ->setCountry($location->country)
                    ->setCreatedAt(now())
                    ->save();
            $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
            $this->getResponse()->setBody(json_encode($model->getData()));
        }catch (Exception $e){
            throw new Exception($e, 4);
        }
    }
}