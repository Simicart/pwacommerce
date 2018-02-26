<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 11/20/17
 * Time: 4:06 PM
 */
class Simi_Simipwa_Block_Adminhtml_Pwa_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * prepare tab form's information
     *
     * @return Simi_Siminotification_Block_Adminhtml_Siminotification_Edit_Tab_Form
     */
    protected function _prepareForm(){
    $form = new Varien_Data_Form();
    $this->setForm($form);

    if (Mage::getSingleton('adminhtml/session')->getAgentData()){
        $data = Mage::getSingleton('adminhtml/session')->getAgentData();
        Mage::getSingleton('adminhtml/session')->setAgentData(null);
    }elseif(Mage::registry('agent_data'))
        $data = Mage::registry('agent_data')->getData();

    $fieldset = $form->addFieldset('agent_form', array('legend'=>Mage::helper('simipwa')->__('User Agent information')));
    $send_message = $form->addFieldset('message_form', array('legend'=>Mage::helper('simipwa')->__('Send Notification')));
    $fieldset->addType('datetime', 'Simi_Simipwa_Block_Adminhtml_Pwa_Edit_Renderer_Datetime');
    $id = $this->getRequest()->getParam('id');
    $data['device_id'] = $id;
    $fieldset->addField('device_id', 'hidden', array(
        'label'  => Mage::helper('simipwa')->__('PWA User Agent'),
        'name'   => 'device_id',
    ));

    $fieldset->addField('user_agent', 'label', array(
        'label'  => Mage::helper('simipwa')->__('PWA User Agent'),
        'name'   => 'user_agent',
        'bold' => true,
    ));

    $fieldset->addField('city', 'label', array(
        'label'  => Mage::helper('simipwa')->__('City'),
        'name'   => 'city',
        'bold' => true,
    ));

    $fieldset->addField('country', 'label', array(
        'label'  => Mage::helper('simipwa')->__('Country'),
        'name'   => 'country',
        'bold' => true,
    ));

    $fieldset->addField('endpoint', 'label', array(
        'label'  => Mage::helper('simipwa')->__('PWA Endpoint'),
        'name'   => 'endpoint',
        'bold' => true,
    ));

    $fieldset->addField('endpoint_key', 'editor', array(
        'label'  => Mage::helper('simipwa')->__('PWA Endpoint Key'),
        'name'   => 'endpoint_key',
        'readonly' => true,
    ));

    $fieldset->addField('p256dh_key', 'label', array(
        'label'  => Mage::helper('simipwa')->__('P256dh key'),
        'name'   => 'p256dh_key',
    ));

    $fieldset->addField('auth_key', 'label', array(
        'label'  => Mage::helper('simipwa')->__('Auth key'),
        'name'   => 'auth_key',
    ));

    $fieldset->addField('created_at', 'datetime', array(
        'label' => Mage::helper('simipwa')->__('Create Date'),
        'bold'  => true,
        'name'  => 'created_at',
    ));

    $send_message->addField('notice_type', 'select', array(
        'label'		=> Mage::helper('simipwa')->__('Send Device'),
        'name'		=> 'notice_type',
        'values'	=> array(
            '1' =>  'Current Device',
            '2' =>  'All Device',
        ),
    ));

    $send_message->addField('notice_title', 'text', array(
       'label' => Mage::helper('simipwa')->__('Title Message'),
       'name' => 'notice_title',
       'required' => true,
    ));

    $send_message->addField('image_url', 'image', array(
        'label'        => Mage::helper('simipwa')->__('Image'),
        'name'        => 'img_url',
        //'note'  => Mage::helper('simipwa')->__('Size max: 1000 x 1000 (PX)'),
    ));

    $send_message->addField('notice_content', 'editor', array(
        'name' => 'notice_content',
        'label' => Mage::helper('simipwa')->__('Message'),
        'title' => Mage::helper('simipwa')->__('Message'),
    ));
    $send_message->addField('type', 'select', array(
        'label' => Mage::helper('simipwa')->__('Direct viewers to'),
        'class' => 'required-entry',
        'required' => true,
        'name' => 'type',
        'values' => Mage::getModel('simipwa/agent')->toOptionArray(),
        'onchange' => 'onchangeNoticeType(this.value)',
        'after_element_html' => '<script> Event.observe(window, "load", function(){onchangeNoticeType(\''.$data['type'].'\');});</script>',
    ));

    $productIds = implode(", ", Mage::getResourceModel('catalog/product_collection')->getAllIds());
    $send_message->addField('product_id', 'text', array(
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

    $send_message->addField('category_id', 'text', array(
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

    $send_message->addField('notice_url', 'text', array(
        'name' => 'notice_url',
        'class' => 'required-entry',
        'required' => true,
        'label' => Mage::helper('simipwa')->__('URL'),
    ));

    $form->setValues($data);
    return parent::_prepareForm();
}
}