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
 * @package    ContusRestapi_Products
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
 * @since      1.0
 */
class ContusRestapi_Products_Model_Api2_Products extends Mage_Api2_Model_Resource {
   // Declaring the string literals variable
   const IS_SALABLE = 'is_saleable';
   const LOGIN_TOKEN = 'login/token';
   const NAME = 'name';
   const CATIMG = 'catalog/image';
   const SMALLIMG = 'small_image';
   const IMG_URL = 'image_url';
   const STOCK_QTY = 'stock_qty';
   const ENTITYID = 'entity_id';
   const STATUS = 'status';
   const PRICE = 'price';
   const TIERPRICE = 'tier_price';
   const ISWISHLIST = 'is_wishlist';
   const RATING = 'rating';
   const OPTIONS = 'options';
   const CATSTOCK = 'cataloginventory/stock_item';
   const ISSTOCK = 'is_stock';
   const IS_IN_STOCK = 'is_in_stock';
   const E_ENTITY_ID = 'e.entity_id';
   const AVAILABILITY = 'availability';
   const IS_SALEABLE = 'is_saleable';
   
   /**
    * function that is called when post is done **
    * Home page details
    *
    * @param array $data           
    *
    * @return array json array
    */
   protected function _retrieveCollection() {
      $response = array ();
      
      // Get Available stores based on website id
      $response = array ();
      $productArray = array ();
      
      // get page from request
      $page = $this->getRequest ()->getParam ( 'page' );
      if ($page <= 0) {
         $page = 1;
      }
      // get page from request
      $limit = $this->getRequest ()->getParam ( 'limit' );
      if ($limit <= 0) {
         $limit = 10;
      }
      // get website id from request
      $websiteId = ( int ) $this->getRequest ()->getParam ( 'website_id' );
      if ($websiteId <= 0) {
         $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
      }
      // get store id from request
      $storeId = ( int ) $this->getRequest ()->getParam ( 'store_id' );
      if ($storeId <= 0) {
         $storeId = Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
      }
      
      // get $category id from request
      $categoryid = ( int ) $this->getRequest ()->getParam ( 'category_id' );
      // get these type of products only
      $productType = array (
            "simple",
            "configurable" 
      );
      
      /**
       * Get product collection based on $categoryid
       *
       * @return array of Object
       */
      $category = new Mage_Catalog_Model_Category ();
      // this is category id
      $category->load ( $categoryid ); 
      $collection = $category->getProductCollection ();
      $collection = $collection->addAttributeToSelect ( '*' )->addAttributeToFilter ( 'status', array (
            'eq' => '1' 
      ) )->addAttributeToFilter ( 'visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH )->addAttributeToFilter ( 'type_id', array (
            'in' => $productType 
      ) );
      // sort and filetr product collection
      $collection = $this->_getProductByFilter ( $collection, $storeId );
      // get total products count
      $totalProducts = $collection->getSize ();
      $collection = $this->_getProductBySort ( $collection, $storeId );
      $products = $collection->load ();
      
