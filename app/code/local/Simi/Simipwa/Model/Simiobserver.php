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
        if ($observerObjectData['resource'] == 'simipwas' || $observerObjectData['resource'] == 'sitemaps'
            || $observerObjectData['resource'] == 'sociallogins') {
            $observerObjectData['module'] = 'simipwa';
        }
        $observerObject->setData($observerObjectData);
    }

    public function simiPwaChangeStoreView($observer)
    {
        $observerObject = $observer->getObject();
        $data = $observerObject->getData();
        if (isset($data['params']) && isset($data['params']['pwa'])) {
            $obj = $observer['object'];
            $info = $obj->storeviewInfo;
            try {
                $siteMap = Mage::helper('simipwa')->getSiteMaps();
                if ($siteMap && isset($siteMap['sitemaps']))
                    $info['urls'] = $siteMap['sitemaps'];
            } catch (Exception $e) {
                $info['urls'] = array();
            }
            $info['pwa_configs'] = array(
                'pwa_enable' => Mage::getStoreConfig('simipwa/general/pwa_enable'),
                'pwa_url' => Mage::getStoreConfig('simipwa/general/pwa_url'),
                'pwa_excluded_paths' => Mage::getStoreConfig('simipwa/general/pwa_excluded_paths'),
            );
            $GATokenKey = Mage::getStoreConfig('simipwa/general/ga_token_key');
            if ($GATokenKey) {
                $info['ga_token_key'] = $GATokenKey;
            }
            $obj->storeviewInfo = $info;
        }
    }

    public function controllerActionPredispatch($observer)
    {
        /*
        if ($_SERVER['REMOTE_ADDR'] !== '27.72.100.84')
            return;
        */
        $agent = Mage::helper('simipwa')->getBrowser();

        $excludedBrowser = array('Opera');
        if(in_array($agent['name'], $excludedBrowser)) return;

        // check version Chrome
        $checkVersion = true;
        foreach ($agent['browser'] as $key => $value) {
            if($value == 'Chrome'){
                $version = $agent['version'][$key];
                $version = explode('.', $version);
                $version = (int)$version[0];
                if($version < 40){
                    $checkVersion = false;
                    break;
                }
            }
        }
        if(!$checkVersion) return;

        $controller = $observer->getControllerAction();
        $request_path = $controller->getRequest()->getRequestString();
        $pwa_type = strpos($request_path, 'pwa-sandbox') !== false ? 'sandbox':'live';

        if (!Mage::getStoreConfig('simipwa/general/pwa_enable'))
            return;

        if($pwa_type == 'sandbox'){
            $this->renderPWA('sandbox',$observer);
            return;
        }

        if (!Mage::getStoreConfig('simipwa/general/pwa_main_url_site'))
            return;

        $redirectIps = Mage::getStoreConfig('simipwa/general/pwa_redirect_ips');
        if ($redirectIps && $redirectIps != '' &&
            !in_array($_SERVER['REMOTE_ADDR'], explode(',', $redirectIps), true))
            return;

        $tablet_browser = 0;
        $mobile_browser = 0;

        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
            $tablet_browser++;
        }

        if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
            $mobile_browser++;
        }

        if ((strpos(strtolower($_SERVER['HTTP_ACCEPT']), 'application/vnd.wap.xhtml+xml') !== false) or ((isset($_SERVER['HTTP_X_WAP_PROFILE']) or isset($_SERVER['HTTP_PROFILE'])))) {
            $mobile_browser++;
        }

        $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
        $mobile_agents = array(
            'w3c ', 'acs-', 'alav', 'alca', 'amoi', 'audi', 'avan', 'benq', 'bird', 'blac',
            'blaz', 'brew', 'cell', 'cldc', 'cmd-', 'dang', 'doco', 'eric', 'hipt', 'inno',
            'ipaq', 'java', 'jigs', 'kddi', 'keji', 'leno', 'lg-c', 'lg-d', 'lg-g', 'lge-',
            'maui', 'maxo', 'midp', 'mits', 'mmef', 'mobi', 'mot-', 'moto', 'mwbp', 'nec-',
            'newt', 'noki', 'palm', 'pana', 'pant', 'phil', 'play', 'port', 'prox',
            'qwap', 'sage', 'sams', 'sany', 'sch-', 'sec-', 'send', 'seri', 'sgh-', 'shar',
            'sie-', 'siem', 'smal', 'smar', 'sony', 'sph-', 'symb', 't-mo', 'teli', 'tim-',
            'tosh', 'tsm-', 'upg1', 'upsi', 'vk-v', 'voda', 'wap-', 'wapa', 'wapi', 'wapp',
            'wapr', 'webc', 'winw', 'winw', 'xda ', 'xda-');

        if (in_array($mobile_ua, $mobile_agents)) {
            $mobile_browser++;
        }

        if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'opera mini') !== false) {
            $mobile_browser++;
            //Check for tablets on opera mini alternative headers
            $stock_ua = strtolower(isset($_SERVER['HTTP_X_OPERAMINI_PHONE_UA']) ? $_SERVER['HTTP_X_OPERAMINI_PHONE_UA'] : (isset($_SERVER['HTTP_DEVICE_STOCK_UA']) ? $_SERVER['HTTP_DEVICE_STOCK_UA'] : ''));
            if (preg_match('/(tablet|ipad|playbook)|(android(?!.*mobile))/i', $stock_ua)) {
                $tablet_browser++;
            }
        }


        $uri = $_SERVER['REQUEST_URI'];
        $baseUrl = Mage::getStoreConfig(Mage_Core_Model_Url::XML_PATH_SECURE_URL);
        $currentUrl = Mage::helper('core/url')->getCurrentUrl();

        if (strpos($currentUrl, $baseUrl) !== false) {
            $uri = '/' . str_replace($baseUrl, '', $currentUrl);
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
        if ((($tablet_browser > 0) || ($mobile_browser > 0)) && !$isExcludedCase) {
            $this->renderPWA('live',$observer);
        }
    }

    public function changeFileManifest(Varien_Event_Observer $observer)
    {
        if (!Mage::getStoreConfig('simipwa/manifest/enable')) return;
        Mage::helper('simipwa')->updateManifest();
    }

    function getPreloadConfig($page){
        if (Mage::getStoreConfig('simipwa/preload_config/enable')){
            $data = explode(',',$page);
            return $data;
        }
    }

    public function prerenderHeader($controller)
    {
        try {
            $store = Mage::app()->getStore();
            $manifestContent = file_get_contents('./pwa/assets-manifest.json');
            // preload homepage
            $homeJs = array();
            $preloadedHomejs = false;
            $preloadJs = $this->getPreloadConfig(Mage::getStoreConfig('simipwa/preload_config/homepage'));
            foreach ($preloadJs as $js){
                $homeJs[] = $js;
            }
            
            $preloadData = array('preload_js'=>array());

            $uri = $_SERVER['REQUEST_URI'];
            $uri = ltrim($uri, '/');

            $uriparts = explode("pwa/", $uri);
            if ($uriparts && isset($uriparts[1]))
                $uri = $uriparts[1];
            $uriparts = explode("?", $uri);
            if ($uriparts && isset($uriparts[1]))
                $uri = $uriparts[0];
            $urlModel = Mage::getResourceModel('catalog/url');
            $match = $urlModel->getRewriteByRequestPath($uri, Mage::app()->getStore()->getId());
            if ($match && $match->getId()) {
                if ($match->getData('product_id')) {
                    $product = Mage::getModel('catalog/product')
                        ->load($match->getData('product_id'));
                    if ($product->getId()) {
                        $preloadData['meta_title'] = $product->getMetaTitle() ? $product->getMetaTitle() : $product->getName();
                        $preloadData['meta_description'] = $product->getMetaDescription() ? $product->getMetaDescription() : substr($product->getDescription(), 0, 255);

                        $preloadJs = $this->getPreloadConfig(Mage::getStoreConfig('simipwa/preload_config/product_detail'));
                        foreach ($preloadJs as $js){
                            $preloadData['preload_js'][] = $js;
                        }
                    }
                } else if ($match->getData('category_id')) {
                    $category = Mage::getModel('catalog/category')->load($match->getData('category_id'));
                    if ($category->getId()) {
                        $collection = $category->getResourceCollection();
                        $pathIds = array_reverse($category->getPathIds());
                        $collection->addAttributeToSelect('name');
                        $collection->addAttributeToFilter('entity_id', array('in' => $pathIds));
                        $group = Mage::getModel('core/store_group')->load($store->getGroupId());
                        $catNamearray = array();
                        foreach ($collection as $cat) {
                            $catNamearray[$cat->getId()] = $cat->getName();
                        }
                        $metaTitle = array();
                        foreach ($pathIds as $index => $path) {
                            if ($path == $group->getData('root_category_id'))
                                break;
                            $metaTitle[] = $catNamearray[$path];
                        }
                        $metaTitle = implode(' - ', $metaTitle);
                        $preloadData['meta_title'] = $metaTitle ? $metaTitle : $category->getName();
                        $preloadData['meta_description'] = $preloadData['meta_title'];
                        $preloadJs = $this->getPreloadConfig(Mage::getStoreConfig('simipwa/preload_config/product_list'));
                        foreach ($preloadJs as $js){
                            $preloadData['preload_js'][] = $js;
                        }
                    }
                } else {
                    if ($homeJs) {
                        $preloadedHomejs = true;
                        $preloadData['preload_js'] = $homeJs;
                    }
                }
            } else {
                $preloadedHomejs = true;
                $preloadData['preload_js'] = $homeJs;
            }
        } catch (Exception $e) {

        }

        $headerString = '';
        if (isset($preloadData['meta_title'])) {
            $headerString .= '<title>' . $preloadData['meta_title'] . '</title>';
        }

        if (isset($preloadData['meta_description'])) {
            $headerString .= '<meta name="description" content="' . $preloadData['meta_description'] . '"/>';
        }
        if (!count($preloadData['preload_js'])) {
            $preloadData['preload_js'] = $homeJs; // add home
        }
        $mainJS = $this->getPreloadConfig(Mage::getStoreConfig('simipwa/preload_config/main_js'));
        foreach ($mainJS as $js){
            array_unshift($preloadData['preload_js'],$js);
        }
        $url_config = '/pwa/js/config/config.js?v='.Mage::getStoreConfig('simipwa/general/build_time');
        array_unshift($preloadData['preload_js'],$url_config);
        if (count($preloadData['preload_js'])) {
            foreach ($preloadData['preload_js'] as $preload_js) {
                $as = 'script';
                $headerString.= '<link rel="preload" as="'.$as.'" href="' . $preload_js . '">';
            }
        }
        try {
            //Add Storeview API
            $headerString .= Mage::helper('simipwa')->addStoreviewPwa($controller);

            //Add HOME API
            if (false) {
            //if ($preloadedHomejs) {
                $homeModel = Mage::getModel('simiconnector/api_homes');
                $data = [
                    'resource'       => 'homes',
                    'resourceid'     => 'lite',
                    'params'         => [
                        'email'=>null,
                        'password'=>null,
                        'get_child_cat'=>true,
                        'image_width'=>300,
                        'image_height'=>300,
                    ],
                    'contents_array' => [],
                    'is_method'      => 1, //GET
                    'module'         => 'simiconnector',
                    'controller'     => $controller,
                ];
                $homeModel->setData($data);
                $homeModel->setBuilderQuery();
                $homeModel->setSingularKey('homes');
                $homeModel->setPluralKey('homes');
                $homeAPI = json_encode($homeModel->show());
                $headerString .= '
            <script type="text/javascript">
                var SIMICONNECTOR_HOME_API = '.$homeAPI.';
            </script>';
            }
        }catch (\Exception $e) {

        }
        return $headerString;
    }

    public function prerenderHeaderSandbox($controller)
    {
        try{
            $manifestContent = file_get_contents('./pwa_sandbox/assets-manifest.json');
            $manifestContent = (array)json_decode($manifestContent);
            $urlConfig = '/pwa_sandbox/js/config/config.js?v='.Mage::getStoreConfig('simipwa/general/build_time_sandbox');
            $preload_js = array($urlConfig,$manifestContent['main.js'],$manifestContent['vendors-main.js']);
            $uri = $_SERVER['REQUEST_URI'];
            $uri = trim($uri, '/');
            if($uri == 'pwa-sandbox'){
                $preload_js[] = $manifestContent['Home.js'];
                $preload_js[] = $manifestContent['Banner.js'];
            }
            $headerString = '';
            if(count($preload_js)){
                foreach ($preload_js as $js) {
                    $headerString.= '<link rel="preload" as="script" href="' . $js . '">';
                }
            }
            $headerString .= Mage::helper('simipwa')->addStoreviewPwa($controller);
            return $headerString;
        }
        catch (Exception $e){}
    }

    public function renderPWA($type,$observer){
        $pwa_html = $type == 'sandbox' ? './pwa_sandbox/index.html' : './pwa/index.html';
        if (file_exists($pwa_html)) {
            $controller = $observer->getControllerAction();
            $controller->getRequest()->setDispatched(true);
            $controller->setFlag(
                '',
                Mage_Core_Controller_Front_Action::FLAG_NO_DISPATCH,
                true
            );

            $content = file_get_contents($pwa_html);
            if($type == 'sandbox'){
                if ($prerenderedHeader = $this->prerenderHeaderSandbox($controller)) {
                    $content = str_replace('<head>', '<head>' . $prerenderedHeader, $content);
                }
            }else{
                if ($prerenderedHeader = $this->prerenderHeader($controller)) {
                    $content = str_replace('<head>', '<head>' . $prerenderedHeader, $content);
                }
            }
            $response = $controller->getResponse();
            $response->setHeader('Content-type', 'text/html; charset=utf-8', true);
            $response->setBody($content);
        }
    }

}