<?php

/**
 * Overwrite Catalog Core Rest API Methods
 *
 * Contus
 * 
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
 * @package    ContusRestapi_ProductList
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
 * @since      1.0
 */
class ContusRestapi_ProductList_Model_Api2_ProductList_Rest extends Mage_Catalog_Model_Api2_Product_Rest {
   
   // Declaring the string literals variable
   const ENTITYID = 'entity_id';
   const NAME = 'name';
   const IMGURL = 'image_url';
   const REGULARPRICE = 'regular_price';
   const FINALPRICE = 'final_price';
   const PRODUCTTYPE = 'product_type';
   const PROCOLLECTION = 'collection';
   const VISIBILITY = 'visibility';
   const STATUS = 'status';
   const CATIMG = 'catalog/image';
   const SMALLIMG = 'small_image';
   const PRICE = 'price';
   const TIERPRICE = 'tier_price';
   const ISWISHLIST = 'is_wishlist';
   const RATING = 'rating';
   const OPTIONS = 'options';
   const CATSTOCK = 'cataloginventory/stock_item';
   const ISSTOCK = 'is_stock';
   const OPTIONID = 'option_id';
   const TITLE = 'title';
   const WEBSITE_ID = 'website_id';
   const STORE_ID = 'store_id';
   const LOGIN_TOKEN = 'login/token';
   const IS_IN_STOCK = 'is_in_stock';
   const E_ENTITY_ID = 'e.entity_id';
   const AVAILABILITY = 'availability';
   const IS_SALEABLE = 'is_saleable';
   const MEDIA = 'media';
   const MOBILEAPP = 'mobileapp';
   const RESIZED = 'resized';
   const ATTRIBUTE_ID = 'attribute_id';
   const CAT_PRO = 'catalog/product';
   const SELLER_ID = 'seller_id';
   const STOCK_QTY = 'stock_qty';
   const MIN_SALE_QTY = 'min_sale_qty';
   const MAX_SALE_QTY = 'max_sale_qty';
   const QTY_INCR = 'qty_increments';
   const IS_QTY_DECIMAL = 'is_qty_decimal';
   CONST LOGIN_FUNCTIONS = 'login/methods_functions';
   
   /**
    * Retrieve product data
    *
    * @return array
    */
   protected function _retrieve() {
      $response = array ();
      
      // get website id from request
      $websiteId = ( int ) $this->getRequest ()->getParam ( static::WEBSITE_ID );
      if ($websiteId <= 0) {
         $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
      }
      
      // get store id from request
      $storeId = ( int ) $this->getRequest ()->getParam ( static::STORE_ID );
      if ($storeId <= 0) {
         $storeId = Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
      }
      
      $productDetail = array ();
      // get default product details
      $product = $this->_getProduct ();
      // load producs based on store id
      $product->setStoreId ( $storeId )->load ( $product->getId () );
      $productDetail = $this->_prepareProductForResponse ( $product );
      
      // get product description
      $productDetail ['description'] = $product->getDescription ();
      // get product short description
      $productDetail ['short_description'] = $product->getShortDescription ();
      
      // get additional images for product
      $productDetail ['images'] = $this->getProductImages ( $product );
      
      // Getting ratings/reviews
      // get only five revieews in product detail page
      $reviews = Mage::getModel ( static::LOGIN_TOKEN )->getReviews ( $product->getId (), $storeId, 1, 5 );
      $productDetail ['reviews'] = $reviews ['reviews'];
      // get product rating count summary
      $ratings = Mage::getModel ( static::LOGIN_TOKEN )->getRatingResult ( $product->getId (), $storeId );
      $productDetail [static::RATING] = $ratings [static::RATING];
      
      $type = $product->getTypeId ();
      $options = array ();
      $super_group = array ();
      $links = array ();
      switch ($type) {
         case Mage_Catalog_Model_Product_Type::TYPE_GROUPED :
            $super_group = $this->getGroupedProductOptions ( $product );
            break;
         
         case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE :
            $options = $this->getConfigurableProductOptions ( $product, $storeId );
            break;
         
         case Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE :
            $links = $this->getDownloadableLinks ( $product );
            break;
         
         default :
            break;
      }
      // get configurable product options
      if (! empty ( $options )) {
         $productDetail [static::OPTIONS] = $options;
      }
      // get grouped product options
      if (! empty ( $super_group )) {
         $productDetail ['group_options'] = $super_group;
      }
      
      // get downloadble product links
      if (! empty ( $links )) {
         $productDetail ['links'] = $links;
      }
      
      // get custom options
      $customOptions = $this->getCustomOptions ( $product );
      $productDetail ['custom_options'] = $customOptions;
      
      if (Mage::getStoreConfig ( 'marketplace/marketplace/activate' )) {
         $productDetail ['seller_info'] = $this->getmarketPlaceDetal ( $product, $storeId );
      }
      
      $response ['error'] = false;
      $response ['success'] = 1;
      $response ['result'] = $productDetail;
      return $response;
   }
   
