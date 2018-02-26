<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 11/20/17
 * Time: 5:13 PM
 */
class Simi_Simipwa_Model_Api_Simipwas extends Simi_Simiconnector_Model_Api_Abstract {
    protected $_DEFAULT_ORDER = 'agent_id';
    public $message_info;
    public function setBuilderQuery(){
        $data = $this->getData();
        $parameters = $data['params'];

        //echo json_encode($data);die;
        if (isset($data['resourceid']) && $data['resourceid']) {
            $this->builderQuery = Mage::getModel('simipwa/agent')->load($data['resourceid']);
        } else {
            if (isset($parameters['endpoint'])) {
                $endpoint = $parameters['endpoint'];
                if ($endpoint) {
                    $this->builderQuery = Mage::getModel('simipwa/message')->getMessage($endpoint);
                }
            }else{
                $this->builderQuery = Mage::getModel('simipwa/agent')->getCollection();
            }
        }
    }

    public function index(){
        $data = $this->getData();
        $message = $this->builderQuery;
        $message_info = $message->getData();
        $img  = null;
        if ($message_info['type'] == 1){
            $product = Mage::getModel('catalog/product')->load($message->getProductId());
            $message_info['notice_url'] = $product->getUrlPath();
        }
        if ($message_info['type'] == 2){
            $cate = Mage::getModel('catalog/category')->load($message->getCategoryId());
            $message_info['notice_url'] = $cate->getUrlPath();
        }
        if ($message_info['image_url']){
            $img = Mage::getUrl('',array('_secure' => true)) . 'media/'.$message_info['image_url'];
            $message_info['image_url'] = $img;
        }
        return array(
            "notification" => $message_info
        );
    }

    public function store(){
        $data = $this->getData();
        $dataAgent = (array) $data['contents'];
        $agent = Mage::getModel('simipwa/agent');
        if (!$dataAgent['endpoint'])
            throw new Exception(Mage::helper('simiconnector')->__('No Endpoint Sent'), 4);
        //echo json_encode($agent->load($dataAgent['endpoint'],'endpoint')->getId());die;

        try {
            if(!$agent->load($dataAgent['endpoint'],'endpoint')->getId()){
                $user_agent = '';
                if ($_SERVER["HTTP_USER_AGENT"]) {
                    $user_agent = $_SERVER["HTTP_USER_AGENT"];
                }
                $endpoint = $dataAgent['endpoint'];
                $number = strrpos($dataAgent['endpoint'],'/');
                $endpoint_key = substr($dataAgent['endpoint'],$number+1);
                $agent->setUserAgent($user_agent)
                    ->setEndpoint($endpoint)
                    ->setEndpointKey($endpoint_key)
                    ->setP256dhKey($dataAgent['keys']->p256dh)
                    ->setAuthKey($dataAgent['keys']->auth)
                    ->setCreatedAt(now())
                    ->save();
            }
            return $this->show();
        }catch (Exception $e){}
    }

    public function destroy(){
        $data = $this->getData();
        $result = array();
        $dataAgent = (array) $data['contents'];
        if (!$dataAgent['endpoint'])
            throw new Exception(Mage::helper('simiconnector')->__('No Endpoint Sent'), 4);
        $agent = Mage::getModel('simipwa/agent')->load($dataAgent['endpoint'],'endpoint');
        if ($agent->getId()){
            try{
                $message = Mage::getModel('simipwa/message')->load($agent->getId(),'device_id');
                if ($message->getId()){
                    $message->delete();
                }
                $agent->delete();

                $result[] =  Mage::helper('simipwa')->__('PWA Agent was removed successfully !');
            }
            catch (Exception $e){
                $error = $e->getMessage();
                throw new Exception($error,4);
            }
        }
        return array(
            "message" => $result
        );
    }
}