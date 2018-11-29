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

    public function deteleAction()
    {
        if ($this->getRequest()->getParam('id') > 0) {
            try {
                $model = Mage::getModel('simipwa/agent');

                $model->setId($this->getRequest()->getParam('id'))
                    ->delete();

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Device was successfully deleted')
                );
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
            )
        );
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

    public function chooseDevicesAction()
    {
        $request = $this->getRequest();
        $additionalInfo = '<p class="note"><span id="note_devices_pushed_number"> </span> <span> ' . Mage::helper('simipwa')->__('Device(s) Selected') . '</span></p>';
//        $block = $this->getLayout()->createBlock(
//            'simipwa/adminhtml_notification_edit_tab_devices','aaaaa'
//        );

        $block = $this->getLayout()->createBlock(
            'simipwa/adminhtml_notification_edit_tab_devices', 'promo_widget_chooser_device_id', array('js_form_object' => $request->getParam('form'),
            )
        );

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
                Mage::helper('adminhtml')->__('Please select Device(s)')
            );
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

    public function syncSwAction()
    {
        $sw_path = Mage::getBaseDir() . '/pwa/service-worker.js';
        if (file_exists($sw_path)) {
            $sw = Mage::getBaseDir() . '/service-worker.js';
            if (copy($sw_path, $sw)) {
                $data['status'] = "1";
                $data['message'] = "Sync Successfully!";
            } else {
                $data['status'] = "1";
                $data['message'] = "Sync Failed!";
            }
        } else {
            $data['status'] = "1";
            $data['message'] = "service-worker.js file does not exits!";
        }

        return $this->getResponse()
            ->setHeader('Content-Type', 'application/json')
            ->setBody(json_encode($data));
    }

    public function buildAction()
    {
        try {
            $type = $this->getRequest()->getParam('build_type');
            if (!$type) $type = 'sandbox';
            $token = Mage::getStoreConfig('simiconnector/general/token_key');
            $secret_key = Mage::getStoreConfig('simiconnector/general/secret_key');

            if (!$token || !$secret_key || ($token == '') || ($secret_key == ''))
                throw new Exception(Mage::helper('simipwa')->__('Please fill your Token and Secret key on SimiCart connector settings'), 4);

            $config = file_get_contents("https://www.simicart.com/appdashboard/rest/app_configs/bear_token/" . $token . '/pwa/1');
            if (!$config || (!$config = json_decode($config, 1)))
                throw new Exception(
                    Mage::helper('simipwa')->__(
                        'We cannot connect To SimiCart, please check your filled token, or check if 
                your server allows connections to SimiCart website'
                    ), 4
                );
            $buildFile = 'https://dashboard.simicart.com/pwa/package.php?app_id=' . $config['app-configs'][0]['app_info_id'];
            $fileToSave = Mage::getBaseDir() . '/pwa/simi_pwa_package.zip';
            $directoryToSave = Mage::getBaseDir() . '/pwa/';
            if($type == 'sandbox'){
                $buildFile = 'https://dashboard.simicart.com/pwa/sandbox_package.php?app_id='.$config['app-configs'][0]['app_info_id'];
                $fileToSave =  Mage::getBaseDir().'/pwa_sandbox/simi_pwa_package.zip';
                $directoryToSave =  Mage::getBaseDir().'/pwa_sandbox/';
            }
            $buildTime = time();
            if ($config['app-configs'][0]['ios_link']) {
                try {
                    $iosId = explode('id', $config['app-configs'][0]['ios_link']);
                    $iosId = $iosId[1];
                    $iosId = substr($iosId, 0, 10);
                } catch (Exception $getIosUrlException) {
                    throw new Exception(Mage::helper('simipwa')->__($getIosUrlException));
                }
            }

            if ($config['app-configs'][0]['android_link']) {
                try {
                    $androidId = explode('id=', $config['app-configs'][0]['android_link']);
                    $androidId = $androidId[1];
                    $androidId = explode('?', $androidId);
                    $androidId = $androidId[0];
                } catch (Exception $getAndroidUrlException) {
                    throw new Exception(Mage::helper('simipwa')->__($getAndroidUrlException));
                }
            }

            // create directory pwa
            Mage::helper('simipwa')->_removeFolder($directoryToSave);
            mkdir($directoryToSave, 0777, true);
            //download file
            file_get_contents($buildFile);
            if (!isset($http_response_header[0]) || !is_string($http_response_header[0]) ||
                (strpos($http_response_header[0], '200') === false)) {
                throw new Exception(Mage::helper('simipwa')->__('Sorry, we cannot get PWA package from SimiCart.'), 4);
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $buildFile);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $data = curl_exec($ch);
            curl_close($ch);
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

            /*
            Use this when downloading or extracting does not work
            $this->moveDir();
            */

            // move service worker
            if($type == 'live'){
                $sw_path = Mage::getBaseDir() . '/pwa/simi-sw.js';
                if (file_exists($sw_path)) {
                    $sw = Mage::getBaseDir() . '/simi-sw.js';
                    if (!copy($sw_path, $sw)) {
                        throw new Exception(Mage::helper('simipwa')->__('Sorry, service-worker.js file does not exits!'), 4);
                    }
                } else {
                    throw new Exception(Mage::helper('simipwa')->__('Sorry, service-worker.js file does not exits!'), 4);
                }
            }else{
                $sw_path = Mage::getBaseDir() . '/pwa_sandbox/simi-sw-sandbox.js';
                if (file_exists($sw_path)) {
                    $sw = Mage::getBaseDir() . '/simi-sw-sandbox.js';
                    if (!copy($sw_path, $sw)) {
                        throw new Exception(Mage::helper('simipwa')->__('Sorry, service-worker.js file does not exits!'), 4);
                    }
                } else {
                    throw new Exception(Mage::helper('simipwa')->__('Sorry, service-worker.js file does not exits!'), 4);
                }
            }

            // app image
            $app_images = $config['app-configs'][0]['app_images'];
            $app_image_logo = Mage::getStoreConfig('simipwa/general/logo_homepage');
            if (!$app_image_logo) {
                $app_image_logo = $app_images['logo'];
                Mage::getConfig()->saveConfig('simipwa/general/logo_homepage', $app_image_logo);
            }

            $app_splash_img_url = Mage::getStoreConfig('simipwa/general/splash_img');
            if (!$app_splash_img_url) {
                $app_splash_img_url = $app_images['splash_screen'];
                Mage::getConfig()->saveConfig('simipwa/general/splash_img', $app_splash_img_url);
            }
            $app_splash_img = '<img src="' . $app_splash_img_url . '" alt="Splash Screen" style="width: 325px;height: auto">';

            $app_icon = Mage::getStoreConfig('simipwa/manifest/logo');
            if (!$app_icon) {
                $app_icon = $app_images['icon'];
                Mage::getConfig()->saveConfig('simipwa/manifest/logo', $app_icon);
            }


            //update index.html file
            $path_to_file = Mage::getBaseDir() . '/pwa/index.html';
            if($type == 'sandbox'){
                $path_to_file = Mage::getBaseDir().'/pwa_sandbox/index.html';
            }
            $theme_color = Mage::getStoreConfig('simipwa/manifest/theme_color') ? '#'.Mage::getStoreConfig('simipwa/manifest/theme_color') : '#3399cc';
            $excludedPaths = Mage::getStoreConfig('simipwa/general/pwa_excluded_paths');
            $file_contents = file_get_contents($path_to_file);
            $file_contents = str_replace('PAGE_TITLE_HERE', $config['app-configs'][0]['app_name'], $file_contents);
            $file_contents = str_replace('IOS_SPLASH_TEXT', $config['app-configs'][0]['app_name'], $file_contents);
            $file_contents = str_replace('"PWA_EXCLUDED_PATHS"', '"' . $excludedPaths . '"', $file_contents);
            $file_contents = str_replace('PWA_BUILD_TIME_VALUE', $buildTime, $file_contents);
            $file_contents = str_replace('content="#FDA343"', 'content="'.$theme_color.'"', $file_contents);
            $file_contents = str_replace('<div id="splash-img"></div>', $app_splash_img, $file_contents);
            if ($head = Mage::getStoreConfig('simipwa/general/custom_head')) {
                $file_contents = str_replace('<head>', '<head>' . $head, $file_contents);
            }

            if ($footerHtml = Mage::getStoreConfig('simipwa/general/footer_html')) {
                $footerHtml = Mage::helper('cms')
                    ->getPageTemplateProcessor()
                    ->filter($footerHtml);
                $file_contents = str_replace('</body>', $footerHtml . '</body>', $file_contents);
            }

            //move favicon into pwa
            $favicon = Mage::getStoreConfig('simipwa/general/favicon');
            $favicon = $favicon ? $favicon : $app_icon;
            $file_contents = str_replace($type == 'sandbox' ? '/pwa_sandbox/favicon.ico':'/pwa/favicon.ico', $favicon, $file_contents);

            // update smart banner
            if (isset($iosId) && $iosId && $iosId !== '') {
                $file_contents = str_replace('IOS_APP_ID', $iosId, $file_contents);
            }

            if (isset($androidId) && $androidId && $androidId !== '') {
                $file_contents = str_replace('GOOGLE_APP_ID', $androidId, $file_contents);
            }

            //update manifest.jon
            if (Mage::getStoreConfig('simipwa/manifest/enable')) {
                Mage::helper('simipwa')->updateManifest($type);
                if ($app_icon) {
                    $file_contents = str_replace($type=='sandbox'?'/pwa_sandbox/images/default_icon_512_512.png':'/pwa/images/default_icon_512_512.png', $app_icon, $file_contents);
                }
            }

            file_put_contents($path_to_file, $file_contents);
            copy($path_to_file, Mage::getBaseDir() . '/pwa/index_sample.html');

            //update config.js file
            Mage::helper('simipwa')->updateConfigJS($config,$buildTime,$type);

            if($type == 'sandbox'){
                $msg_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/pwa-sandbox";
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Sandbox PWA  was Built Successfully.'.'<br/>Please go to '.$msg_url.' to review.')
                );
            }else{
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('PWA Application was Built Successfully.')
                );
            }
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        return $this->_redirect('*/system_config/edit/section/simipwa');
    }

    /* 
    Use this when downloading or extracting does not work
    */
    public function moveDir(){
        $source = Mage::getBaseDir() . '/pwa_package/';
        $dest= Mage::getBaseDir() . '/pwa/';

        mkdir($dest, 0755);
        foreach (
         $iterator = new \RecursiveIteratorIterator(
          new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
          \RecursiveIteratorIterator::SELF_FIRST) as $item
        ) {
          if ($item->isDir()) {
            mkdir($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
          } else {
            copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
          }
        }
    }

    public function RefreshCacheAction()
    {
        try {
            if (Mage::getStoreConfig('simipwa/cache_api/enable')) {
                $path_file = Mage::getBaseDir() . '/pwa/index_sample.html';
                if (file_exists($path_file)) {
                    copy($path_file, Mage::getBaseDir() . '/pwa/index.html');
                    $buildTime = time();
                }

                $api_storeview = Mage::getUrl('simiconnector/rest/v2/storeviews/default?pwa=1');
                $token = Mage::getStoreConfig('simiconnector/general/token_key');
                $api_simicart = "https://www.simicart.com/appdashboard/rest/app_configs/bear_token/" . $token . '/pwa/1';
                Mage::helper('simipwa')->SyncApi(Mage::getStoreConfig('simipwa/cache_api/storeview_api'), $api_storeview, "'sync_api_storeview'");
                Mage::helper('simipwa')->SyncApi(Mage::getStoreConfig('simipwa/cache_api/simicart_api'), $api_simicart, "'sync_api_simicart'");
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Refresh Cache Api Successfully.')
                );
            } else {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please enable cache api'));
            }
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        return $this->_redirect('*/system_config/edit/section/simipwa');
    }
}