   /**
    * Get seller details
    *
    * @param objet $product           
    * @param int $storeId           
    * @return array $sellers
    */
   public function getmarketPlaceDetal($product, $storeId) {
      $productId = $product->getEntity_id ();
      // Get Seller Id
      $sellerId = $product->getData ( static::SELLER_ID );
      if ($sellerId > 0) {
         $currentSeller [] = Mage::getModel ( static::LOGIN_FUNCTIONS )->sellerdisplay ( $sellerId, '1', $storeId, $productId );
      }
      $allSellers = $this->getSellerInfo ( $productId );
      return array_merge ( ( array ) $currentSeller, ( array ) $allSellers );
   }
   
   /**
    * Get Seller info by product id
    *
    * @param int $productId
    *           return array sellers information
    */
   public function getSellerInfo($productId) {
      $sellerInfo = array ();
      $i = 0;
      $collection = $this->getComparePrice ( $productId );
      
      foreach ( $collection as $_collection ) {
         $sellerId = $_collection->getSellerId ();
         if ($sellerId > 0) {
            $sellerInfo [$i] = Mage::getModel ( static::LOGIN_FUNCTIONS )->sellerdisplay ( $sellerId, '0', $storeId, $_collection->getId () );
         }
         $i ++;
         if ($i == count ( $collection )) {
            continue;
         }
      }
      return $sellerInfo;
   }
   
   /**
    * Get Product Collection with 'compare_product_id' attribute filter
    *
    * Passed the product id for which we need to compare price
    *
    * @param int $productId
    *           Return product collection as array
    * @return array
    */
   public function getComparePrice($productId) {
      $productCollection = Mage::getModel ( static::CAT_PRO )->getCollection ()->addAttributeToSelect ( '*' )->addAttributeToFilter ( 'is_assign_product', array (
            'eq' => 1 
      ) )->addAttributeToFilter ( static::STATUS, array (
            'eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED 
      ) );
      
      $productCollection->addFieldToFilter ( 'assign_product_id', array (
            'eq' => $productId 
      ) );
      $productCollection->setOrder ( 'price', 'ASC' );
      return $productCollection;
   }
   
   /**
    * Function to get seller product
    *
    * Passed the seller id as $sellerId to get particular seller products
    *
    * @param int $sellerId
    *           Return products of the seller
    */
   function sellerproduct($sellerid) {
      $sellerproduct = Mage::getModel ( static::CAT_PRO )->getCollection ()->addFieldToFilter ( static::SELLER_ID, $sellerid );
      return $sellerproduct->getData ();
   }
   
