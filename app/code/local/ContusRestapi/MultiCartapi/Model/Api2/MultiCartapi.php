<?php
/**
 * Contus
 * 
 * Bulk Cart Api
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
 * @package    ContusRestapi_MultiCartapi
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
 * @since      1.0
 */
class ContusRestapi_MultiCartapi_Model_Api2_MultiCartapi extends Mage_Api2_Model_Resource {
   // define staic variable
   const STOREID = 'store_id';
   const WEBSITEID = 'website_id';
   const CUSTOMER_ID = 'customer_id';
   const ENTITYID = 'entity_id';
   const PRODUCT_ID = 'product_id';
   const QUOTE_ID = 'quote_id';
   const SLAES_QUOTE = 'sales/quote';
   const SUCCESS = 'success';
   const MESSAGE = 'message';
   const ERROR = 'error';
   const RESULT = 'result';
   const TOKEN = 'token';
   const ITEM_COUNT = 'item_count';
   const ITEMS_COUNT = 'items_count';
   const LINKS = 'links';
   const LOGIN_TOKEN = 'login/token';
   const VALID_TOKEN = 'isValidToken';
   const AUTH_FAIL = 'Authentication failed.';
   const CUSTOM_OPTION = 'custom_option';
   const OPTIONS = 'options';
   const SUPER_ATTR = 'super_attribute';
   const SUPER_GRP = 'super_group';
   const QTY = 'qty';
   const UPDATED = 'updated';
   
   /**
    * function that is called when post is done **
    * Add product to cart
    *
    * @param array $data           
    * @return array json array
    */
   protected function _create(array $data) {
      $response = array ();
      $messages = array ();
      // Get website id
      $websiteId = (isset ( $data [static::WEBSITEID] )) ? $data [static::WEBSITEID] : Mage::app ()->getWebsite ( 'base' )->getId ();
      // get store id
      $storeId = (isset ( $data [static::STOREID] )) ? $data [static::STOREID] : Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
      // get customer id
      $customerId = ( int ) $data [static::CUSTOMER_ID];
      
      $products = $data ['detail'];
      // get base currency code
      $currencyCode = $data ['currencyCode'];
      // get quote id from request
      $quoteId = ( int ) $data [static::QUOTE_ID];
      // get cart quote by customer
      $quote = Mage::getModel ( static::LOGIN_TOKEN )->setSaleQuoteByCustomer ( $customerId, $storeId, $currencyCode );
      if ($quoteId <= 0) {
         $quoteId = $quote->getId ();
      }
      
      /**
       * Check this customer has valid token or not
       *
       * @var $isValidToken Mage_Login_Model_Token
       */
      $isValidToken = Mage::getModel ( static::LOGIN_TOKEN )->checkUserToken ( $customerId, $data [static::TOKEN] );
      $error_flag = false;
      try {
         
         if (! $isValidToken) {
            throw new Exception ( static::AUTH_FAIL );
         }
         $i = 0;
         foreach ( $products as $item ) {
            $product = $this->_getProduct ( $item [static::PRODUCT_ID], $storeId, 'id' );
            
            // Note: 1. $item ['flag'] = 1 => for add item to cart
            // Note: 2. $item ['flag'] = 2 => for update item in cart
            // check product
            if (is_null ( $product )) {
               $messages [$i] [static::SUCCESS] = 0;
               $messages [$i] [static::MESSAGE] = $item [static::PRODUCT_ID] . ' - Can not specify the product.';
               $error_flag = false;
            } elseif ($item ['flag'] == 2) {
               // update product qty in cart
               $quote = Mage::getModel ( static::SLAES_QUOTE );
               $quote->setStoreId ( $storeId )->load ( $quoteId );
               $productItem = $this->updateToCart ( $product, $quote, $item [static::QTY], $item );
               $quoteItem = Mage::getModel ( static::LOGIN_TOKEN )->_getQuoteItemByProduct ( $quote, $product, Mage::getModel ( static::LOGIN_TOKEN )->_getProductRequest ( $productItem ) );
               
               $update = $this->cartUpdate ( $quoteItem, $quote, $item [static::QTY] );
               $messages [$i] [static::SUCCESS] = $update [static::SUCCESS];
               $messages [$i] [static::MESSAGE] = $product->getName () . '- ' . $update [static::MESSAGE];
               $error_flag = true;
            } else {
               $quote = Mage::getModel ( static::LOGIN_TOKEN )->setSaleQuoteByCustomer ( $customerId, $storeId, $currencyCode );
               // add product to cart
               $error = $this->addToCart ( $product, $quote, $item [static::QTY], $item );
               if (is_string ( $error )) {
                  $messages [$i] [static::MESSAGE] = $product->getName () . '- ' . $error;
                  $messages [$i] [static::SUCCESS] = 0;
                  $error_flag = false;
               } else {
                  $messages [$i] [static::MESSAGE] = $product->getName () . ' was added to your cart successfully.';
                  $messages [$i] [static::SUCCESS] = 1;
                  $error_flag = true;
               }
            }
            
            $i ++;
            $success = 1;
            $message = 'Cart add and upadte Successfully';
            $quote->collectTotals ();
            $quote->save ();
         }
      } catch ( Mage_Core_Exception $e ) {
         $message = $e->getMessage ();
         $success = 0;
         $error_flag = false;
      }
      
      // get cart product list
      $prodctsList = Mage::getModel ( static::LOGIN_TOKEN )->getCartProducts ( array (
            static::STOREID => $storeId,
            static::CUSTOMER_ID => $customerId 
      ) );
      $response [static::VALID_TOKEN] = $isValidToken;
      $response [static::SUCCESS] = $success;
      $response [static::ERROR] = $error_flag;
      $response [static::MESSAGE] = $message;
      $response [static::RESULT] = $prodctsList [static::RESULT];
      $response [static::RESULT] ['errors'] = $messages;
      
      $this->getResponse ()->setBody ( json_encode ( $response ) );
      return;
   }
   
