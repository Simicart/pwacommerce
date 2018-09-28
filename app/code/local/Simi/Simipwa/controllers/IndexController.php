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
        $filePath = Mage::getBaseDir().'/pwa/';
        $enable = (!Mage::getStoreConfig('simipwa/general/pwa_enable') || !Mage::getStoreConfig('simipwa/general/pwa_main_url_site'))?0:1;
        $build_time = Mage::getStoreConfig('simipwa/general/build_time') ? Mage::getStoreConfig('simipwa/general/build_time') : 0;
        $result = array(
            'pwa' => array(
                //notification and offline
                'enable_noti' => (int)Mage::getStoreConfig('simipwa/notification/enable'),
                // enable pwa
                'enable' => $enable,
                'build_time' => (int)$build_time
            )
        );
        $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody(json_encode($result));
    }

    public function updateConfigAction(){
        try{
            $token =  Mage::getStoreConfig('simiconnector/general/token_key');
            $secret_key =  Mage::getStoreConfig('simiconnector/general/secret_key');
            $logoUrlSetting = Mage::getStoreConfig('simipwa/general/logo_homepage');
            $app_image_logo = ($logoUrlSetting && $logoUrlSetting!='')?
                $logoUrlSetting : Mage::getStoreConfig('design/header/logo_src');
            if (!$token || !$secret_key || ($token == '') || ($secret_key == ''))
                throw new Exception(Mage::helper('simipwa')->__('Please fill your Token and Secret key on SimiCart connector settings'), 4);

            $config = file_get_contents("https://www.simicart.com/appdashboard/rest/app_configs/bear_token/".$token.'/pwa/1');
            if (!$config || (!$config = json_decode($config, 1)))
                throw new Exception(
                    Mage::helper('simipwa')->__(
                        'We cannot connect To SimiCart, please check your filled token, or check if 
                your server allows connections to SimiCart website'
                    ), 4
                );

            $url = $config['app-configs'][0]['url'];
            $buildTime = time();
            if ($config['app-configs'][0]['ios_link']) {
                try {
                    $iosId = explode('id', $config['app-configs'][0]['ios_link']);
                    $iosId = $iosId[1];
                    $iosId = substr($iosId, 0, 10);
                }
                catch (Exception $getIosUrlException) {
                    throw new Exception(Mage::helper('simipwa')->__($getIosUrlException));
                }
            }

            if ($config['app-configs'][0]['android_link']) {
                try {
                    $androidId = explode('id=', $config['app-configs'][0]['android_link']);
                    $androidId = $androidId[1];
                    $androidId = explode('?', $androidId);
                    $androidId = $androidId[0];
                }
                catch (Exception $getAndroidUrlException) {
                    throw new Exception(Mage::helper('simipwa')->__($getAndroidUrlException));
                }
            }
            //update config.js file
            $gaToken = Mage::getStoreConfig('simipwa/general/ga_token_key');
            $gaToken = $gaToken ? $gaToken : '';
            $app_splash_img_url = Mage::getStoreConfig('simipwa/general/splash_img') ;
            $mixPanelToken = Mage::getStoreConfig('simiconnector/mixpanel/token');
            $mixPanelToken = ($mixPanelToken && $mixPanelToken!=='')?$mixPanelToken:'5d46127799a0614259cb4c733f367541';
            $zopimKey = Mage::getStoreConfig('simiconnector/zopim/account_key');
            $base_name = Mage::getStoreConfig('simipwa/general/pwa_main_url_site') ? '' : 'pwa';
            $msConfigs = '
            var PWA_BUILD_TIME = "'.$buildTime.'";
            var SMCONFIGS = {
                merchant_url: "'.$url.'",
                api_path: "simiconnector/rest/v2/",
                merchant_authorization: "'.$secret_key.'",
                simicart_url: "https://www.simicart.com/appdashboard/rest/app_configs/",
                simicart_authorization: "'.$token.'",
                notification_api: "simipwa/index/",
                zopim_key: "'.$zopimKey.'",
                zopim_language: "en",
                base_name: "'.$base_name.'",
                show_social_login: {
                    facebook: 0,
                    google: 0,
                    twitter: 0
                },
                google_analytics:{
                    google_analytics_key: "' . trim($gaToken) . '"
                },
                mixpanel: {
                    token_key: "'.trim($mixPanelToken).'"
                },
                logo_url: "'.$app_image_logo.'",
                splash_screen : "'.$app_splash_img_url.'",
                magento_version : 1
            };
            ';

            foreach ($config['app-configs'] as $index=>$appconfig) {
                if ($appconfig['theme']) {
                    $theme = $appconfig['theme'];
                    $splash = $appconfig['splash'];
                    $msConfigs.= "
                var DEFAULT_COLORS = {
                    key_color: '".$theme['key_color']."',
                    top_menu_icon_color: '".$theme['top_menu_icon_color']."',
                    button_background: '".$theme['button_background']."',
                    button_text_color: '".$theme['button_text_color']."',
                    menu_background: '".$theme['menu_background']."',
                    menu_text_color: '".$theme['menu_text_color']."',
                    menu_line_color: '".$theme['menu_line_color']."',
                    menu_icon_color: '".$theme['menu_icon_color']."',
                    search_box_background: '".$theme['search_box_background']."',
                    search_text_color: '".$theme['search_text_color']."',
                    app_background: '".$theme['app_background']."',
                    content_color: '".$theme['content_color']."',
                    image_border_color: '".$theme['image_border_color']."',
                    line_color: '".$theme['line_color']."',
                    price_color: '".$theme['price_color']."',
                    special_price_color: '".$theme['special_price_color']."',
                    icon_color: '".$theme['icon_color']."',
                    section_color: '".$theme['section_color']."',
                    status_bar_background: '".$theme['status_bar_background']."',
                    status_bar_text: '".$theme['status_bar_text']."',
                    loading_color: '".$theme['loading_color']."',
                    splash_screen_color : '".$splash['color']."',
                    loading_splash_screen_color : '".$splash['loading_color']."',
                };
                        ";
                    break;
                }
            }

            if (isset($androidId) || isset($iosId)) {
                if (!isset($androidId))
                    $androidId = '';
                if (!isset($iosId))
                    $iosId = '';
                $msConfigs.=
                    "
                    var SMART_BANNER_CONFIGS = {
                        ios_app_id: '".$iosId."',
                        android_app_id: '".$androidId."',
                        app_store_language: '', 
                        title: '".$config['app-configs'][0]['app_name']."',
                        author: '".$config['app-configs'][0]['app_name']."',
                        button_text: 'View',
                        store: {
                            ios: 'On the App Store',
                            android: 'In Google Play',
                            windows: 'In Windows store'
                        },
                        price: {
                            ios: 'FREE',
                            android: 'FREE',
                            windows: 'FREE'
                        },
                    }; 
                        ";
            }
            $config = json_encode($config);
            $msConfigs.=
                "
                    var Simicart_Api = $config;
                ";

            $path_to_file = Mage::getBaseDir() .'/pwa/js/config/config.js';
            file_put_contents($path_to_file, $msConfigs);

            //update index.html file
            $prev_time = Mage::getStoreConfig('simipwa/general/build_time');
            $path_to_file = Mage::getBaseDir() .'/pwa/index.html';
            $file_contents = file_get_contents($path_to_file);
            $file_contents = str_replace("?v=$prev_time", "?v=$buildTime", $file_contents);
            file_put_contents($path_to_file, $file_contents);

            Mage::getConfig()->saveConfig('simipwa/general/build_time', $buildTime);
            Mage::app()->getCacheInstance()->cleanType(1);
            $result = array(
                "pwa" => array('success' => true,'buildtime'=>$buildTime)
            );
            $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
            $this->getResponse()->setBody(json_encode($result));
        }catch (Exception $e){
            throw new Exception(Mage::helper('simipwa')->__($e));
        }

    }
}