      // get total pages with limit
      $last_page = ceil ( $totalProducts / $limit );
      if ($last_page < $page) {
         $productArray = array ();
      } else {
         $productArray = $this->getProductsList ( $products, $storeId );
      }
      $response ['success'] = 1;
      $response ['error'] = false;
      $response ['total_count'] = $totalProducts;
      $response ['result'] = $productArray;
      return json_encode ( $response );
   }
   
   /**
    *
    * @param array $products           
    * @param int $storeId           
    * @return array $productArray
    */
   public function getProductsList($products, $storeId) {
      $_proudct_data = array ();
      $i = 0;
      // get image size for resize
      $imageSize = ( int ) $this->getRequest ()->getParam ( 'image_size' );
      
      foreach ( $products as $product ) {
         $product->setStoreId ( $storeId )->load ( $product->getId () );
         $_proudct_data [$i] ['entity_id'] = $product->getId ();
         // get product name
         $_proudct_data [$i] [static::NAME] = $product->getName ();
         $_proudct_data [$i] ['type_id'] = $product->getTypeId ();
         // get the product final price
         if ($product->getTypeId () == 'grouped') {
            $_proudct_data [$i] ['regular_price_with_tax'] = number_format ( $product->getMinimalPrice (), 2, '.', '' );
            $_proudct_data [$i] ['fianl_price_with_tax'] = number_format ( $product->getMinimalPrice (), 2, '.', '' );
         } else {
            $_proudct_data [$i] ['regular_price_with_tax'] = number_format ( $product->getPrice (), 2, '.', '' );
            $_proudct_data [$i] ['fianl_price_with_tax'] = number_format ( $product->getFinalPrice (), 2, '.', '' );
         }
         // get product stock details
         $stockDetail = Mage::getModel ( static::LOGIN_TOKEN )->getStockDetail ( $product );
         $_proudct_data [$i] [static::IS_SALABLE] = $stockDetail [static::IS_SALABLE];
         $_proudct_data [$i] [static::ISSTOCK] = $stockDetail [static::ISSTOCK];
         // get product image
         if ($imageSize <= 0) {
            $_proudct_data [$i] [static::IMG_URL] = ( string ) Mage::helper ( static::CATIMG )->init ( $product, static::SMALLIMG );
         } else {
            $_proudct_data [$i] [static::IMG_URL] = ( string ) Mage::helper ( static::CATIMG )->init ( $product, static::SMALLIMG )->constrainOnly ( TRUE )->keepAspectRatio ( TRUE )->keepFrame ( FALSE )->resize ( $imageSize, null );
         }
         
         // get rating
         $_proudct_data [$i] ['summary_rating'] = Mage::getModel ( static::LOGIN_TOKEN )->rateSummary ( $product->getId (), $storeId ) ? Mage::getModel ( static::LOGIN_TOKEN )->rateSummary ( $product->getId (), $storeId ) : '0';
         
         // get wishlisted products by customer
         $wishListIds = array ();
         if ($customerId > 0) {
            $wishListIds = Mage::getModel ( static::LOGIN_TOKEN )->getWishlistByCustomer ( $customerId );
         }
         // Check to see the product is in wishlist
         if (in_array ( $product->getId (), $wishListIds )) {
            $_proudct_data [$i] [static::ISWISHLIST] = true;
         } else {
            $_proudct_data [$i] [static::ISWISHLIST] = false;
         }
         
         // get stock available qty
         if ($product->getTypeId () != 'configurable') {
            // get product availabile qty
            $_proudct_data [$i] [static::STOCK_QTY] = floatval ( Mage::getModel ( 'cataloginventory/stock_item' )->loadByProduct ( $product )->getQty () );
         } else {
            $_proudct_data [$i] [static::STOCK_QTY] = floatval ( '' );
         }
         
         $i ++;
      }
      
      return $_proudct_data;
   }
   
   /**
    * Filter products collection
    *
    * @param object $collection           
    * @param int $storeId           
    * @return mixed object
    */
   public function _getProductByFilter($collection, $storeId) {
      
      // get filter types attributes
      $filters = json_decode ( $this->getRequest ()->getParam ( 'filters' ), true );
      
      if (array_key_exists ( static::PRICE, $filters )) {
         
         // get price range
         $priceRange = $filters [static::PRICE];
         unset ( $filters [static::PRICE] );
      }
      // get availability tag
      if (array_key_exists ( static::AVAILABILITY, $filters )) {
         $availability = $filters [static::AVAILABILITY];
         unset ( $filters [static::AVAILABILITY] );
      }
      // get the table preix value
      $prefix = Mage::getConfig ()->getTablePrefix ();
      
      /**
       * Filter collection Based on stock availability
       *
       * @param boolean $availability
       *           0 | 1 input for stock in or stock out
       */
      if ($availability != '') {
         Mage::getSingleton ( 'cataloginventory/stock' )->addInStockFilterToCollection ( $collection );
      }
      
      /**
       * Filter collection Based on minimun and maximum price
       *
       * @param integer $minPrice           
       * @param integer $maxPrice           
       */
      if ($priceRange [0] != '' && $priceRange [1] != '') {
         $collection->getSelect ()->join ( array (
               'price1' => $prefix . 'catalog_product_index_price' 
         ), 'cat_pro.product_id = price1.entity_id', array (
               static::FINALPRICE => 'price1.final_price' 
         ) )->where ( 'price1.customer_group_id =1' )->where ( "price1.final_price >= $priceRange[0]  and price1.final_price <= $priceRange[1]" );
         $collection->getSelectSql ( true );
      }
      
      /**
       * Filter collection Based on attribute code
       *
       * @param string $filters
       *           input for brand array filter products
       */
      if (is_array ( $filters )) {
         foreach ( $filters as $attrCode => $attrValueId ) {
            $attributeid = $this->getAttributeId ( $attrCode );
            if ($attrValueId != '') {
               $attrValueId = implode ( ",", $attrValueId );
               $collection->getSelect ()->join ( array (
                     $attrCode => Mage::getModel ( 'core/resource' )->getTableName ( 'catalog_product_index_eav' ) 
               ), "e.entity_id = " . $attrCode . ".entity_id AND " . $attrCode . ".attribute_id = " . $attributeid . " AND " . $attrCode . ".value  IN(" . $attrValueId . ")", array (
                     'value' 
               ) );
               $collection->getSelectSql ( true );
            }
         }
      }
      $collection->getSelect ()->group ( static::E_ENTITY_ID );
      return $collection;
   }
   
   /**
    * Sort Products Collection
    *
    * @param object $collection           
    * @param int $storeId           
    * @return mixed object
    */
   public function _getProductBySort($collection, $storeId) {
      // get sort by for products from request
      $sortBy = $this->getRequest ()->getParam ( 'sortby' );
      // get orderby for products from request
      $orderBy = $this->getRequest ()->getParam ( 'orderby' );
      
      // get page from request
      $page = ( int ) $this->getRequest ()->getParam ( 'page' );
      if ($page <= 0) {
         $page = 1;
      }
      // get page from request
      $limit = ( int ) $this->getRequest ()->getParam ( 'limit' );
      if ($limit <= 0) {
         $limit = 10;
      }
      // get the table preix value
      $prefix = Mage::getConfig ()->getTablePrefix ();
      
      // sorting products by name
      if ($sortBy == static::NAME) {
         $collection->setPage ( $page, $limit );
         $collection->setOrder ( static::NAME, $orderBy );
      } else if ($sortBy == static::PRICE) {
         // sorting products by price
         $collection->setPage ( $page, $limit );
         $collection->getSelect ()->join ( array (
               static::PRICE => $prefix . 'catalog_product_index_price' 
         ), 'cat_pro.product_id = price.entity_id', array (
               static::FINALPRICE => 'price.final_price' 
         ) )->where ( 'price.customer_group_id =1' )->group ( static::E_ENTITY_ID )->order ( "price.final_price $orderBy" );
      } else if ($sortBy == static::RATING) {
         // sorting products by rating
         $collection->setPage ( $page, $limit );
         $collection->getSelect ()->joinLeft ( array (
               'rating' => $prefix . 'rating_option_vote_aggregated' 
         ), 'rating.entity_pk_value  = cat_pro.product_id and rating.store_id =' . $storeId, array (
               'percent_approved' => 'rating.percent_approved' 
         ) )->group ( static::E_ENTITY_ID )->order ( "rating.percent_approved $orderBy" );
         $collection->getSelectSql ( true );
      } else {
         $collection->setPage ( $page, $limit );
         $collection->setOrder ( static::NAME, 'asc' );
      }
      return $collection;
   }
}