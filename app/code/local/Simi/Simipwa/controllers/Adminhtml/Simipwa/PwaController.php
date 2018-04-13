<?php

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 11/20/17
 * Time: 4:16 PM
 */
class Simi_Simipwa_Adminhtml_Simipwa_PwaController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('simipwa');
    }

    public function indexAction()
    {
        $this->loadLayout()->renderLayout();
    }

    public function deteleAction(){
        if ($this->getRequest()->getParam('id') > 0) {
            try {
                $model = Mage::getModel('simipwa/agent');

                $model->setId($this->getRequest()->getParam('id'))
                    ->delete();

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Device was successfully deleted'));
                $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        $this->_redirect('*/*/');
    }

    public function editAction()
    {
        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('simipwa/agent')->load($id);

        if ($model->getId() || $id == 0) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if (!empty($data))
                $model->setData($data);

            Mage::register('agent_data', $model);

            $this->loadLayout();
            $this->_setActiveMenu('simipwa/agent');

            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('PWA Manager'), Mage::helper('adminhtml')->__('PWA Manager'));
            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('PWA News'), Mage::helper('adminhtml')->__('PWA News'));

            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
            $this->_addContent($this->getLayout()->createBlock('simipwa/adminhtml_pwa_edit'))
                ->_addLeft($this->getLayout()->createBlock('simipwa/adminhtml_pwa_edit_tabs'));

            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('simipwa')->__('Item does not exist'));
            $this->_redirect('*/*/');
        }
    }

    public function sendMessageAction()
    {
        if ($data = $this->getRequest()->getPost()) {
            $data['device_id'] = (int)$data['device_id'];
            $id = $data['device_id'];
            $message = Mage::getModel('simipwa/message')->load($id, 'device_id');

            /*upload img*/
            if (isset($_FILES['img_url']['name']) && $_FILES['img_url']['name'] != '') {
                try {
                    $uploader = new Varien_File_Uploader($_FILES['img_url']);
                    $uploader->setAllowedExtensions(array('jpg', 'jpeg', 'gif', 'png'));
                    $uploader->setAllowRenameFiles(false);
                    $uploader->setFilesDispersion(false);

                    $path = Mage::getBaseDir('media') . DS . 'simipwa' . DS . 'img' . DS;

                    $result = $uploader->save($path, $_FILES['img_url']['name']);

                    $data['image_url'] = 'simipwa/img/' . $result['file'];
                } catch (Exception $e) {
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                }
            } else {
                if (isset($data['img_url']['delete']) && $data['img_url']['delete'] == 1) {
                    $pathImg = 'media/' . $data['img_url']['value'];
                    if (file_exists($pathImg)) {
                        unlink($pathImg);
                    }
                    $data['image_url'] = '';
                }
            }
            try {
                //zend_debug::dump($message->getId());die;
                $message_id = $message->getId();
                if (!$message_id) {
                    $message = Mage::getModel('simipwa/message');
                }
                if (!$data['type'] && $data['product_id']) {
                    $data['type'] = 1;
                }
                //zend_debug::dump($data);die;
                $message->setData($data);
                $mess = Mage::getModel('simipwa/message')->getCollection()
                    ->addFieldToFilter('status', 1);
                foreach ($mess as $item) {
                    $item['status'] = 2;
                    $item->save();
                }
                $message->setCreatedTime(now())->setStatus(1);
//                zend_debug::dump($message->getData());die;
                $message->setId($message_id)->save();
                if ($data['notice_type'] == 1) {
                    if ($data['endpoint_key']) {
                        Mage::getModel('simipwa/agent')->send($data['device_id']);
                    }
                } elseif ($data['notice_type'] == 2) {
                    $devices = Mage::getModel('simipwa/agent')->getCollection();
                    foreach ($devices as $item) {
                        $send = Mage::getModel('simipwa/agent')->send($item->getId());
                        if (!$send) $item->delete();
                    }
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('simipwa')->__('Notification was successfully sent'));
                Mage::getSingleton('adminhtml/session')->setFormData(false);
                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
    }

    public function chooserMainCategoriesAction()
    {
        $request = $this->getRequest();
        $id = $request->getParam('selected', array());
        $block = $this->getLayout()->createBlock('simipwa/adminhtml_pwa_edit_tab_categories', 'maincontent_category', array('js_form_object' => $request->getParam('form')))
            ->setCategoryIds($id);

        if ($block) {
            $this->getResponse()->setBody($block->toHtml());
        }
    }

    /**
     * Get tree node (Ajax version)
     */
    public function categoriesJsonAction()
    {
        if ($categoryId = (int)$this->getRequest()->getPost('id')) {
            $this->getRequest()->setParam('id', $categoryId);

            if (!$category = $this->_initCategory()) {
                return;
            }
            $this->getResponse()->setBody(
                $this->getLayout()->createBlock('adminhtml/catalog_category_tree')
                    ->getTreeJson($category)
            );
        }
    }

    /**
     * Initialize category object in registry
     *
     * @return Mage_Catalog_Model_Category
     */
    protected function _initCategory()
    {
        $categoryId = (int)$this->getRequest()->getParam('id', false);
        $storeId = (int)$this->getRequest()->getParam('store');

        $category = Mage::getModel('catalog/category');
        $category->setStoreId($storeId);

        if ($categoryId) {
            $category->load($categoryId);
            if ($storeId) {
                $rootId = Mage::app()->getStore($storeId)->getRootCategoryId();
                if (!in_array($rootId, $category->getPathIds())) {
                    $this->_redirect('*/*/', array('_current' => true, 'id' => null));
                    return false;
                }
            }
        }

        Mage::register('category', $category);
        Mage::register('current_category', $category);

        return $category;
    }

    public function categoriesJson2Action()
    {
        $this->_initItem();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('simipwa/adminhtml_pwa_edit_tab_categories')
                ->getCategoryChildrenJson($this->getRequest()->getParam('category'))
        );
    }

    public function chooserMainProductsAction()
    {
        $request = $this->getRequest();
        $block = $this->getLayout()->createBlock(
            'simipwa/adminhtml_pwa_edit_tab_products', 'promo_widget_chooser_sku', array('js_form_object' => $request->getParam('form'),
        ));
        if ($block) {
            $this->getResponse()->setBody($block->toHtml());
        }
    }

    public function syncSitemapsAction()
    {
        $result = Mage::helper('simipwa')->synSiteMaps();
        $data = array();
        if ($result) {
            $data['status'] = "1";
            $data['message'] = "Sync Completed!";
        } else {
            $data['status'] = "1";
            $data['message'] = "Sync Failed!";
        }
       return $this->getResponse()
            ->setHeader('Content-Type', 'application/json')
            ->setBody(json_encode($data));
    }

    /*
     * Get Device to Push Notification
     */

    public function chooseDevicesAction() {
        $request = $this->getRequest();
        $additionalInfo = '<p class="note"><span id="note_devices_pushed_number"> </span> <span> '.Mage::helper('simipwa')->__('Device(s) Selected').'</span></p>';
//        $block = $this->getLayout()->createBlock(
//            'simipwa/adminhtml_notification_edit_tab_devices','aaaaa'
//        );

        $block = $this->getLayout()->createBlock(
            'simipwa/adminhtml_notification_edit_tab_devices', 'promo_widget_chooser_device_id', array('js_form_object' => $request->getParam('form'),
        ));

        if ($block) {
            $this->getResponse()->setBody($additionalInfo . $block->toHtml());
        }
    }

    /**
     * Delete msg in mass number
     */
    public function massDeleteAction()
    {
        $Ids = $this->getRequest()->getParam('agent');
        if (!is_array($Ids)) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('adminhtml')->__('Please select Device(s)'));
        } else {
            try {
                foreach ($Ids as $id) {
                    $msg = Mage::getModel('simipwa/agent')->load($id);
                    $msg->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__(
                        'Total of %d record(s) were successfully deleted', count($Ids)
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    public function massStatusAction()
    {
        $Ids = $this->getRequest()->getParam('agent');
        $stt = $this->getRequest()->getParam('status');
        if (!is_array($Ids)) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Please select Device(s)'));
        } else {
            try {
                foreach ($Ids as $id) {
                    $device = Mage::getSingleton('simipwa/agent')
                        ->load($id);
                    $device->setStatus($stt)->save();

                }
                $this->_getSession()->addSuccess(
                    $this->__('Total of %d record(s) were successfully updated', count($Ids))
                );
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    public function syncSwAction(){
        $sw_path = Mage::getBaseDir() . '/pwa/service-worker.js';
        if (file_exists($sw_path)) {
            $sw = Mage::getBaseDir() . '/service-worker.js';
            if(copy($sw_path,$sw)){
                $data['status'] = "1";
                $data['message'] = "Sync Successfully!";
            }else{
                $data['status'] = "1";
                $data['message'] = "Sync Failed!";
            }
        }else{
            $data['status'] = "1";
            $data['message'] = "service-worker.js file does not exits!";
        }
        return $this->getResponse()
            ->setHeader('Content-Type', 'application/json')
            ->setBody(json_encode($data));
    }

    public function buildAction(){
        try{
            $token =  Mage::getStoreConfig('simiconnector/general/token_key');
            $secret_key =  Mage::getStoreConfig('simiconnector/general/secret_key');
            $logoUrlSetting = Mage::getStoreConfig('simipwa/general/logo_homepage');
            $app_image_logo = ($logoUrlSetting && $logoUrlSetting!='')?
                $logoUrlSetting : Mage::getStoreConfig('design/header/logo_src');
            if (!$token || !$secret_key || ($token == '') || ($secret_key == ''))
                throw new Exception(Mage::helper('simipwa')->__('Please fill your Token and Secret key on SimiCart connector settings'), 4);

            $config = file_get_contents("https://www.simicart.com/appdashboard/rest/app_configs/bear_token/".$token);
            if (!$config || (!$config = json_decode($config, 1)))
                throw new Exception(Mage::helper('simipwa')->__('We cannot connect To SimiCart, please check your filled token, or check if 
                your server allows connections to SimiCart website'), 4);
            $buildFile = 'https://dashboard.simicart.com/pwa/package.php?app_id='.$config['app-configs'][0]['app_info_id'];
            $fileToSave = Mage::getBaseDir() .'/pwa/simi_pwa_package.zip';
            $directoryToSave = Mage::getBaseDir().'/pwa/';
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
            // create directory pwa
            Mage::helper('simipwa')->_removeFolder($directoryToSave);
            mkdir($directoryToSave, 0777, true);
            //download file
            file_get_contents($buildFile);
            if (!isset($http_response_header[0]) || !is_string($http_response_header[0]) ||
                (strpos($http_response_header[0],'200') === false)) {
                throw new Exception(Mage::helper('simipwa')->__('Sorry, we cannot get PWA package from SimiCart.'), 4);
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $buildFile);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $data = curl_exec ($ch);
            curl_close ($ch);
            $file = fopen($fileToSave, "w+");
            fputs($file, $data);
            fclose($file);

            //unzip file

            $zip = new ZipArchive();
            $res = $zip->open($fileToSave);
            //zend_debug::dump($res);die;
            if ($res === TRUE) {
                $zip->extractTo($directoryToSave);
                $zip->close();
            } else {
                throw new Exception(Mage::helper('simipwa')->__('Sorry, we cannot extract PWA package.'), 4);
            }

            // rename htaccess
            $htaccess = '/pwa/htaccess';
            if(file_exists($htaccess)){
                rename($htaccess,'/pwa/.htaccess');
            }

            // move service worker
            $sw_path = Mage::getBaseDir() . '/pwa/service-worker.js';
            if (file_exists($sw_path)) {
                $sw = Mage::getBaseDir() . '/service-worker.js';
                if(!copy($sw_path,$sw)){
                    throw new Exception(Mage::helper('simipwa')->__('Sorry, service-worker.js file does not exits!'), 4);
                }
            }else{
                throw new Exception(Mage::helper('simipwa')->__('Sorry, service-worker.js file does not exits!'), 4);
            }

            //update index.html file
            $path_to_file = Mage::getBaseDir() .'/pwa/index.html';
            $excludedPaths = Mage::getStoreConfig('simipwa/general/pwa_excluded_paths');
            $file_contents = file_get_contents($path_to_file);
            $file_contents = str_replace('PAGE_TITLE_HERE',$config['app-configs'][0]['app_name'],$file_contents);
            $file_contents = str_replace('IOS_SPLASH_TEXT',$config['app-configs'][0]['app_name'],$file_contents);
            $file_contents = str_replace('"PWA_EXCLUDED_PATHS"','"'.$excludedPaths.'"',$file_contents);
            $file_contents = str_replace('PWA_BUILD_TIME_VALUE',$buildTime,$file_contents);
            //move favicon into pwa
            $favicon = Mage::getStoreConfig('simipwa/general/favicon');
            if ($favicon && $favicon != ''){
                $file_contents = str_replace('/pwa/favicon.ico',$favicon,$file_contents);
            }
            // update smart banner
            if(isset($iosId) && $iosId && $iosId!==''){
                $file_contents = str_replace('IOS_APP_ID',$iosId,$file_contents);
            }
            if(isset($androidId) && $androidId && $androidId!==''){
                $file_contents = str_replace('GOOGLE_APP_ID',$androidId,$file_contents);
            }
            //update manifest.jon
            if(Mage::getStoreConfig('simipwa/manifest/enable')){
                Mage::helper('simipwa')->updateManifest();
                if($icon =  Mage::getStoreConfig('simipwa/manifest/logo')){
                    $file_contents = str_replace('/pwa/images/default_icon_512_512.png',$icon,$file_contents);
                }
            }
            file_put_contents($path_to_file,$file_contents);
            //update version.js file
            $versionContent = '
                var PWA_BUILD_TIME = '.$buildTime.';
                var PWA_LOCAL_BUILD_TIME = localStorage.getItem("pwa_build_time");
                if(!PWA_LOCAL_BUILD_TIME || PWA_LOCAL_BUILD_TIME === null){
                    localStorage.setItem(\'pwa_build_time\',PWA_BUILD_TIME);
                    PWA_LOCAL_BUILD_TIME = PWA_BUILD_TIME;
                }else{
                    PWA_LOCAL_BUILD_TIME = parseInt(PWA_LOCAL_BUILD_TIME,10);
                    if(PWA_BUILD_TIME > PWA_LOCAL_BUILD_TIME){
                        localStorage.setItem(\'pwa_build_time\',PWA_BUILD_TIME);
                        PWA_LOCAL_BUILD_TIME = PWA_BUILD_TIME;
                    }
                }
                var INDEX_LOCAL_BUILD_TIME = parseInt(localStorage.getItem("index_build_time"),10);
                if(PWA_LOCAL_BUILD_TIME !== INDEX_LOCAL_BUILD_TIME){
                    use_pwa = false;
                    if(PWA_LOCAL_BUILD_TIME > INDEX_LOCAL_BUILD_TIME){
                        localStorage.setItem("index_build_time",PWA_LOCAL_BUILD_TIME);
                    }else{
                        localStorage.setItem("pwa_build_time",INDEX_LOCAL_BUILD_TIME);
                    }
                }
                console.log(use_pwa);
                if (!use_pwa) {
                    navigator.serviceWorker.getRegistrations().then(function(registrations) {
                     for(let registration of registrations) {
                      registration.unregister()
                    } });
                    caches.keys().then(function(names) {
                        for (let name of names)
                            caches.delete(name);
                    });
                    window.location.reload();
                }
            ';


            $path_to_file = './pwa/js/config/version.js';
            file_put_contents($path_to_file, $versionContent);

            //update config.js file

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
        
                mixpanel: {
                    token_key: "'.trim($mixPanelToken).'"
                },
                logo_url: "'.$app_image_logo.'"
            };
            ';

            foreach ($config['app-configs'] as $index=>$appconfig) {
                if ($appconfig['theme']) {
                    $theme = $appconfig['theme'];
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

            $path_to_file = Mage::getBaseDir() .'/pwa/js/config/config.js';
            file_put_contents($path_to_file, $msConfigs);
            $msg_url = Mage::getStoreConfig('simipwa/general/pwa_main_url_site') ? $url : $url.'pwa/';
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('adminhtml')->__('PWA Application was Built Successfully. To review it, please go to '.$msg_url));
        }catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        return $this->_redirect('*/system_config/edit/section/simipwa');
    }
}