   /**
    * function that is called when put is done **
    * Update Cart products Qty
    *
    * @param array $data           
    * @return array json array
    */
   protected function _update(array $data) {
      $response = array ();
      $messages = array ();
      $message = '';
      // get quote id from request
      $quoteId = ( int ) $data [static::QUOTE_ID];
      
      // get customer id from request
      $customerId = ( int ) $data [static::CUSTOMER_ID];
      
      // Get website id
      $websiteId = (isset ( $data [static::WEBSITEID] )) ? $data [static::WEBSITEID] : Mage::app ()->getWebsite ( 'base' )->getId ();
      // get store id
      $storeId = (isset ( $data [static::STOREID] )) ? $data [static::STOREID] : Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
      
      $products = $data ['detail'];
      
      /**
       * Check this customer has valid token or not
       *
       * @var $isValidToken Mage_Login_Model_Token
       */
      $isValidToken = Mage::getModel ( static::LOGIN_TOKEN )->checkUserToken ( $customerId, $data [static::TOKEN] );
      try {
         
         if (! $isValidToken) {
            throw new Exception ( static::AUTH_FAIL );
         }
         
         $i = 0;
         $error_flag = true;
         
         foreach ( $products as $item ) {
            $quote = Mage::getModel ( static::SLAES_QUOTE );
            $quote->setStoreId ( $storeId )->load ( $quoteId );
            $productByItem = $this->_getProduct ( $item [static::PRODUCT_ID], $storeId, 'id' );
            
            if (is_null ( $productByItem )) {
               
               $messages [$i] [static::UPDATED] = 0;
               $messages [$i] [static::MESSAGE] = 'Item is not available.';
               $error_flag = false;
            } else {
               $productItem = $this->updateToCart ( $productByItem, $quote, $item [static::QTY], $item );
               $quoteItem = Mage::getModel ( static::LOGIN_TOKEN )->_getQuoteItemByProduct ( $quote, $productByItem, Mage::getModel ( static::LOGIN_TOKEN )->_getProductRequest ( $productItem ) );
               
               $update = $this->cartUpdate ( $quoteItem, $quote, $item [static::QTY] );
               $messages [$i] [static::UPDATED] = $update [static::SUCCESS];
               $messages [$i] [static::MESSAGE] = $productByItem->getName () . '- ' . $update [static::MESSAGE];
            }
            if ($messages [$i] [static::UPDATED] == 0) {
               $error_flag = false;
            }
            
            $i ++;
            $success = 1;
         }
         if ($error_flag) {
            $success = 1;
            $message = 'Cart updated successfully.';
         } else {
            $success = 0;
            $message = 'Cart not updated.';
         }
      } catch ( Mage_Core_Exception $e ) {
         
         $message = $e->getMessage ();
         $success = 0;
      }
      // get cart product list
      $prodctsList = Mage::getModel ( static::LOGIN_TOKEN )->getCartProducts ( array (
            static::STOREID => $storeId,
            static::CUSTOMER_ID => $customerId 
      ) );
      $response [static::ERROR] = false;
      
      $response [static::SUCCESS] = $success;
      $response [static::VALID_TOKEN] = $isValidToken;
      $response [static::MESSAGE] = $message;
      $response [static::RESULT] = $prodctsList [static::RESULT];
      $response [static::RESULT] ['errors'] = $messages;
      $this->getResponse ()->setBody ( json_encode ( $response ) );
      
      return;
   }
   
   /**
    * Get product details
    *
    * @param integer $productId           
    * @param string $store           
    * @param string $identifierType           
    * @return NULL array
    */
   public function _getProduct($productId, $store = NULL, $identifierType = NULL) {
      
      /**
       *
       * @var $product Mage_Catalog_Model_Product
       */
      $product = Mage::helper ( 'catalog/product' )->getProduct ( $productId, $store, $identifierType );
      if (! $product->getId ()) {
         return NULL;
      }
      
      return $product;
   }
   