   /**
    * Get product Images
    *
    * @param object $product           
    * @return array $product_images
    */
   public function getProductImages($product) {
      $_images = $product->getMediaGalleryImages ();
      $product_images = array ();
      // get image size for resize
      $imageSize = ( int ) $this->getRequest ()->getParam ( 'image_size' );
      
      if (isset ( $_images )) {
         $i = 0;
         foreach ( $_images as $_image ) {
            if ($imageSize <= 0) {
               $product_images [$i] = $_image->url;
            } else {
               $product_images [$i] = $this->imageResize ( $_image->url, $imageSize );
            }
            
            $i ++;
         }
      }
      return $product_images;
   }
   /**
    * Image resize and store
    *
    * @param string $imageUrl           
    * @param int $imageSize           
    * @return string image url
    */
   public function imageResize($imageUrl, $imageSize) {
      if (! file_exists ( Mage::getBaseDir ( static::MEDIA ) . DS . static::MOBILEAPP . DS . static::RESIZED )) {
         mkdir ( Mage::getBaseDir ( static::MEDIA ) . DS . static::MOBILEAPP . DS . static::RESIZED, 0777 );
      }
      
      $imageName = substr ( strrchr ( $imageUrl, "/" ), 1 );
      // get file extension
      $extension = end ( explode ( ".", $imageName ) );
      $imageName = uniqid () . '.' . $extension;
      $imageResized = Mage::getBaseDir ( static::MEDIA ) . DS . static::MOBILEAPP . DS . static::RESIZED . DS . $imageName;
      $dirImg = Mage::getBaseDir () . str_replace ( "/", DS, strstr ( $imageUrl, '/' . static::MEDIA ) );
      if (! file_exists ( $imageResized ) && file_exists ( $dirImg )) :
         $imageObj = new Varien_Image ( $dirImg );
         $imageObj->constrainOnly ( true );
         $imageObj->keepAspectRatio ( true );
         $imageObj->keepFrame ( false );
         $imageObj->resize ( $imageSize, null );
         $imageObj->save ( $imageResized );
      
      
      
      
      
      
        endif;
      return Mage::getBaseUrl ( static::MEDIA ) . static::MOBILEAPP . "/" . static::RESIZED . "/" . $imageName;
   }
   
   /**
    * Grouped Product Options - Get Associated products
    *
    * @param object $product           
    * @return array $options
    */
   public function getGroupedProductOptions($product) {
      $options = array ();
      $associatedProducts = $product->getTypeInstance ( true )->getAssociatedProducts ( $product );
      
      if (count ( $associatedProducts )) {
         foreach ( $associatedProducts as $product ) {
            
            // get stock data
            $stockItem = Mage::getModel ( static::CATSTOCK )->loadByProduct ( $product->getId () );
            $options [] = array (
                  static::OPTIONID => $product->getId (),
                  'option_value' => $product->getName (),
                  'option_title' => $product->getName (),
                  'option_regular_price' => number_format ( $product->getPrice (), 2, '.', '' ),
                  'option_final_price' => number_format ( $product->getFinalPrice (), 2, '.', '' ),
                  'option_image' => $product->getThumbnailUrl ( 105, 80 ),
                  static::ISSTOCK => ($stockItem->getIsInStock () > 0) 
            );
         }
      }
      
      return $options;
   }
   
   /**
    * Get Custom options of Simple Product
    *
    * @param object $product           
    * @return array $options
    */
   public function getCustomOptions($product) {
      $options = array ();
      $optionsData = $product->getOptions ();
      foreach ( $optionsData as $option ) {
         $optionVal = array ();
         $optionValues = $option->getValues ();
         foreach ( $optionValues as $optVal ) {
            $optionVal [] = array (
                  static::OPTIONID => $optVal->getOptionId (),
                  'option_type_id' => $optVal->getOptionTypeId (),
                  'sku' => $optVal->getSku (),
                  'sort_order' => $optVal->getSortOrder (),
                  static::TITLE => $optVal->getTitle (),
                  static::PRICE => $optVal->getPrice (),
                  'price_type' => $optVal->getPriceType () 
            );
         }
         
         $options [] = array (
               'type' => $option->getType (),
               static::OPTIONID => $option->getOptionId (),
               'product_id' => $option->getProductId (),
               'type' => $option->getType (),
               'is_require' => $option->getIsRequire (),
               'sort_order' => $option->getSortOrder (),
               static::TITLE => $option->getTitle (),
               static::PRICE => $option->getPrice (),
               'price_type' => $option->getPriceType (),
               'option_value' => $optionVal 
         );
      }
      
      return $options;
   }
   
