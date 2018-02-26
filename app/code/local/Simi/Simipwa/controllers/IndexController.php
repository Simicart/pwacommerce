<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 12/8/17
 * Time: 10:58 AM
 */
class Simi_Simipwa_IndexController extends Mage_Core_Controller_Front_Action
{
    public function messageAction(){
        $data = $this->getRequest()->getParams();
        $endpoint = $data['endpoint'];
        $message = Mage::getModel('simipwa/message')->getMessage($endpoint);
        $message_info = $message->getData();
        $img  = null;
        if ($message_info['type'] == 1){
            $product = Mage::getModel('catalog/product')->load($message->getProductId());
            $message_info['notice_url'] = $product->getUrlPath() . "?id=".$message_info["product_id"];
        }
        if ($message_info['type'] == 2){
            $cate = Mage::getModel('catalog/category')->load($message->getCategoryId());
            $message_info['notice_url'] = $cate->getUrlPath() . "?cat=".$message_info["category_id"];
        }
        if ($message_info['image_url']){
            $img = Mage::getUrl('',array('_secure' => true)) . 'media/'.$message_info['image_url'];
            $message_info['image_url'] = $img;
        }
        $result = array(
            "notification" => $message_info
        );
        $this->getResponse()->clearHeaders()->setHeader('Content-type','application/json',true);
        $this->getResponse()->setBody(json_encode($result));
    }

    public function registerAction(){
        $data = $this->getRequest()->getRawBody();
        $data = (array) json_decode($data);
        $agent = Mage::getModel('simipwa/agent');
        if (!$data['endpoint'])
            throw new Exception(Mage::helper('simipwa')->__('No Endpoint Sent'), 4);
        //echo json_encode($agent->load($dataAgent['endpoint'],'endpoint')->getId());die;

        try {
            if(!$agent->load($data['endpoint'],'endpoint')->getId()){
                $user_agent = '';
                if ($_SERVER["HTTP_USER_AGENT"]) {
                    $user_agent = $_SERVER["HTTP_USER_AGENT"];
                }
                $ip = $_SERVER['REMOTE_ADDR'];
                $details = json_decode(file_get_contents("http://ipinfo.io/{$ip}/json"));

                $endpoint = $data['endpoint'];
                $number = strrpos($data['endpoint'],'/');
                $endpoint_key = substr($data['endpoint'],$number+1);
                $agent->setUserAgent($user_agent)
                    ->setEndpoint($endpoint)
                    ->setEndpointKey($endpoint_key)
                    ->setP256dhKey($data['keys']->p256dh)
                    ->setAuthKey($data['keys']->auth)
                    ->setCreatedAt(now())
                    ->setCity($details->city)
                    ->setCountry($details->country)
                    ->save();
            }
            $this->getResponse()->clearHeaders()->setHeader('Content-type','application/json',true);
            $this->getResponse()->setBody(json_encode($agent->getData()));
        }catch (Exception $e){
            throw new Exception($e,4);
        }
    }

    public function deleteAction(){
        $data = $this->getRequest()->getRawBody();
        $dataAgent = (array) json_decode($data);
        $result = array();
        if (!$dataAgent['endpoint'])
            throw new Exception(Mage::helper('simipwa')->__('No Endpoint Sent'), 4);
        $agent = Mage::getModel('simipwa/agent')->load($dataAgent['endpoint'],'endpoint');
        if ($agent->getId()){
            try{
                $message = Mage::getModel('simipwa/message')->load($agent->getId(),'device_id');
                if ($message->getId()){
                    $message->delete();
                }
                $agent->delete();

                $result =  Mage::helper('simipwa')->__('PWA Agent was removed successfully !');
            }
            catch (Exception $e){
                $error = $e->getMessage();
                throw new Exception($error,4);
            }
        }
        $this->getResponse()->clearHeaders()->setHeader('Content-type','application/json',true);
        $this->getResponse()->setBody(json_encode(array(
            "message" => $result
        )));
    }
}