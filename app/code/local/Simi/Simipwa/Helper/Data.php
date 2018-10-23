<?php

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 11/20/17
 * Time: 4:28 PM
 */
class Simi_Simipwa_Helper_Data extends Mage_Core_Helper_Data
{
    public function getSiteMaps()
    {
        $filePath = Mage::getBaseDir('code') . DS . "local" . DS . "Simi" . DS . "Simipwa" . DS . "Assest" . DS . "sitemaps.json";

        if (file_exists($filePath)) {
            $sitemaps = file_get_contents($filePath);
            if (!$sitemaps) {
                $sitemaps = $this->getDataSiteMaps();
                file_put_contents($filePath, $sitemaps);
                return json_decode($sitemaps, true);
            }

            return json_decode($sitemaps, true);
        } else {
            $file = @fopen($filePath, 'w+');
            $sitemaps = $this->getDataSiteMaps();
            if ($file) {
                file_put_contents($filePath, $sitemaps);
                return json_decode($sitemaps, true);
            }

            return json_decode($sitemaps, true);
        }

    }

    public function getDataSiteMaps()
    {
        $storeId = Mage::app()
            ->getWebsite(true)
            ->getDefaultGroup()
            ->getDefaultStoreId();

        $baseUrl = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
        // get categories
        $collection = Mage::getModel('simipwa/catemap')->getCollection($storeId);                
        $categories = new Varien_Object();
        $categories->setItems($collection);
        $categories_url = array();
        foreach ($categories->getItems() as $item) {
            $categories_url[] = array(
                'id' => $item->getId(),
                'url' => $item->getUrl(),
                'hasChild' => $item->getChild() ? true : false,
                'name' => $item->getCategoryName()
            );
        }

        $urls['categories_url'] = $categories_url;        
//         get products
        $collection = new Mage_Sitemap_Model_Resource_Catalog_Product();
        $collection = $collection->getCollection($storeId);
//        $collection = Mage::getResourceModel('sitemap/catalog_product')->getCollection($storeId);
        $products = new Varien_Object();
        $products->setItems($collection);
        $products_url = array();
        foreach ($products->getItems() as $item) {
            $products_url[] = array(
                'id' => $item->getId(),
                'url' => $item->getUrl(),
            );
        }

        $urls['products_url'] = $products_url;
        unset($collection);

//         get cms pages
        $cms_url = array();
        $collection = new Mage_Sitemap_Model_Resource_Cms_Page();
        $collection = $collection->getCollection($storeId);
        foreach ($collection as $item) {
            $cms_url[] = array(
                'id' => $item->getId(),
                'url' => $item->getUrl(),
            );
        }

        $urls['cms_url'] = $cms_url;
        unset($collection);
        $result = array();
        $result['sitemaps'] = $urls;
        return json_encode($result);
    }

    public function synSiteMaps()
    {
        $filePath = Mage::getBaseDir('code') . DS . "local" . DS . "Simi" . DS . "Simipwa" . DS . "Assest" . DS . "sitemaps.json";        
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $file = @fopen($filePath, 'w+');
        $sitemaps = $this->getDataSiteMaps();        
        if ($file) {
            file_put_contents($filePath, $sitemaps);
            return true;
        }

        return false;
    }

    public function IsEnableForWebsite()
    {
        return Mage::getStoreConfig('simipwa/notification/enable');
    }

    public function _removeFolder($folder)
    {
        if (is_dir($folder))
            $dir_handle = opendir($folder);
        if (!$dir_handle)
            return false;
        while($file = readdir($dir_handle)) {
            if ($file != "." && $file != "..") {
                if (!is_dir($folder."/".$file))
                    unlink($folder."/".$file);
                else
                    $this->_removeFolder($folder.'/'.$file);
            }
        }

        closedir($dir_handle);
        rmdir($folder);
        return true;
    }

    public function updateManifest($type='sandbox')
    {
        $isSandbox = $type == 'sandbox';
        $name = Mage::getStoreConfig('simipwa/manifest/name') ? Mage::getStoreConfig('simipwa/manifest/name') : 'Progressive Web App';
        $short_name = Mage::getStoreConfig('simipwa/manifest/short_name') ? Mage::getStoreConfig('simipwa/manifest/short_name') : 'PWA';
        $default_icon = $isSandbox ? '/pwa_sandbox/images/default_icon_512_512.png' : '/pwa/images/default_icon_512_512.png';
        $icon =  Mage::getStoreConfig('simipwa/manifest/logo') ? Mage::getStoreConfig('simipwa/manifest/logo') : $default_icon;
        $start_url = Mage::getStoreConfig('simipwa/general/pwa_main_url_site') ? '/' : '/pwa/';
        if($type == 'sandbox') $start_url = '/pwa-sandbox/';
        if (!class_exists('Simi_Simiconnector_Controller_Action')) {
            $start_url = '/';
        }
        $theme_color = Mage::getStoreConfig('simipwa/manifest/theme_color') ? '#'.Mage::getStoreConfig('simipwa/manifest/theme_color') : '#3399cc';
        $background_color = Mage::getStoreConfig('simipwa/manifest/background_color') ? '#'.Mage::getStoreConfig('simipwa/manifest/background_color') : '#ffffff';
        $content = "{
              \"short_name\": \"$short_name\",
              \"name\": \"$name\",
              \"icons\": [
                {
                  \"src\": \"$icon\",
                  \"sizes\": \"192x192\",
                  \"type\": \"image/png\"
                },
                {
                  \"src\": \"$icon\",
                  \"sizes\": \"256x256\",
                  \"type\": \"image/png\"
                },
                {
                  \"src\": \"$icon\",
                  \"sizes\": \"384x384\",
                  \"type\": \"image/png\"
                },
                {
                  \"src\": \"$icon\",
                  \"sizes\": \"512x512\",
                  \"type\": \"image/png\"
                }
              ],
              \"start_url\": \"$start_url\",
              \"display\": \"standalone\",
              \"theme_color\": \"$theme_color\",
              \"background_color\": \"$background_color\",
              \"gcm_sender_id\" : \"832571969235\"
            }";