   /**
    * Get config options for configurable product
    *
    * @param object $product           
    * @param int $storeId           
    * @return array $result
    */
   public function getConfigurableProductOptions($product, $storeId) {
      // get product price
      $finalPrice = $product->getFinalPrice ();
      
      // Load all used configurable attributes
      $configurableAttributeCollection = $product->getTypeInstance ( true )->getConfigurableAttributes ( $product );
      
      $allProducts = $product->getTypeInstance ( true )->getUsedProducts ( null, $product );
      foreach ( $allProducts as $product ) {
         $products [] = $product;
      }
      
      $options = array ();
      $result = array ();
      $i = 0;
      foreach ( $configurableAttributeCollection as $productAttribute ) {
         $options [$i] [static::TITLE] = $productAttribute ['label'];
         $options [$i] ['code'] = $productAttribute->getProductAttribute ()->getAttributeCode ();
         $options [$i] [static::ATTRIBUTE_ID] = $productAttribute [static::ATTRIBUTE_ID];
         $i ++;
      }
      $result ['config'] = $options;
      $resultattr = array ();
      // Get combinations
      foreach ( $products as $product ) {
         $attr = array ();
         // get produt stock qty for simple product
         /**
          *
          * @var $stockItem Mage_CatalogInventory_Model_Stock_Item
          */
         $stockItem = $product->getStockItem ();
         if (! $stockItem) {
            $stockItem = Mage::getModel ( static::CATSTOCK );
            $stockItem->loadByProduct ( $product );
         }
         $stockQty = floor ( $stockItem->getQty () );
         $is_stock = $stockItem->getIsInStock ();
         $j = 0;
         $valuearry ['product_id'] = $product->getId ();
         // get product stock details
         $inventoryDetail = Mage::getModel ( static::LOGIN_FUNCTIONS )->getinventoryDetail ( $product, $storeId );
         // get stock qty
         $valuearry [static::STOCK_QTY] = $inventoryDetail [static::STOCK_QTY];
         $valuearry [static::MIN_SALE_QTY] = $inventoryDetail [static::MIN_SALE_QTY];
         $valuearry [static::MAX_SALE_QTY] = $inventoryDetail [static::MAX_SALE_QTY];
         $valuearry [static::QTY_INCR] = $inventoryDetail [static::QTY_INCR];
         $valuearry [static::IS_QTY_DECIMAL] = $inventoryDetail [static::IS_QTY_DECIMAL];
         foreach ( $configurableAttributeCollection as $attribute ) {
            
            $productAttribute = $attribute->getProductAttribute ();
            $attrCode = $productAttribute->getAttributeCode ();
            $attributeValue = $product->getData ( $attrCode );
            
            /* getting option text value */
            if ($productAttribute->usesSource ()) {
               $label = $productAttribute->setStoreId ( $storeId )->getSource ()->getOptionText ( $attributeValue );
            } else {
               $label = '';
            }
            
            /**
             * Get price for associated product
             */
            $prices = $attribute->getPrices ();
            $value = $product->getData ( $attribute->getProductAttribute ()->getAttributeCode () );
            
            $valuearry ['label'] = $label;
            $valuearry ['code'] = $attrCode;
            $valuearry ['config_id'] = $attributeValue;
            $valuearry [static::ISSTOCK] = $is_stock;
            $valuearry ['stock_qty'] = $stockQty;
            $valuearry [static::PRICE] = $this->calculateCustumPrice ( $prices, $value, $finalPrice );
            
            $val [$options [$j] ['code']] = $attributeValue;
            $j ++;
            array_push ( $attr, $valuearry );
         }
         // get configurable product options
         $attr = $this->getAttrColl ( $val, $attr );
         
         $resultattr = array_merge ( $resultattr, $attr );
      }
      $result ['attr'] = $resultattr;
      return $result;
   }
   
