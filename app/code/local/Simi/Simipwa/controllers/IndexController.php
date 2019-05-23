<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 12/8/17
 * Time: 10:58 AM
 */
class Simi_Simipwa_IndexController extends Mage_Core_Controller_Front_Action
{
    public function messageAction()
    {
        $message = Mage::getModel('simipwa/message')->getMessage();
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
            $img = Mage::getUrl('', array('_secure' => true)) . 'media/'.$message_info['image_url'];
            $message_info['image_url'] = $img;
        }

        $message_info['logo_icon'] = Mage::getStoreConfig('simipwa/notification/logo');
        if (Mage::getStoreConfig('simipwa/general/pwa_enable')){
            if(!Mage::getStoreConfig('simipwa/general/pwa_main_url_site')){
                $message_info['pwa_url'] = Mage::getUrl('pwa', array('_secure'=>true));
            }
        }

        $result = array(
            "notification" => $message_info
        );
        $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody(json_encode($result));
    }

    public function registerAction()
    {
        $data = $this->getRequest()->getRawBody();
        $data = (array) json_decode($data);
        $agent = Mage::getModel('simipwa/agent');
        if (!$data['endpoint'])
            throw new Exception(Mage::helper('simipwa')->__('No Endpoint Sent'), 4);
        //echo json_encode($agent->load($dataAgent['endpoint'],'endpoint')->getId());die;

        try {
            if(!$agent->load($data['endpoint'], 'endpoint')->getId()){
                $user_agent = '';
                if ($_SERVER["HTTP_USER_AGENT"]) {
                    $user_agent = $_SERVER["HTTP_USER_AGENT"];
                }

                $ip = $_SERVER['REMOTE_ADDR'];
                $details = json_decode(file_get_contents("http://ipinfo.io/{$ip}/json"));
                if($_SERVER['SERVER_NAME'] == 'localhost'){
                    $details->city = 'Simi';
                    $details->country = 'Ha Noi';
                }
                $endpoint = $data['endpoint'];
                $number = strrpos($data['endpoint'], '/');
                $endpoint_key = substr($data['endpoint'], $number+1);
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

            $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
            $this->getResponse()->setBody(json_encode($agent->getData()));
        }catch (Exception $e){
            throw new Exception($e, 4);
        }
    }

    public function deleteAction()
    {
        $data = $this->getRequest()->getRawBody();
        $dataAgent = (array) json_decode($data);
        $result = array();
        if (!$dataAgent['endpoint'])
            throw new Exception(Mage::helper('simipwa')->__('No Endpoint Sent'), 4);
        $agent = Mage::getModel('simipwa/agent')->load($dataAgent['endpoint'], 'endpoint');
        if ($agent->getId()){
            try{
                $message = Mage::getModel('simipwa/message')->load($agent->getId(), 'device_id');
                if ($message->getId()){
                    $message->delete();
                }

                $agent->delete();

                $result =  Mage::helper('simipwa')->__('PWA Agent was removed successfully !');
            }
            catch (Exception $e){
                $error = $e->getMessage();
                throw new Exception($error, 4);
            }
        }

        $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody(
            json_encode(
                array(
                "message" => $result
                )
            )
        );
    }

    public function configAction()
    {
        if(!Mage::getSingleton('core/session')->getData('simiconnector_platform')||
            Mage::getSingleton('core/session')->getData('simiconnector_platform') != 'pwa'
        ) {
            Mage::getSingleton('core/session')->setData('simiconnector_platform', 'pwa');
        }

        $enable = (!Mage::getStoreConfig('simipwa/general/pwa_enable') || !Mage::getStoreConfig('simipwa/general/pwa_main_url_site'))?0:1;
        $build_time = Mage::getStoreConfig('simipwa/general/build_time') ? Mage::getStoreConfig('simipwa/general/build_time') : 0;
        $build_time_sandbox = Mage::getStoreConfig('simipwa/general/build_time_sandbox') ? Mage::getStoreConfig('simipwa/general/build_time_sandbox') : 0;
        $result = array(
            'pwa' => array(
                //notification and offline
                'enable_noti' => (int)Mage::getStoreConfig('simipwa/notification/enable'),
                // enable pwa
                'enable' => $enable,
                'pwa_type' => Mage::getStoreConfig('simipwa/general/pwa_main_url_site') ? 'live' : 'sandbox',
                'build_time' => (int)$build_time,
                'build_time_sandbox' => (int)$build_time_sandbox
            )
        );
        $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody(json_encode($result));
    }

    public function updateConfigAction(){
        try{
            $type = $this->getRequest()->getParam('build_type');
            if (!$type) $type = 'sandbox';
            $token = Mage::getStoreConfig('simipwa/general/dashboard_token_key');
            $token = $token?$token:Mage::getStoreConfig('simiconnector/general/token_key');
            if (!$token || ($token == ''))
                throw new Exception(Mage::helper('simipwa')->__('Please fill your Token and Secret key on SimiCart connector settings'), 4);

            $dashboard_url = Mage::getStoreConfig('simipwa/general/dashboard_url');
            $dashboard_url = $dashboard_url?$dashboard_url:'https://www.simicart.com';
            $config = file_get_contents($dashboard_url . "/appdashboard/rest/app_configs/bear_token/".$token.'/pwa/1');

            if (!$config || (!$config = json_decode($config, 1)))
                throw new Exception(
                    Mage::helper('simipwa')->__(
                        'We cannot connect To SimiCart, please check your filled token, or check if 
                your server allows connections to SimiCart website'
                    ), 4
                );

            $buildTime = time();

            Mage::helper('simipwa')->updateConfigJS($config,$buildTime,$type);

            $result = array(
                "pwa" => array('success' => true)
            );
            if($type == 'sandbox'){
                $result['pwa']['build_time_sandbox'] = $buildTime;
            }else{
                $result['pwa']['build_time'] = $buildTime;
            }
            $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
            $this->getResponse()->setBody(json_encode($result));
        }catch (Exception $e){
            throw new Exception(Mage::helper('simipwa')->__($e));
        }

    }

//    public function installDbAction(){
//        $setup = new Mage_Core_Model_Resource_Setup();
//        $installer = $setup;
//        $installer->startSetup();
//        $installer->run("
//            DROP TABLE IF EXISTS {$installer->getTable('simipwa_agent')};
//            DROP TABLE IF EXISTS {$installer->getTable('simipwa_message')};
//            DROP TABLE IF EXISTS {$installer->getTable('simipwa_social_customer_mapping')};
//             CREATE TABLE {$installer->getTable('simipwa_agent')} (
//                `agent_id` int(11) unsigned NOT NULL auto_increment,
//                `user_agent` text NULL default '',
//                `endpoint` VARCHAR(255) NULL DEFAULT  '',
//                `endpoint_key` text NULL  DEFAULT  '',
//                `p256dh_key` text NULL  DEFAULT '',
//                `auth_key` text NULL DEFAULT '',
//                `created_at` datetime NOT NULL default '0000-00-00 00:00:00' ,
//                `status` SMALLINT(2) NOT NULL DEFAULT 2,
//                `city` VARCHAR(255) NULL DEFAULT '',
//                `country` VARCHAR (255) NULL  DEFAULT  '',
//                PRIMARY KEY (`agent_id`)
//                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
//
//            CREATE TABLE {$installer->getTable('simipwa_message')} (
//              `message_id` INT (11) unsigned NOT NULL auto_increment,
//              `device_id` VARCHAR (255) NOT NULL DEFAULT '',
//              `notice_title` varchar(255) NULL default '',
//                `notice_url` varchar(255) NULL default '',
//                `notice_content` text NULL default '',
//                `type` smallint(5) unsigned DEFAULT 1,
//                `category_id` int(10) unsigned  NOT NULL,
//                `product_id` int(10) unsigned  NOT NULL,
//                `image_url` varchar(255) NOT NULL default '',
//                `created_time` datetime NOT NULL default '0000-00-00 00:00:00' ,
//                `notice_type` smallint(5) unsigned DEFAULT 2,
//                `status` smallint(5) unsigned,
//                PRIMARY KEY (`message_id`)
//            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
//
//            CREATE TABLE {$installer->getTable('simipwa_social_customer_mapping')} (
//                `id` int(11) unsigned NOT NULL auto_increment,
//                `customer_id` int(11) NULL default 0,
//                `social_user_id` VARCHAR(255) NULL DEFAULT  '',
//                `provider_id` VARCHAR(255) NULL DEFAULT  '',
//                PRIMARY KEY (`id`)
//            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
//        ");
//        $installer->endSetup();
//        echo "success";
//    }
}