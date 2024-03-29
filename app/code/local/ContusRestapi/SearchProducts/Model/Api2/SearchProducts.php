<?php
/**
 * Contus
 *  
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.apptha.com/LICENSE.txt
 *
 * ==============================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * ==============================================================
 * This package designed for Magento COMMUNITY edition
 * Apptha does not guarantee correct work of this extension
 * on any other Magento edition except Magento COMMUNITY edition.
 * Apptha does not provide extension support in case of
 * incorrect edition usage.
 * ==============================================================
 *
 * @category   Contus
 * @package    ContusRestapi_SearchProducts
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
 * @since      1.0
 */
class ContusRestapi_SearchProducts_Model_Api2_SearchProducts extends Mage_Api2_Model_Resource {
   
   // Declaring the string literals variable
   const STOREID = 'store_id';
   const CUSTOMER_ID = 'customer_id';
   const SEARCH_TERM = 'search_term';
   const CATEGORY_ID = 'category_id';
   const CATALOGSEARCH = 'catalogsearch';
   const IS_SALABLE = 'is_saleable';
   const LIMIT = 'limit';
   const PAGE = 'page';
   const LOGIN_TOKEN = 'login/token';
   const NAME = 'name';
   const CATIMG = 'catalog/image';
   const SMALLIMG = 'small_image';
   const IMG_URL = 'image_url';
   const FINAL_PRICE = 'final_price_with_tax';
   const STOCK_QTY = 'stock_qty';
   
   /**
    * Search products based on posted value
    *
    * @return array json array
    */
   protected function _retrieveCollection() {
      
      // get website id
      $websiteId = ( int ) Mage::app ()->getRequest ()->getParam ( 'website_id' );
      if ($websiteId <= 0) {
         $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
      }
      
      // get store id from request
      $storeId = ( int ) Mage::app ()->getRequest ()->getParam ( static::STOREID );
      if ($storeId <= 0) {
         $storeId = Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
      }
      // get search term from request
      $searchterm = Mage::app ()->getRequest ()->getParam ( static::SEARCH_TERM );
      // get category id from request
      $categoryId = ( int ) Mage::app ()->getRequest ()->getParam ( static::CATEGORY_ID );
      
      // get page from request
      $page = ( int ) $this->getRequest ()->getParam ( static::PAGE );
      if ($page <= 0) {
         $page = 1;
      }
      
      // get page from request
      $limit = ( int ) $this->getRequest ()->getParam ( static::LIMIT );
      if ($limit <= 0) {
         $limit = 10;
      }
      // get customer id from request
      $customerId = ( int ) Mage::app ()->getRequest ()->getParam ( static::CUSTOMER_ID );
      
      $collection = $this->getCatalogSearch ( array (
            static::SEARCH_TERM => $searchterm,
            static::STOREID => $storeId,
            static::CATEGORY_ID => $categoryId,
            static::CUSTOMER_ID => $customerId,
            static::PAGE => $page,
            static::LIMIT => $limit 
      ) );
      
      return $this->getResult ( $collection, $page, $limit, $customerId, $storeId );
   }
   
   /**
    * Get cart dotal qty
    *
    * @param integer $customerId           
    * @return array json array
    */
   protected function _retrieve() {
      $response = array ();
      $_proudct_data = array ();
      // get website id
      $websiteId = ( int ) Mage::app ()->getRequest ()->getParam ( 'website_id' );
      if ($websiteId <= 0) {
         $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
      }
      
      // get store id from request
      $storeId = ( int ) Mage::app ()->getRequest ()->getParam ( static::STOREID );
      if ($storeId <= 0) {
         $storeId = Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
      }
      // get image size for resize
      $imageSize = ( int ) $this->getRequest ()->getParam ( 'image_size' );
      
      // get search term from request
      $searchterm = Mage::app ()->getRequest ()->getParam ( static::SEARCH_TERM );
      $categoryId = '';
      $collection = $this->getCatalogSearch ( array (
            static::SEARCH_TERM => $searchterm,
            static::STOREID => $storeId,
            static::CATEGORY_ID => $categoryId 
      ) );
      if ($collection) {
         $collection->addAttributeToSort ( static::NAME, 'asc' );
         $collection->getSelect ()->limit ( 10 );
         
         $i = 0;
         foreach ( $collection as $product ) {
            $product->setStoreId ( $storeId )->load ( $product->getId () );
            $_proudct_data [$i] ['entity_id'] = $product->getId ();
            // get product name
            $_proudct_data [$i] [static::NAME] = $product->getName ();
            $_proudct_data [$i] [static::FINAL_PRICE] = number_format ( $product->getFinalPrice (), 2, '.', '' );
            // get product image
            if ($imageSize <= 0) {
               $_proudct_data [$i] [static::IMG_URL] = ( string ) Mage::helper ( static::CATIMG )->init ( $product, static::SMALLIMG );
            } else {
               $_proudct_data [$i] [static::IMG_URL] = ( string ) Mage::helper ( static::CATIMG )->init ( $product, static::SMALLIMG )->constrainOnly ( TRUE )->keepAspectRatio ( TRUE )->keepFrame ( FALSE )->resize ( $imageSize, null );
            }
            
            $i ++;
         }
      }
      
      $response ['success'] = 1;
      $response ['error'] = false;
      $response ['total_count'] = count ( $_proudct_data );
      $response ['result'] = $_proudct_data;
      
      return $response;
   }
   