   /**
    * Get price for config options
    *
    * @param array $prices           
    * @param string $value           
    * @param float $finalPrice           
    * @return string $totalPrice
    */
   public function calculateCustumPrice($prices, $value, $finalPrice) {
      $totalPrice = 0;
      foreach ( $prices as $price ) {
         if ($price ['is_percent']) {
            // if the price is specified in percents
            $pricesByAttributeValues [$price ['value_index']] = ( float ) $price ['pricing_value'] * $finalPrice / 100;
         } else {
            // if the price is absolute value
            $pricesByAttributeValues [$price ['value_index']] = ( float ) $price ['pricing_value'];
         }
      }
      
      if (isset ( $pricesByAttributeValues [$value] )) {
         $totalPrice = $pricesByAttributeValues [$value];
      }
      return strval ( $totalPrice );
   }
   
   /**
    * Collect configurable product options
    *
    * @param array $val           
    * @param array $attr           
    * @return array $attr
    */
   public function getAttrColl($val, $attr) {
      $attr [0] ['attr_id'] = array ();
      foreach ( $val as $key => $value ) {
         
         for($k = 0; $k < count ( $attr ); $k ++) {
            $attrValue = array ();
            if ($attr [$k] ['code'] != $key) {
               $attrValue ['value'] = $value;
               $attrValue ['code'] = $key;
               
               $attr [$k] ['attr_id'] [] = $attrValue;
            }
         }
      }
      return $attr;
   }
   
   /**
    * Load product by its SKU or ID provided in request
    *
    * @return Mage_Catalog_Model_Product
    */
   public function _getProduct() {
      
      // get website id from request
      $websiteId = ( int ) $this->getRequest ()->getParam ( static::WEBSITE_ID );
      if ($websiteId <= 0) {
         $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
      }
      
      // get store id from request
      $storeId = ( int ) $this->getRequest ()->getParam ( static::STORE_ID );
      if ($storeId <= 0) {
         $storeId = Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
      }
      if (is_null ( $this->_product )) {
         $productId = $this->getRequest ()->getParam ( 'id' );
         /**
          *
          * @var $productHelper Mage_Catalog_Helper_Product
          */
         $productHelper = Mage::helper ( 'catalog/product' );
         $product = $productHelper->getProduct ( $productId, $storeId );
         if (! ($product->getId ())) {
            $this->_critical ( static::RESOURCE_NOT_FOUND );
         }
         // check if product belongs to website current
         if ($this->_getStore ()->getId ()) {
            $isValidWebsite = in_array ( $websiteId, $product->getWebsiteIds () );
            if (! $isValidWebsite) {
               $this->_critical ( static::RESOURCE_NOT_FOUND );
            }
         }
         // Check display settings for customers & guests
         if ($this->getApiUser ()->getType () != Mage_Api2_Model_Auth_User_Admin::USER_TYPE) {
            // check if product assigned to any website and can be shown
            if ((! Mage::app ()->isSingleStoreMode () && ! count ( $product->getWebsiteIds () )) || ! $productHelper->canShow ( $product )) {
               $this->_critical ( static::RESOURCE_NOT_FOUND );
            }
         }
         $this->_product = $product;
      }
      return $this->_product;
   }
   
   /**
    * Get Downloadable product
    *
    * @param object $product           
    * @return array $links
    */
   public function getDownloadableLinks($product) {
      $productAttributeLinks = $product->getTypeInstance ( true )->getLinks ( $product );
      $i = 0;
      foreach ( $productAttributeLinks as $productLinks ) {
         $links [$i] ['link_id'] = $productLinks->getLinkId ();
         $links [$i] [static::ENTITYID] = $productLinks->getProductId ();
         $links [$i] ['is_require'] = $product->getLinksPurchasedSeparately ();
         $links [$i] [static::PRICE] = $productLinks->getPrice ();
         $links [$i] [static::TITLE] = $productLinks->getTitle ();
         $i ++;
      }
      return $links;
   }
   
