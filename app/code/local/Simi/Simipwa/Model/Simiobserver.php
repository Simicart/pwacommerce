<?php

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 11/20/17
 * Time: 5:12 PM
 */
class Simi_Simipwa_Model_Simiobserver
{
    public function simiSimiconnectorModelServerInitialize($observer)
    {
        $observerObject = $observer->getObject();
        $observerObjectData = $observerObject->getData();
        if ($observerObjectData['resource'] == 'simipwas' || $observerObjectData['resource'] == 'sitemaps') {
            $observerObjectData['module'] = 'simipwa';
        }
        $observerObject->setData($observerObjectData);
    }

    public function simiPwaChangeStoreView($observer){
    	$observerObject = $observer->getObject();
    	$data = $observerObject->getData();
    	if(isset($data['params']) && isset($data['params']['pwa'])){
    		$obj = $observer['object'];
    		$info = $obj->storeviewInfo;
    		$siteMap = Mage::helper('simipwa')->getSiteMaps();
            if($siteMap && isset($siteMap['sitemaps']))
    		  $info['urls'] = $siteMap['sitemaps'];
            $info['pwa_configs'] = array(
                'pwa_enable'=> Mage::getStoreConfig('simipwa/general/pwa_enable'),
                'pwa_url'=> Mage::getStoreConfig('simipwa/general/pwa_url'),
                'pwa_excluded_paths'=> Mage::getStoreConfig('simipwa/general/pwa_excluded_paths'),
            );
    		$obj->storeviewInfo = $info;    		
    	}    	
    }
    
    public function controllerActionPredispatch($observer) {
        /*
        if ($_SERVER['REMOTE_ADDR'] !== '27.72.100.84')
            return;
        */
        if (!Mage::getStoreConfig('simipwa/general/pwa_enable') || !Mage::getStoreConfig('simipwa/general/pwa_url') 
        ||(Mage::getStoreConfig('simipwa/general/pwa_url') == ''))
            return;
        $tablet_browser = 0;
        $mobile_browser = 0;

        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
            $tablet_browser++;
        }

        if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
            $mobile_browser++;
        }

        if ((strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml') !== false) or ((isset($_SERVER['HTTP_X_WAP_PROFILE']) or isset($_SERVER['HTTP_PROFILE'])))) {
            $mobile_browser++;
        }

        $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
        $mobile_agents = array(
            'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',
            'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',
            'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',
            'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',
            'newt','noki','palm','pana','pant','phil','play','port','prox',
            'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',
            'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',
            'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',
            'wapr','webc','winw','winw','xda ','xda-');

        if (in_array($mobile_ua,$mobile_agents)) {
            $mobile_browser++;
        }

        if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'opera mini') !== false) {
            $mobile_browser++;
            //Check for tablets on opera mini alternative headers
            $stock_ua = strtolower(isset($_SERVER['HTTP_X_OPERAMINI_PHONE_UA'])?$_SERVER['HTTP_X_OPERAMINI_PHONE_UA']:(isset($_SERVER['HTTP_DEVICE_STOCK_UA'])?$_SERVER['HTTP_DEVICE_STOCK_UA']:''));
            if (preg_match('/(tablet|ipad|playbook)|(android(?!.*mobile))/i', $stock_ua)) {
                $tablet_browser++;
            }
        }



        $uri = $_SERVER['REQUEST_URI'];
        $baseUrl = Mage::getStoreConfig(Mage_Core_Model_Url::XML_PATH_SECURE_URL);
        $currentUrl = Mage::helper('core/url')->getCurrentUrl();

        if (strpos($currentUrl, $baseUrl) !== false) {
            $uri = '/'.str_replace($baseUrl, '', $currentUrl);
        }
        
        $excludedUrls = array('admin', 'simiconnector', 'simicustompayment', 'payfort', 'simipwa', 'rest/v2');
        
        $excludedPaths = str_replace(' ', '', Mage::getStoreConfig('simipwa/general/pwa_excluded_paths'));
        $excludedPaths = explode(',', $excludedPaths);
        
        $excludedUrls = array_merge($excludedUrls, $excludedPaths);
        
        $isExcludedCase = false;

        foreach ($excludedUrls as $key => $excludedUrl) {
            if ($excludedUrl != '' && (strpos($uri, $excludedUrl) !== false)) {
                $isExcludedCase = true;
            }
        }
        if((($tablet_browser > 0)||($mobile_browser > 0)) && Mage::getStoreConfig('simipwa/general/pwa_main_url_site') && !$isExcludedCase){
            if(file_exists('./pwa/index.html')){
                require 'pwa/index.html';
                exit();
            }
        }
//        if (($tablet_browser > 0)||($mobile_browser > 0) && !$isExcludedCase) {
//            $url = Mage::getStoreConfig('simipwa/general/pwa_url').$uri;
//            header("Location: ".$url);
//        }
    }

    public function changeFileManifest(Varien_Event_Observer $observer){
        $name = Mage::getStoreConfig('simipwa/manifest/name') ? Mage::getStoreConfig('simipwa/manifest/name') : 'Progressive Web App';
        $short_name = Mage::getStoreConfig('simipwa/manifest/short_name') ? Mage::getStoreConfig('simipwa/manifest/short_name') : 'PWA';
        $default_icon = Mage::getBaseDir().'/pwa/images/default_icon_512_512.png';
        $icon =  Mage::getStoreConfig('simipwa/manifest/logo') ? Mage::getStoreConfig('simipwa/manifest/logo') : $default_icon;
        $start_url = Mage::getStoreConfig('simipwa/general/pwa_main_url_site') ? '/' : '/pwa/';
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
              \"theme_color\": \"#3399cc\",
              \"background_color\": \"#ffffff\",
              \"gcm_sender_id\" : \"832571969235\"
            }";
        $filePath = Mage::getBaseDir() . '/manifest.json';
        //zend_debug::dump($icon);die;
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $file = @fopen($filePath, 'w+');
        if ($file) {
            file_put_contents($filePath, $content);
        }
    }
}