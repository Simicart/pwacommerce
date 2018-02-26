<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 11/20/17
 * Time: 4:07 PM
 */
class Simi_Simipwa_Block_Adminhtml_Pwa_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct(){
        parent::__construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'simipwa';
        $this->_controller = 'adminhtml_pwa';
        $this->removeButton('reset');
        $this->removeButton('save');
        $this->removeButton('delete');
        //$this->_updateButton('delete', 'label', Mage::helper('simipwa')->__('Delete Item'));

        $this->_addButton('duplicate', array(
            'label'		=> Mage::helper('adminhtml')->__('Send Notification'),
            'onclick'	=> 'send_message()',
            'class'		=> 'save',
        ), -100);

        $url = Mage::getUrl('adminhtml/simipwa_pwa/sendMessage');

        $this->_formScripts[] = "
			function toggleEditor() {
				if (tinyMCE.getInstanceById('pfeatures_content') == null)
					tinyMCE.execCommand('mceAddControl', false, 'simipwa_content');
				else
					tinyMCE.execCommand('mceRemoveControl', false, 'simipwa_content');
			}

			function saveAndContinueEdit(){
				editForm.submit($('edit_form').action+'back/edit/');
			}
			
			function send_message(){
			    editForm.submit('$url');
			}
			
			function onchangeNoticeType(type){
				switch (type) {
					case '1':
						$('product_id').up('tr').show(); 						
						$('product_id').className = 'required-entry input-text'; 
						$('category_id').up('tr').hide();
						$('category_id').className = 'input-text'; 
						$('notice_url').up('tr').hide(); 
						$('notice_url').className = 'input-text'; 
						break;
					case '2':
						$('category_id').up('tr').show(); 
						$('category_id').className = 'required-entry input-text'; 
						$('product_id').up('tr').hide(); 
						$('product_id').className = 'input-text'; 
						$('notice_url').up('tr').hide(); 
						$('notice_url').className = 'input-text'; 
						break;
					case '3':
						$('notice_url').up('tr').show(); 
						$('notice_url').className = 'required-entry input-text'; 
						$('product_id').up('tr').hide(); 
						$('product_id').className = 'input-text'; 
						$('category_id').up('tr').hide();
						$('category_id').className = 'input-text'; 
						break;
					default:
						$('product_id').up('tr').show(); 
						$('product_id').className = 'required-entry input-text'; 
						$('category_id').up('tr').hide(); 
						$('category_id').className = 'input-text'; 
						$('notice_url').up('tr').hide();
						$('notice_url').className = 'input-text'; 
				}
			}
			
		";
    }

    public function getHeaderText(){
        return Mage::helper('simipwa')->__('PWA User Agent');
    }
}