   /**
    * Retrieve list of products
    *
    * @return array
    */
   protected function _retrieveCollection() {
      $response = array ();
      $productArray = array ();
      // get website id from request
      $websiteId = ( int ) $this->getRequest ()->getParam ( static::WEBSITE_ID );
      if ($websiteId <= 0) {
         $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
      }
      // get store id from request
      $storeId = ( int ) $this->getRequest ()->getParam ( static::STORE_ID );
      if ($storeId <= 0) {
         $storeId = Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
      }
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
      
      // get these type of products only
      $productType = array (
            "simple",
            "configurable" 
      );
      
      // get city for filter products
      $city = ( int ) Mage::app ()->getRequest ()->getParam ( 'city' );
      
      /**
       *
       * @var $collection Mage_Catalog_Model_Resource_Product_Collection
       */
      $collection = Mage::getResourceModel ( 'catalog/product_collection' );
      $collection->addStoreFilter ( $storeId )->addPriceData ( $this->_getCustomerGroupId (), $websiteId )->addAttributeToSelect ( static::NAME, static::PRICE )->addAttributeToFilter ( static::VISIBILITY, array (
            'neq' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE 
      ) )->addAttributeToFilter ( static::STATUS, array (
            'eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED 
      ) )->addAttributeToFilter ( 'type_id', array (
            'in' => $productType 
      ) );
      // Filter products by city
      if ($city) {
         $collection->addFieldToFilter ( 'city', array (
               array (
                     'regexp' => $city 
               ) 
         ) );
      }
      
      $this->_applyCategoryFilter ( $collection );
      $this->_applyCollectionModifiers ( $collection );
      
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
      $productArray = array ();
      
      // @var Mage_Catalog_Model_Product $product
      $productColl = array ();
      foreach ( $products as $product ) {
         
         $product->setStoreId ( $storeId )->load ( $product->getId () );
         $this->_setProduct ( $product );
         $productColl = $this->_prepareProductForResponse ( $product );
         
         $productArray [] = $productColl;
      }
      
      return $productArray;
   }
   