   /**
    * Search products by given word
    *
    * @param array $data           
    * @return array
    */
   public function getCatalogSearch($data) {
      
      // get category id
      $categoryId = ( int ) $data [static::CATEGORY_ID];
      $collection = '';
      $_helper = Mage::helper ( static::CATALOGSEARCH );
      $queryParam = str_replace ( '%20', ' ', $data [static::SEARCH_TERM] );
      Mage::app ()->getRequest ()->setParam ( $_helper->getQueryParamName (), $queryParam );
      $query = $_helper->getQuery ();
      $query->setStoreId ( $data [static::STOREID] );
      $error = FALSE;
      if ($query->getQueryText () != '') {
         
         $this->searchQuery ( $query );
      } else {
         $error = TRUE;
      }
      // get these type of products only
      $productType = array (
            "simple",
            "configurable" 
      );
      
      // get city for filter products
      $city = ( int ) Mage::app ()->getRequest ()->getParam ( 'city' );
      
      if (! $error) {
         $collection = Mage::getResourceModel ( 'catalogsearch/fulltext_collection' );
         $collection->addAttributeToSelect ( Mage::getSingleton ( 'catalog/config' )->getProductAttributes () )->addSearchFilter ( $data [static::SEARCH_TERM] )->setstore ( $data [static::STOREID] )->addAttributeToFilter ( 'type_id', array (
               'in' => $productType 
         ) )->addStoreFilter ()->addUrlRewrite ();
         $collection->addMinimalPrice ()->addFinalPrice ()->addTaxPercents ();
         Mage::getSingleton ( 'catalog/product_status' )->addVisibleFilterToCollection ( $collection );
         Mage::getSingleton ( 'catalog/product_visibility' )->addVisibleInSearchFilterToCollection ( $collection );
         // Filter products by city
         if ($city) {
            $collection->addFieldToFilter ( 'city', array (
                  array (
                        'regexp' => $city 
                  ) 
            ) );
         }
         // ge products by catgory id
         if ($categoryId > 0) {
            $collection->getSelect ()->join ( array (
                  'category' => $prefix . 'catalog_category_product_index' 
            ), 'category.product_id  = cat_index.product_id and category.category_id  =' . $categoryId, array () )->group ( 'e.entity_id' );
         }
      }
      return $collection;
   }
   public function getResult($collection, $page, $limit, $customerId, $storeId) {
      $response = array ();
      $totalProducts = 0;
      $_proudct_data = array ();
      if ($collection) {
         // set pagination
         $collection->setPage ( $page, $limit );
         $collection->setOrder ( static::NAME, 'asc' );
         // get total products count
         $totalProducts = $collection->getSize ();
         // get total pages with limit
         $last_page = ceil ( $totalProducts / $limit );
         if ($last_page < $page) {
            $_proudct_data = array ();
         } else {
            $_proudct_data = $this->getSearchCollection ( $collection, $customerId, $storeId );
         }
      }
      
      $response ['success'] = 1;
      $response ['error'] = false;
      $response ['total_count'] = $totalProducts;
      $response ['result'] = $_proudct_data;
      
      return json_encode ( $response );
   }
   /**
    * Get product detail as collection
    *
    * @param array $collection           
    * @param int $customerId           
    * @param int $storeId           
    * @return array $_proudct_data
    */
   public function getSearchCollection($collection, $customerId, $storeId) {
      $_proudct_data = array ();
      $i = 0;
      // get image size for resize
      $imageSize = ( int ) $this->getRequest ()->getParam ( 'image_size' );
      
      foreach ( $collection as $product ) {
         $product->setStoreId ( $storeId )->load ( $product->getId () );
         $_proudct_data [$i] ['entity_id'] = $product->getId ();
         // get product name
         $_proudct_data [$i] [static::NAME] = $product->getName ();
         $_proudct_data [$i] ['type_id'] = $product->getTypeId ();
         // get the product final price
         if ($product->getTypeId () == 'grouped') {
            $_proudct_data [$i] ['regular_price_with_tax'] = number_format ( $product->getMinimalPrice (), 2, '.', '' );
            $_proudct_data [$i] [static::FINAL_PRICE] = number_format ( $product->getMinimalPrice (), 2, '.', '' );
         } else {
            $_proudct_data [$i] ['regular_price_with_tax'] = number_format ( $product->getPrice (), 2, '.', '' );
            $_proudct_data [$i] [static::FINAL_PRICE] = number_format ( $product->getFinalPrice (), 2, '.', '' );
         }
         // get product stock details
         $stockDetail = Mage::getModel ( static::LOGIN_TOKEN )->getStockDetail ( $product );
         $_proudct_data [$i] [static::IS_SALABLE] = $stockDetail [static::IS_SALABLE];
         $_proudct_data [$i] ['is_stock'] = $stockDetail ['is_stock'];
         // get product image
         if ($imageSize <= 0) {
            $_proudct_data [$i] [static::IMG_URL] = ( string ) Mage::helper ( static::CATIMG )->init ( $product, static::SMALLIMG );
         } else {
            $_proudct_data [$i] [static::IMG_URL] = ( string ) Mage::helper ( static::CATIMG )->init ( $product, static::SMALLIMG )->constrainOnly ( TRUE )->keepAspectRatio ( TRUE )->keepFrame ( FALSE )->resize ( $imageSize, null );
         }
         // get wishlisted products by customer
         $wishListIds = array ();
         if ($customerId > 0) {
            $wishListIds = Mage::getModel ( static::LOGIN_TOKEN )->getWishlistByCustomer ( $customerId );
         }
         // Check to see the product is in wishlist
         if (in_array ( $product->getId (), $wishListIds )) {
            $_proudct_data [$i] ['is_wishlist'] = true;
         } else {
            $_proudct_data [$i] ['is_wishlist'] = false;
         }
         // get rating
         $_proudct_data [$i] ['summary_rating'] = Mage::getModel ( static::LOGIN_TOKEN )->rateSummary ( $product->getId (), $storeId ) ? Mage::getModel ( static::LOGIN_TOKEN )->rateSummary ( $product->getId (), $storeId ) : '0';
         // get product stock details
         $inventoryDetail = Mage::getModel ( 'login/methods_functions' )->getinventoryDetail ( $product, $storeId );
         // get stock available qty
         $_proudct_data [$i] [static::STOCK_QTY] = $inventoryDetail [static::STOCK_QTY];
         // get product minimum qty allowed to cart
         $_proudct_data [$i] ['min_sale_qty'] = $inventoryDetail ['min_sale_qty'];
         // get product maximm qty allowed to cart
         $_proudct_data [$i] ['max_sale_qty'] = $inventoryDetail ['max_sale_qty'];
         // get product increment qty allowed to cart
         $_proudct_data [$i] ['qty_increments'] = $inventoryDetail ['qty_increments'];
         $_proudct_data [$i] ['is_qty_decimal'] = $inventoryDetail ['is_qty_decimal'];
         
         $i ++;
      }
      
      return $_proudct_data;
   }
   
   /**
    * Search Products
    *
    * @param object $query           
    */
   public function searchQuery($query) {
      $check = FALSE;
      if (Mage::helper ( static::CATALOGSEARCH )->isMinQueryLength ()) {
         $query->setId ( 0 )->setIsActive ( 1 )->setIsProcessed ( 1 );
      } else {
         if ($query->getId ()) {
            $query->setPopularity ( $query->getPopularity () + 1 );
         } else {
            $query->setPopularity ( 1 );
         }
         
         if ($query->getRedirect ()) {
            $query->save ();
            $check = TRUE;
         } else {
            $query->prepare ();
         }
      }
      if (! $check) {
         Mage::helper ( static::CATALOGSEARCH )->checkNotes ();
         if (! Mage::helper ( static::CATALOGSEARCH )->isMinQueryLength ()) {
            $query->save ();
         }
      }
   }
}