<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 11/22/17
 * Time: 11:01 AM
 */
class Simi_Simipwa_Block_Adminhtml_Notification_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm(){
        $form = new Varien_Data_Form();
        $this->setForm($form);

        if (Mage::getSingleton('adminhtml/session')->getMessageData()){
            $data = Mage::getSingleton('adminhtml/session')->getMessageData();
            Mage::getSingleton('adminhtml/session')->setMessageData(null);
        }elseif(Mage::registry('message_data'))
            $data = Mage::registry('message_data')->getData();
        //zend_debug::dump($data);
        $data['id'] = $this->getRequest()->getParam('id');
        $fieldset = $form->addFieldset('message_form', array('legend'=>Mage::helper('simipwa')->__('Send Notification')));
//        $device = Mage::getModel('simipwa/agent')->getCollection();
//        $items = array();
//        $items[] = array(
//            'value' => 0,
//            'label' => 'All Device'
//        );
//        foreach ($device as $item){
//            $items[] = array(
//                'value' => $item->getId(),
//                'label' => $item->getId(),
//            );
//        }
//        if ($data['notice_type'] == 2){
//            $data['device_id'] = 0;
//        }
//        $data['device_id'] = explode(',',$data['device_id']);
//
//        $fieldset->addField('device_id', 'multiselect', array(
//            'label'    => Mage::helper('simipwa')->__('Device ID'),
//            'class'    => '',
//            'required' => true,
//            'name'     => 'device_id',
//            'values'   => $items,
//        ));
        $deviceIds = Mage::getModel('simipwa/agent')->getCollection()->getAllIds();
        $data['devices_pushed'] = $data['device_id'];
        $fieldset->addField('devices_pushed', 'textarea', array(
            'name' => 'devices_pushed',
            'class' => 'required-entry',
            'required' => true,
            'label' => Mage::helper('simipwa')->__('Device IDs'),
            'note' => Mage::helper('simipwa')->__('Select your Devices'),
            'after_element_html' => '
                <a id="product_link" href="javascript:void(0)" onclick="toggleMainDevices()"><img src="' . $this->getSkinUrl('images/rule_chooser_trigger.gif') . '" alt="" class="v-middle rule-chooser-trigger" title="Select Device"></a>
                <input type="hidden" value="' . $deviceIds . '" id="device_all_ids"/>
                <div id="main_devices_select" style="display:none"></div>  
                <script type="text/javascript">
                    function clearDevices(){                    
                        $("main_devices_select").style.display == "none";
                        toggleMainDevices(2);
                    }
                    function updateNumberSeleced(){
                        $("note_devices_pushed_number").update($("devices_pushed").value.split(", ").size());
                    }
                    function toggleMainDevices(check){
                        var cate = $("main_devices_select");
                        if($("main_devices_select").style.display == "none" || (check ==1) || (check == 2)){
                            var url = "' . $this->getUrl('adminhtml/simipwa_pwa/chooseDevices') . '";                        
                            if(check == 1){
                                $("devices_pushed").value = $("devices_all_ids").value;
                            }else if(check == 2){
                                $("devices_pushed").value = "";
                            }
                            var params = $("devices_pushed").value.split(", ");
                            var parameters = {"form_key": FORM_KEY,"selected[]":params };
                            var request = new Ajax.Request(url,
                                {
                                    evalScripts: true,
                                    parameters: parameters,
                                    onComplete:function(transport){
                                        $("main_devices_select").update(transport.responseText);
                                        $("main_devices_select").style.display = "block"; 
                                    }
                                });
                        if(cate.style.display == "none"){
                            cate.style.display = "";
                        }else{
                            cate.style.display = "none";
                        } 
                    }else{
                        cate.style.display = "none";                    
                    }
                    updateNumberSeleced();
                };
                
                var griddevice;
                   
                function constructDataDevice(div){
                    griddevice = window[div.id+"JsObject"];
                    if(!griddevice.reloadParams){
                        griddevice.reloadParams = {};
                        griddevice.reloadParams["selected[]"] = $("devices_pushed").value.split(", ");
                    }
                }
                function toogleCheckAllDevices(el){
                    if(el == true){
                        $$("#main_devices_select input[type=checkbox][class=checkbox]").each(function(e){
                            if(e.name != "check_all"){
                                if(!e.checked){
                                    if($("devices_pushed").value == "")
                                        $("devices_pushed").value = e.value;
                                    else
                                        $("devices_pushed").value = $("devices_pushed").value + ", "+e.value;
                                    e.checked = true;
                                    griddevice.reloadParams["selected[]"] = $("devices_pushed").value.split(", ");
                                }
                            }
                        });
                    }else{
                        $$("#main_devices_select input[type=checkbox][class=checkbox]").each(function(e){
                            if(e.name != "check_all"){
                                if(e.checked){
                                    var vl = e.value;
                                    if($("devices_pushed").value.search(vl) == 0){
                                        if($("devices_pushed").value == vl) $("devices_pushed").value = "";
                                        $("devices_pushed").value = $("devices_pushed").value.replace(vl+", ","");
                                    }else{
                                        $("devices_pushed").value = $("devices_pushed").value.replace(", "+ vl,"");
                                    }
                                    e.checked = false;
                                    griddevice.reloadParams["selected[]"] = $("devices_pushed").value.split(", ");
                                }
                            }
                        });
                    }
                    updateNumberSeleced();
                }
                function selectDevice(e) {
                        if(e.checked == true){
                            if(e.id == "main_on"){
                                $("devices_pushed").value = $("device_all_ids").value;
                            }else{
                                if($("devices_pushed").value == "")
                                    $("devices_pushed").value = e.value;
                                else
                                    $("devices_pushed").value = $("devices_pushed").value + ", "+e.value;
                                e.checked == false;
                                griddevice.reloadParams["selected[]"] = $("devices_pushed").value.split(", ");
                            }
                        }else{
                             if(e.id == "main_on"){
                                $("devices_pushed").value = "";
                            }else{
                                var vl = e.value;
                                if($("devices_pushed").value.search(vl) == 0){
                                    if ($("devices_pushed").value.search(",") == -1)
                                        $("devices_pushed").value = "";
                                    else
                                        $("devices_pushed").value = $("devices_pushed").value.replace(vl+", ","");
                                }else{
                                    $("devices_pushed").value = $("devices_pushed").value.replace(", "+ vl,"");
                                }
                                e.checked == false;
                                griddevice.reloadParams["selected[]"] = $("devices_pushed").value.split(", ");
                            }
                        }
                        updateNumberSeleced();
                    }
            </script>
            '
        ));

