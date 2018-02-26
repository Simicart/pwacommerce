<?php

/**
 * Created by PhpStorm.
 * User: scottsimicart
 * Date: 12/12/17
 * Time: 9:40 AM
 */
class Simi_Simipwa_Model_Catemap extends Mage_Sitemap_Model_Resource_Catalog_Category
{

    public function getCollection($storeId)
    {
        /* @var $store Mage_Core_Model_Store */
        $store = Mage::app()->getStore($storeId);
        if (!$store) {
            return false;
        }        
        $this->_select = $this->_getWriteAdapter()->select()
            ->from($this->getMainTable())
            ->where($this->getIdFieldName() . '=?', $store->getRootCategoryId());

        $categoryRow = $this->_getWriteAdapter()->fetchRow($this->_select);        
        if (!$categoryRow) {
            return false;
        }
        $this->_select = $this->_getWriteAdapter()->select()
            ->from(array('main_table' => $this->getMainTable()), array($this->getIdFieldName(), 'children_count'))
            ->join(array('cv' => 'catalog_category_entity_varchar'), 'main_table.entity_id = cv.entity_id', 'value as category_name')
            ->join(array('att' => 'eav_attribute'), 'att.attribute_id = cv.attribute_id', '')
            ->join(array('aty' => 'eav_entity_type'), 'aty.entity_type_id = att.entity_type_id', '')
            ->where("aty.`entity_model` = 'catalog/category' and att.`attribute_code` = 'name'")
            ->where('main_table.path LIKE ?', $categoryRow['path'] . '/%');

        $storeId = (int)$store->getId();

        /** @var $urlRewrite Mage_Catalog_Helper_Category_Url_Rewrite_Interface */
        $urlRewrite = $this->_factory->getCategoryUrlRewriteHelper();
        $urlRewrite->joinTableToSelect($this->_select, $storeId);

        $this->_addFilter($storeId, 'is_active', 1);

        return $this->_loadEntities();
    }

    protected function _prepareObject(array $row)
    {
        $entity = new Varien_Object();
        $entity->setId($row[$this->getIdFieldName()]);
        $entity->setUrl($this->_getEntityUrl($row, $entity));
        $entity->setChild($row['children_count']);
        $entity->setCategoryName($row['category_name']);
        return $entity;
    }
}