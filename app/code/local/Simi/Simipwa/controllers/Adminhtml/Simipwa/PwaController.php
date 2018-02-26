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
        echo '<p class="note"><span id="note_devices_pushed_number"> </span> <span> '.Mage::helper('simipwa')->__('Device(s) Selected').'</span></p>';
//        $block = $this->getLayout()->createBlock(
//            'simipwa/adminhtml_notification_edit_tab_devices','aaaaa'
//        );

        $block = $this->getLayout()->createBlock(
            'simipwa/adminhtml_notification_edit_tab_devices', 'promo_widget_chooser_device_id', array('js_form_object' => $request->getParam('form'),
        ));

        if ($block) {
            $this->getResponse()->setBody($block->toHtml());
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
}