        $fieldset->addField('id', 'hidden', array(
            'label'  => Mage::helper('simipwa')->__('PWA User Agent'),
            'name'   => 'id',
        ));

        $fieldset->addField('notice_title', 'text', array(
            'label' => Mage::helper('simipwa')->__('Title Message'),
            'name' => 'notice_title',
            'required' => true,
        ));

        $fieldset->addField('image_url', 'image', array(
            'label'        => Mage::helper('simipwa')->__('Image'),
            'name'        => 'img_url',
            //'note'  => Mage::helper('simipwa')->__('Size max: 1000 x 1000 (PX)'),
        ));

        $fieldset->addField('notice_content', 'editor', array(
            'name' => 'notice_content',
            'label' => Mage::helper('simipwa')->__('Message'),
            'title' => Mage::helper('simipwa')->__('Message'),
        ));
        $fieldset->addField('type', 'select', array(
            'label' => Mage::helper('simipwa')->__('Direct viewers to'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'type',
            'values' => Mage::getModel('simipwa/agent')->toOptionArray(),
            'onchange' => 'onchangeNoticeType(this.value)',
            'after_element_html' => '<script> Event.observe(window, "load", function(){onchangeNoticeType(\''.$data['type'].'\');});</script>',
        ));

        $productIds = implode(", ", Mage::getResourceModel('catalog/product_collection')->getAllIds());
        $fieldset->addField('product_id', 'text', array(
            'name' => 'product_id',
            'class' => 'required-entry',
            'required' => true,
            'label' => Mage::helper('simipwa')->__('Product ID'),
            'note'  => Mage::helper('simipwa')->__('Choose a product'),
            'after_element_html' => '<a id="product_link" href="javascript:void(0)" onclick="toggleMainProducts()"><img src="' . $this->getSkinUrl('images/rule_chooser_trigger.gif') . '" alt="" class="v-middle rule-chooser-trigger" title="Select Products"></a><input type="hidden" value="'.$productIds.'" id="product_all_ids"/><div id="main_products_select" style="display:none;width:640px"></div>
            <script type="text/javascript">
                function toggleMainProducts(){
                    if($("main_products_select").style.display == "none"){
                        var url = "' . $this->getUrl('adminhtml/simipwa_pwa/chooserMainProducts') . '";
                        var params = $("product_id").value.split(", ");
                        var parameters = {"form_key": FORM_KEY,"selected[]":params };
                        var request = new Ajax.Request(url,
                        {
                            evalScripts: true,
                            parameters: parameters,
                            onComplete:function(transport){
                                $("main_products_select").update(transport.responseText);
                                $("main_products_select").style.display = "block"; 
                            }
                        });
                    }else{
                        $("main_products_select").style.display = "none";
                    }
                };
                var grid;
               
                function constructData(div){
                    grid = window[div.id+"JsObject"];
                    if(!grid.reloadParams){
                        grid.reloadParams = {};
                        grid.reloadParams["selected[]"] = $("product_id").value.split(", ");
                    }
                }
                function toogleCheckAllProduct(el){
                    if(el.checked == true){
                        $$("#main_products_select input[type=checkbox][class=checkbox]").each(function(e){
                            if(e.name != "check_all"){
                                if(!e.checked){
                                    if($("product_id").value == "")
                                        $("product_id").value = e.value;
                                    else
                                        $("product_id").value = $("product_id").value + ", "+e.value;
                                    e.checked = true;
                                    grid.reloadParams["selected[]"] = $("product_id").value.split(", ");
                                }
                            }
                        });
                    }else{
                        $$("#main_products_select input[type=checkbox][class=checkbox]").each(function(e){
                            if(e.name != "check_all"){
                                if(e.checked){
                                    var vl = e.value;
                                    if($("product_id").value.search(vl) == 0){
                                        if($("product_id").value == vl) $("product_id").value = "";
                                        $("product_id").value = $("product_id").value.replace(vl+", ","");
                                    }else{
                                        $("product_id").value = $("product_id").value.replace(", "+ vl,"");
                                    }
                                    e.checked = false;
                                    grid.reloadParams["selected[]"] = $("product_id").value.split(", ");
                                }
                            }
                        });
                        
                    }
                }
                function selectProduct(e) {
                    if(e.checked == true){
                        if(e.id == "main_on"){
                            $("product_id").value = $("product_all_ids").value;
                        }else{
                            if($("product_id").value == "")
                                $("product_id").value = e.value;
                            else
                                $("product_id").value = e.value;
                            grid.reloadParams["selected[]"] = $("product_id").value;
                        }
                    }else{
                         if(e.id == "main_on"){
                            $("product_id").value = "";
                        }else{
                            var vl = e.value;
                            if($("product_id").value.search(vl) == 0){
                                $("product_id").value = $("product_id").value.replace(vl+", ","");
                            }else{
                                $("product_id").value = $("product_id").value.replace(", "+ vl,"");
                            }
                        }
                    }
                    
                }
            </script>'
        ));

        $fieldset->addField('category_id', 'text', array(
            'name' => 'category_id',
            'class' => 'required-entry',
            'required' => true,
            'label' => Mage::helper('simipwa')->__('Category ID'),
            'note'  => Mage::helper('simipwa')->__('Choose a category'),
            'after_element_html' => '<a id="category_link" href="javascript:void(0)" onclick="toggleMainCategories()"><img src="' . $this->getSkinUrl('images/rule_chooser_trigger.gif') . '" alt="" class="v-middle rule-chooser-trigger" title="Select Category"></a>
            <div id="main_categories_select" style="display:none"></div>
                <script type="text/javascript">
                function toggleMainCategories(check){
                    var cate = $("main_categories_select");
                    if($("main_categories_select").style.display == "none" || (check ==1) || (check == 2)){
                        var url = "' . $this->getUrl('adminhtml/simipwa_pwa/chooserMainCategories') . '";                        
                        if(check == 1){
                            $("category_id").value = $("category_all_ids").value;
                        }else if(check == 2){
                            $("category_id").value = "";
                        }
                        var params = $("category_id").value.split(", ");
                        var parameters = {"form_key": FORM_KEY,"selected[]":params };
                        var request = new Ajax.Request(url,
                            {
                                evalScripts: true,
                                parameters: parameters,
                                onComplete:function(transport){
                                    $("main_categories_select").update(transport.responseText);
                                    $("main_categories_select").style.display = "block"; 
                                }
                            });
                    if(cate.style.display == "none"){
                        cate.style.display = "";
                    }else{
                        cate.style.display = "none";
                    } 
                }else{
                    cate.style.display = "none";                    
                }
            };
    </script>
        '
        ));

        $fieldset->addField('notice_url', 'text', array(
            'name' => 'notice_url',
            'class' => 'required-entry',
            'required' => true,
            'label' => Mage::helper('simipwa')->__('URL'),
        ));

        $form->setValues($data);
        return parent::_prepareForm();
    }
}