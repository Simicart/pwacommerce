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

    public function IsEnableForWebsite()
    {
        return Mage::getStoreConfig('simipwa/notification/enable');
    }

    public function _removeFolder($folder)
    {
        if (is_dir($folder))
            $dir_handle = opendir($folder);
        if (!$dir_handle)
            return false;
        while($file = readdir($dir_handle)) {
            if ($file != "." && $file != "..") {
                if (!is_dir($folder."/".$file))
                    unlink($folder."/".$file);
                else
                    $this->_removeFolder($folder.'/'.$file);
            }
        }

        closedir($dir_handle);
        rmdir($folder);
        return true;
    }

    public function updateManifest()
    {
        $name = Mage::getStoreConfig('simipwa/manifest/name') ? Mage::getStoreConfig('simipwa/manifest/name') : 'Progressive Web App';
        $short_name = Mage::getStoreConfig('simipwa/manifest/short_name') ? Mage::getStoreConfig('simipwa/manifest/short_name') : 'PWA';
        $default_icon = '/pwa/images/default_icon_512_512.png';
        $icon =  Mage::getStoreConfig('simipwa/manifest/logo') ? Mage::getStoreConfig('simipwa/manifest/logo') : $default_icon;
        $start_url = Mage::getStoreConfig('simipwa/general/pwa_main_url_site') ? '/' : '/pwa/';
        $theme_color = Mage::getStoreConfig('simipwa/manifest/theme_color') ? '#'.Mage::getStoreConfig('simipwa/manifest/theme_color') : '#3399cc';
        $background_color = Mage::getStoreConfig('simipwa/manifest/background_color') ? '#'.Mage::getStoreConfig('simipwa/manifest/background_color') : '#ffffff';
        $content = "{
              \"short_name\": \"$short_name\",
              \"name\": \"$name\",
              \"icons\": [
                {
                  \"src\": \"$icon\",
                  \"sizes\": \"192x192\",
                  \"type\": \"image/png\"
                },
                {
                  \"src\": \"$icon\",
                  \"sizes\": \"256x256\",
                  \"type\": \"image/png\"
                },
                {
                  \"src\": \"$icon\",
                  \"sizes\": \"384x384\",
                  \"type\": \"image/png\"
                },
                {
                  \"src\": \"$icon\",
                  \"sizes\": \"512x512\",
                  \"type\": \"image/png\"
                }
              ],
              \"start_url\": \"$start_url\",
              \"display\": \"standalone\",
              \"theme_color\": \"$theme_color\",
              \"background_color\": \"$background_color\",
              \"gcm_sender_id\" : \"832571969235\"
            }";
        $filePath = Mage::getBaseDir() . '/pwa/manifest.json';
        //zend_debug::dump($icon);die;
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $file = @fopen($filePath, 'w+');
        if ($file) {
            file_put_contents($filePath, $content);
        }
    }

    public function IsEnableAddToHomescreen()
    {
        return Mage::getStoreConfig('simipwa/manifest/enable');
    }

    public function isJSON($string)
    {
        return is_string($string) && is_array(json_decode($string, true)) ? true : false;
    }

    public function getApi($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    public function SyncApi($config,$url,$replaceStr)
    {
        if($config){
            $path_to_file = Mage::getBaseDir() .'/pwa/index.html';
            if(file_exists($path_to_file)){
                $file_contents = file_get_contents($path_to_file);
                $api = $this->getApi($url);
                if($this->isJSON($api)){
                    //update index.html file
                    $file_contents = str_replace($replaceStr, $api, $file_contents);
                    file_put_contents($path_to_file, $file_contents);
                }
            }
        }
    }
}