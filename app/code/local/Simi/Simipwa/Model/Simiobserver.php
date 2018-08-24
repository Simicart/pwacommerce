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
        if (!Mage::getStoreConfig('simipwa/general/pwa_enable'))
            return;
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
            if (file_exists('./pwa/index.html')) {
                $content = file_get_contents('./pwa/index.html');
                if ($prerenderedHeader = $this->prerenderHeader()) {
                    $content = str_replace('<head>', '<head>' . $prerenderedHeader, $content);
                }

                $controller = $observer->getControllerAction();
                $controller->getRequest()->setDispatched(true);
                $controller->setFlag(
                    '',
                    Mage_Core_Controller_Front_Action::FLAG_NO_DISPATCH,
                    true
                );
                $response = $controller->getResponse();
                $response->setHeader('Content-type', 'text/html; charset=utf-8', true);
                $response->setBody($content);
            }
        }
    }

    public function changeFileManifest(Varien_Event_Observer $observer)
    {
        if (!Mage::getStoreConfig('simipwa/manifest/enable')) return;
        Mage::helper('simipwa')->updateManifest();
    }

    public function prerenderHeader()
    {
        try {
            $store = Mage::app()->getStore();
            $manifestContent = file_get_contents('./pwa/assets-manifest.json');
            if ($manifestContent && $manifestJsFiles = json_decode($manifestContent, true)) {
                if (isset($manifestJsFiles['CategoryRoot.js'])) {
                    $catrootJs = $manifestJsFiles['CategoryRoot.js'];
                }
                if (isset($manifestJsFiles['Products.js'])) {
                    $productsJs = $manifestJsFiles['Products.js'];
                }
                if (isset($manifestJsFiles['Product.js'])) {
                    $productJs = $manifestJsFiles['Product.js'];
                }
                if (isset($manifestJsFiles['HomeBase.js'])) {
                    $homeJs = $manifestJsFiles['HomeBase.js'];
                }
                if (isset($manifestJsFiles['main.js'])) {
                    $mainJs = $manifestJsFiles['main.js'];
                }
                if (isset($manifestJsFiles['vendors-main.js'])) {
                    $vendorJs = $manifestJsFiles['vendors-main.js'];
                }
                if (isset($manifestJsFiles['NoMatch.js'])) {
                    $noMatchJs = $manifestJsFiles['NoMatch.js'];
                }
                if (isset($manifestJsFiles['UrlMatch.js'])) {
                    $urlMatchJs = $manifestJsFiles['UrlMatch.js'];
                }
            }
            
            $preloadData = array('preload_js'=>array());
            if ($mainJs)
                $preloadData['preload_js'][] = $mainJs;
            if ($vendorJs)
                $preloadData['preload_js'][] = $vendorJs;

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

                        //preload files
                        if ($productJs)
                            $preloadData['preload_js'][] = $productJs;
                        if ($urlMatchJs)
                            $preloadData['preload_js'][] = $urlMatchJs;
                        if ($noMatchJs)
                            $preloadData['preload_js'][] = $noMatchJs;
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

                        //preload files
                        if ($productJs)
                            $preloadData['preload_js'][] = $productsJs;
                        if ($catrootJs)
                            $preloadData['preload_js'][] = $catrootJs;
                        if ($urlMatchJs)
                            $preloadData['preload_js'][] = $urlMatchJs;
                        if ($noMatchJs)
                            $preloadData['preload_js'][] = $noMatchJs;
                    }
                } else {
                    if ($homeJs)
                        $preloadData['preload_js'][] = $homeJs;
                }
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
        
        if (count($preloadData['preload_js'])) {
            foreach ($preloadData['preload_js'] as $preload_js) {
                $headerString.= '<link rel="preload" as="script" href="' . $preload_js . '">';   
            }
        }
        /*
        try {        
            $storeviewModel = Mage::getModel('simiconnector/api_storeviews');
            $data = array('resouceid'=>'default');
            $storeviewModel->setSingularKey('storeviews');
            $storeviewModel->setData($data);
            $storeviewModel->setBuilderQuery();
            $headerString .= '<script type="text/javascript"> var MERCHANT_CONFIGS = '.json_encode($storeviewModel->show()).'</script>';
        } catch (Exception $e) {

        }
        */
        return $headerString;
    }
}