   /**
    *
    * @param integer $quoteId           
    * @param integer $storeId           
    * @return mixed array $quote
    */
   protected function _getQuote($quoteId, $storeId) {
      /**
       *
       * @var $quote Mage_Sales_Model_Quote
       */
      $quote = Mage::getModel ( static::SLAES_QUOTE );
      $quote->setStoreId ( $storeId )->load ( $quoteId );
      return $quote;
   }
   
   /**
    * Get Quote data by customer
    *
    * @param int $customerId           
    * @param int $storeId           
    * @return array $quoteData
    */
   public function getQuoteIdBycustomer($customerId, $storeId) {
      $quote = Mage::getModel ( static::SLAES_QUOTE )->getCollection ()->addFieldToFilter ( static::CUSTOMER_ID, $customerId )->addFieldToFilter ( static::STOREID, $storeId )->addFieldToFilter ( 'is_active', '1' )->setOrder ( static::ENTITYID, 'desc' );
      return $quote->getData ();
   }
   
   /**
    * Add product to cart
    *
    * @param object $product           
    * @param object $quote           
    * @param int $qty           
    * @return $result
    */
   public function addToCart($product, $quote, $qty, $data) {
      // get product type
      $type = $product->getTypeId ();
      switch ($type) {
         case Mage_Catalog_Model_Product_Type::TYPE_GROUPED :
            $request_add = new Varien_Object ( array (
                  static::SUPER_GRP => $qty 
            ) );
            $cartResult = $quote->addProduct ( $product, $request_add );
            break;
         case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE :
            $request_add = new Varien_Object ( array (
                  static::SUPER_ATTR => $data [static::SUPER_ATTR],
                  static::OPTIONS => $data [static::CUSTOM_OPTION],
                  static::QTY => $qty 
            ) );
            $cartResult = $quote->addProduct ( $product, $request_add );
            break;
         case Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE :
            $request_add = new Varien_Object ( array (
                  static::LINKS => explode ( ',', $data [static::LINKS] ),
                  static::OPTIONS => $data [static::CUSTOM_OPTION],
                  static::QTY => $qty 
            ) );
            $cartResult = $quote->addProduct ( $product, $request_add );
            break;
         default :
            if (isset ( $data [static::CUSTOM_OPTION] )) {
               $qty = new Varien_Object ( array (
                     static::OPTIONS => $data [static::CUSTOM_OPTION],
                     static::QTY => $qty 
               ) );
            }
            $cartResult = $quote->addProduct ( $product, $qty );
            break;
      }
      return $cartResult;
   }
   
   /**
    * Update product to cart
    *
    * @param object $productByItem           
    * @param object $quote           
    * @param int $qty           
    * @return $result
    */
   public function updateToCart($productByItem, $quote, $qty, $data) {
      $type = $productByItem->getTypeId ();
      $updateProduct = '';
      switch ($type) {
         case Mage_Catalog_Model_Product_Type::TYPE_GROUPED :
            $updateProduct = new Varien_Object ( array (
                  static::SUPER_GRP => $qty 
            ) );
            $quote->addProduct ( $product, $request );
            break;
         case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE :
            if (isset ( $data [static::SUPER_ATTR] ) && $data [static::SUPER_ATTR] != '') {
               $updateProduct = new Varien_Object ( array (
                     static::SUPER_ATTR => json_decode ( $data [static::SUPER_ATTR], true ),
                     static::OPTIONS => json_decode ( $data [static::CUSTOM_OPTION], true ),
                     static::QTY => $qty 
               ) );
            }
            break;
         case Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE :
            if (isset ( $data [static::LINKS] ) && $data [static::LINKS] != '') {
               $updateProduct = new Varien_Object ( array (
                     static::LINKS => explode ( ',', $data [static::LINKS] ),
                     static::OPTIONS => json_decode ( $data [static::CUSTOM_OPTION], true ),
                     static::QTY => $qty 
               ) );
            }
            break;
         default :
            if (isset ( $data [static::CUSTOM_OPTION] ) && $data [static::CUSTOM_OPTION] != '') {
               $updateProduct = new Varien_Object ( array (
                     static::OPTIONS => json_decode ( $data [static::CUSTOM_OPTION], true ),
                     static::QTY => $qty 
               ) );
            } else {
               $updateProduct = '';
            }
            break;
      }
      
      return $updateProduct;
   }
   
   /**
    * Update product in cart
    *
    * @param object $quoteItem           
    * @param object $quote           
    * @return array $response
    */
   public function cartUpdate($quoteProduct, $quote, $qty) {
      $response = array ();
      if (empty ( $quoteProduct )) {
         $success = 0;
         $message = 'Item is not added in cart';
      } else {
         if ($qty > 0) {
            $quoteProduct->setQty ( $qty );
         } else {
            $quote->removeItem ( $quoteProduct->getId () );
         }
         $quote->getBillingAddress ();
         $quote->getShippingAddress ()->setCollectShippingRates ( TRUE );
         $quote->collectTotals ();
         $quote->save ();
         $success = 1;
         $message = 'Cart updated successfully.';
      }
      
      $response [static::SUCCESS] = $success;
      $response [static::MESSAGE] = $message;
      return $response;
   }
}