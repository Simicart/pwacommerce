<?php

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 11/20/17
 * Time: 4:28 PM
 */
class Simi_Simipwa_Helper_Data extends Mage_Core_Helper_Data
{
    public function getSiteMaps()
    {
        $filePath = Mage::getBaseDir('code') . DS . "local" . DS . "Simi" . DS . "Simipwa" . DS . "Assest" . DS . "sitemaps.json";

        if (file_exists($filePath)) {
            $sitemaps = file_get_contents($filePath);
            if (!$sitemaps) {
                $sitemaps = $this->getDataSiteMaps();
                file_put_contents($filePath, $sitemaps);
                return json_decode($sitemaps, true);
            }
            return json_decode($sitemaps, true);
        } else {
            $file = @fopen($filePath, 'w+');
            $sitemaps = $this->getDataSiteMaps();
            if ($file) {
                file_put_contents($filePath, $sitemaps);
                return json_decode($sitemaps, true);
            }
            return json_decode($sitemaps, true);
        }

    }

    public function getDataSiteMaps()
    {
        $storeId = Mage::app()
            ->getWebsite(true)
            ->getDefaultGroup()
            ->getDefaultStoreId();

        $baseUrl = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
        // get categories
        $collection = Mage::getModel('simipwa/catemap')->getCollection($storeId);                
        $categories = new Varien_Object();
        $categories->setItems($collection);
        $categories_url = array();
        foreach ($categories->getItems() as $item) {
            $categories_url[] = array(
                'id' => $item->getId(),
                'url' => $item->getUrl(),
                'hasChild' => $item->getChild() ? true : false,
                'name' => $item->getCategoryName()
            );
        }
        $urls['categories_url'] = $categories_url;        
//         get products
        $collection = new Mage_Sitemap_Model_Resource_Catalog_Product();
        $collection = $collection->getCollection($storeId);
//        $collection = Mage::getResourceModel('sitemap/catalog_product')->getCollection($storeId);
        $products = new Varien_Object();
        $products->setItems($collection);
        $products_url = array();
        foreach ($products->getItems() as $item) {
            $products_url[] = array(
                'id' => $item->getId(),
                'url' => $item->getUrl(),
            );
        }
        $urls['products_url'] = $products_url;
        unset($collection);

//         get cms pages
        $cms_url = array();
        $collection = new Mage_Sitemap_Model_Resource_Cms_Page();
        $collection = $collection->getCollection($storeId);
        foreach ($collection as $item) {
            $cms_url[] = array(
                'id' => $item->getId(),
                'url' => $item->getUrl(),
            );
        }
        $urls['cms_url'] = $cms_url;
        unset($collection);
        $result = array();
        $result['sitemaps'] = $urls;
        return json_encode($result);
    }

    public function synSiteMaps()
    {
        $filePath = Mage::getBaseDir('code') . DS . "local" . DS . "Simi" . DS . "Simipwa" . DS . "Assest" . DS . "sitemaps.json";        
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $file = @fopen($filePath, 'w+');
        $sitemaps = $this->getDataSiteMaps();        
        if ($file) {
            file_put_contents($filePath, $sitemaps);
            return true;
        }

        return false;
    }

    public function IsEnableForWebsite(){
        return Mage::getStoreConfig('simipwa/notification/enable');
    }
}