   /**
    * Add special fields to product get response
    *
    * @param Mage_Catalog_Model_Product $product           
    */
   protected function _prepareProductForResponse(Mage_Catalog_Model_Product $product) {
      $websiteId = ( int ) $this->getRequest ()->getParam ( static::WEBSITE_ID );
      if ($websiteId <= 0) {
         $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
      }
      // get store id from request
      $storeId = ( int ) $this->getRequest ()->getParam ( static::STORE_ID );
      if ($storeId <= 0) {
         $storeId = Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
      }
      // get customer id from request
      $customerId = ( int ) $this->getRequest ()->getParam ( 'customer_id' );
      // get image size for resize
      $imageSize = ( int ) $this->getRequest ()->getParam ( 'image_size' );
      
      /**
       *
       * @var $productHelper Mage_Catalog_Helper_Product
       */
      $productHelper = Mage::helper ( static::CAT_PRO );
      
      $productData [static::ENTITYID] = $product->getEntity_id ();
      $productData [static::NAME] = $product->getName ();
      $productData ['type_id'] = $product->getTypeId ();
      $product->setWebsiteId ( $websiteId );
      // customer group is required in product for correct prices calculation
      $product->setCustomerGroupId ( $this->_getCustomerGroupId () );
      // calculate prices
      $finalPrice = $product->getFinalPrice ();
      if ($product->getTypeId () == 'grouped') {
         $productData ['regular_price_with_tax'] = number_format ( $product->getMinimalPrice (), 2, '.', '' );
         $productData ['final_price_with_tax'] = number_format ( $product->getMinimalPrice (), 2, '.', '' );
      } else {
         $productData ['regular_price_with_tax'] = number_format ( $this->_applyTaxToPrice ( $product->getPrice (), true ), 2, '.', '' );
         $productData ['regular_price_without_tax'] = number_format ( $this->_applyTaxToPrice ( $product->getPrice (), false ), 2, '.', '' );
         $productData ['final_price_with_tax'] = number_format ( $this->_applyTaxToPrice ( $finalPrice, true ), 2, '.', '' );
         $productData ['final_price_without_tax'] = number_format ( $this->_applyTaxToPrice ( $finalPrice, false ), 2, '.', '' );
      }
      // get product stock details
      $stockDetail = Mage::getModel ( static::LOGIN_TOKEN )->getStockDetail ( $product );
      $productData [static::IS_SALEABLE] = $stockDetail [static::IS_SALEABLE];
      $productData [static::ISSTOCK] = $stockDetail ['is_stock'];
      // get product image
      if ($imageSize <= 0) {
         $productData [static::IMGURL] = ( string ) Mage::helper ( static::CATIMG )->init ( $product, static::SMALLIMG );
      } else {
         $productData [static::IMGURL] = ( string ) Mage::helper ( static::CATIMG )->init ( $product, static::SMALLIMG )->constrainOnly ( TRUE )->keepAspectRatio ( TRUE )->keepFrame ( FALSE )->resize ( $imageSize, null );
      }
      // get rating
      $productData ['summary_rating'] = Mage::getModel ( static::LOGIN_TOKEN )->rateSummary ( $product->getId (), $storeId ) ? Mage::getModel ( static::LOGIN_TOKEN )->rateSummary ( $product->getId (), $storeId ) : '0';
      // get wishlisted products by customer
      $wishListIds = array ();
      if ($customerId > 0) {
         $wishListIds = Mage::getModel ( static::LOGIN_TOKEN )->getWishlistByCustomer ( $customerId );
      }
      // Check to see the product is in wishlist
      if (in_array ( $product->getId (), $wishListIds )) {
         $productData [static::ISWISHLIST] = true;
      } else {
         $productData [static::ISWISHLIST] = false;
      }
      // get product stock details
      $inventoryDetail = Mage::getModel ( static::LOGIN_FUNCTIONS )->getinventoryDetail ( $product, $storeId );
      // get stock qty
      $productData [static::STOCK_QTY] = $inventoryDetail [static::STOCK_QTY];
      $productData [static::MIN_SALE_QTY] = $inventoryDetail [static::MIN_SALE_QTY];
      $productData [static::MAX_SALE_QTY] = $inventoryDetail [static::MAX_SALE_QTY];
      $productData [static::QTY_INCR] = $inventoryDetail [static::QTY_INCR];
      $productData [static::IS_QTY_DECIMAL] = $inventoryDetail [static::IS_QTY_DECIMAL];
      
      if ($this->getActionType () == static::ACTION_TYPE_ENTITY) {
         // define URLs
         $productData ['url'] = $productHelper->getProductUrl ( $product->getId () );
         if ($imageSize <= 0) {
            $productData [static::IMGURL] = ( string ) Mage::helper ( static::CATIMG )->init ( $product, 'image' );
         } else {
            $productData [static::IMGURL] = ( string ) Mage::helper ( static::CATIMG )->init ( $product, 'image' )->constrainOnly ( TRUE )->keepAspectRatio ( TRUE )->keepFrame ( FALSE )->resize ( $imageSize, null );
         }
         
         // @var $cartHelper Mage_Checkout_Helper_Cart
         $cartHelper = Mage::helper ( 'checkout/cart' );
         $productData ['buy_now_url'] = $cartHelper->getAddUrl ( $product );
         
         /**
          *
          * @var $reviewModel Mage_Review_Model_Review
          */
         $reviewModel = Mage::getModel ( 'review/review' );
         $productData ['total_reviews_count'] = $reviewModel->getTotalReviews ( $product->getId (), true, $storeId );
         $productData [static::TIERPRICE] = number_format ( $this->_getTierPrices (), 2, '.', '' );
         // get product has custom options or not
         $productData ['has_custom_options'] = count ( $product->getOptions () ) > 0;
      } else {
         // remove tier price from response
         $product->unsetData ( static::TIERPRICE );
         unset ( $productData [static::TIERPRICE] );
      }
      return $productData;
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
   
   /**
    * Get attribute id by attribute code
    *
    * @param string $code
    *           Getting the particular attribute id
    *           
    * @return int $id
    */
   public function getAttributeId($code = '') {
      if (! $code) {
         return;
      }
      $attribute_details = Mage::getSingleton ( "eav/config" )->getAttribute ( 'catalog_product', $code );
      return $attribute_details->getData ( static::ATTRIBUTE_ID );
   }
}