//        $filePath = Mage::getBaseDir() . '/pwa/manifest.json'; // for pwa
//        $filePath1 = Mage::getBaseDir() . '/manifest.json'; // for free version
        //zend_debug::dump($icon);die;
        $this->updateFile('/pwa/simi-manifest.json',$content); // for pwa
        $this->updateFile('/pwa_sandbox/simi-manifest.json',$content); // for pwa sandbox
        $this->updateFile('/simi-manifest.json',$content); // for free version
    }

    public function updateFile($url,$content){
        $filePath = Mage::getBaseDir() . $url;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        $file = @fopen($filePath, 'w+');
        if ($file) {
            file_put_contents($filePath, $content);
        }
    }

    public function IsEnableAddToHomescreen()
    {
        return Mage::getStoreConfig('simipwa/manifest/enable');
    }

    public function isJSON($string)
    {
        return is_string($string) && is_array(json_decode($string, true)) ? true : false;
    }

    public function getApi($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    public function SyncApi($config,$url,$replaceStr)
    {
        if($config){
            $path_to_file = Mage::getBaseDir() .'/pwa/index.html';
            if(file_exists($path_to_file)){
                $file_contents = file_get_contents($path_to_file);
                $api = $this->getApi($url);
                if($this->isJSON($api)){
                    //update index.html file
                    $file_contents = str_replace($replaceStr, $api, $file_contents);
                    file_put_contents($path_to_file, $file_contents);
                }
            }
        }
    }

    public function updateConfigJs($config,$buildTime,$type='sandbox'){
        $token =  Mage::getStoreConfig('simiconnector/general/token_key');
        $secret_key =  Mage::getStoreConfig('simiconnector/general/secret_key');
        $logoUrlSetting = Mage::getStoreConfig('simipwa/general/logo_homepage');
        $app_image_logo = ($logoUrlSetting && $logoUrlSetting!='')?
            $logoUrlSetting : Mage::getStoreConfig('design/header/logo_src');
        if (!$token || !$secret_key || ($token == '') || ($secret_key == ''))
            throw new Exception(Mage::helper('simipwa')->__('Please fill your Token and Secret key on SimiCart connector settings'), 4);
        //update config.js file
        $url = $config['app-configs'][0]['url'];
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
        $gaToken = Mage::getStoreConfig('simipwa/general/ga_token_key');
        $gaToken = $gaToken ? $gaToken : '';
        $app_splash_img_url = Mage::getStoreConfig('simipwa/general/splash_img') ;
        $mixPanelToken = Mage::getStoreConfig('simiconnector/mixpanel/token');
        $mixPanelToken = ($mixPanelToken && $mixPanelToken!=='')?$mixPanelToken:'5d46127799a0614259cb4c733f367541';
        $zopimKey = Mage::getStoreConfig('simiconnector/zopim/account_key');
        $base_name = 'pwa-sandbox';
        if($type !== 'sandbox')
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
        if($type == 'sandbox')
            $path_to_file = Mage::getBaseDir() . '/pwa_sandbox/js/config/config.js';
        file_put_contents($path_to_file, $msConfigs);

        //update index.html file
        $prev_time = $type == 'sandbox' ? Mage::getStoreConfig('simipwa/general/build_time_sandbox') : Mage::getStoreConfig('simipwa/general/build_time');
        $path_to_file = $type == 'sandbox' ?  Mage::getBaseDir() .'/pwa_sandbox/index.html' : Mage::getBaseDir() .'/pwa/index.html';
        $file_contents = file_get_contents($path_to_file);
        $file_contents = str_replace("?v=$prev_time", "?v=$buildTime", $file_contents);
        file_put_contents($path_to_file, $file_contents);

        if($type == 'sandbox'){
            Mage::getConfig()->saveConfig('simipwa/general/build_time_sandbox', $buildTime);
        }else{
            Mage::getConfig()->saveConfig('simipwa/general/build_time', $buildTime);
        }
        Mage::app()->getCacheInstance()->cleanType(1);
    }
}