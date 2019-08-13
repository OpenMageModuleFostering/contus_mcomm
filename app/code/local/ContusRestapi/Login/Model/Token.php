<?php
/**
 * Token table model initialization
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
 * @package    ContusRestapi_Login
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
 * @since      1.0
 */
class ContusRestapi_Login_Model_Token extends Mage_Core_Model_Abstract {
   const STOREID = 'store_id';
   const FIRSTNAME = 'firstname';
   const LASTNAME = 'lastname';
   const NEWSLETTER = 'newsletter';
   const ENTITYID = 'entity_id';
   const CUSTOMERID = 'customer_id';
   const EMAIL = 'email';
   const SUCCESS = 'success';
   const TOKEN = 'token';
   const IS_SALABLE = 'is_saleable';
   const IS_STOCK = 'is_stock';
   const RATING = 'rating';
   const PRODUCT_ID = 'product_id';
   const QUOTE_ID = 'quote_id';
   const QTY = 'qty';
   const DISCOUNT = 'discount';
   const SHIPPING_AMT = 'shipping_amount';
   const TAX = 'tax';
   const ITEM_COUNT = 'item_count';
   const ITEMS_QTY = 'items_qty';
   const GRAND_TOTAL = 'grand_total';
   const SUBTOTAL = 'subtotal';
   const COUPON_CODE = 'coupon_code';
   const SLAES_QUOTE = 'sales/quote';
   const IS_ACTIVE = 'is_active';
   const CUSTOMER_CUSTOMER = 'customer/customer';
   const ITEMS_COUNT = 'items_count';
   const NO_ITEM = 'No items in your cart.';
   const STOCK_QTY = 'stock_qty';
   const CATALOG_STOCK = 'cataloginventory/stock_item';
   const RESULT = 'result';
   const MESSAGE = 'message';
   const BILLING = 'billing';
   const SHIPPING = 'shipping';
   const CHECKOUT_CART_QTY = 'checkout/cart_link/use_qty';
   const CONFIGURABLE = 'configurable';
   const MIN_SALE_QTY = 'min_sale_qty';
   const MAX_SALE_QTY = 'max_sale_qty';
   const QTY_INCR = 'qty_increments';
   const IS_QTY_DECIAML = 'is_qty_decimal';
   
   protected function _construct() {
      $this->_init ( 'login/token' );
   }
   
   /**
    * Generate token value for customer
    */
   public function getRandomString($length = 6) {
      $validCharacters = "abcdefghijklmnopqrstuxyvwzABCDEFGHIJKLMNOPQRSTUXYVWZ123456789";
      $validCharNumber = strlen ( $validCharacters );
      $result = "";
      for($i = 0; $i < $length; $i ++) {
         $index = mt_rand ( 0, $validCharNumber - 1 );
         $result .= $validCharacters [$index];
      }
      $tokobj = Mage::getModel ( 'login/token' )->load ( $result, 'token' );
      if ($tokobj->getTokenid ()) {
         $this->getRandomString ( 6 );
      }
      return $result;
   }
   
   /**
    * Check Customer has vaild token or not
    *
    * @param int $userid           
    * @param string $token           
    * @return boolean
    */
   public function checkUserToken($userid, $token) {
      $tokenObj = $this->load ( $userid, 'userid' );
      $value = TRUE;
      if ($tokenObj->getToken () == $token && trim ( $token ) != '') {
         $value = TRUE;
      } else {
         $value = FALSE;
      }
      return $value;
   }
   
   /**
    * Get Customer Details by id
    *
    * @param int $customerId           
    * @return array $response
    */
   public function getCustomerDetail($customerId) {
      $response = array ();
      
      /**
       *
       * @var $customer Mage_Customer_Model_Customer
       */
      $customerData = Mage::getModel ( static::CUSTOMER_CUSTOMER )->load ( $customerId )->getData ();
      $customerID = $customerData [static::ENTITYID];
      if (isset ( $customerID )) {
         $response [static::SUCCESS] = 1;
         $response [static::CUSTOMERID] = $customerData [static::ENTITYID];
         // get customer name
         $response [static::FIRSTNAME] = $customerData [static::FIRSTNAME];
         $response [static::LASTNAME] = $customerData [static::LASTNAME];
         // get customer email
         $response [static::EMAIL] = $customerData [static::EMAIL];
         $subscriber = Mage::getModel ( 'newsletter/subscriber' )->loadByEmail ( $customerData [static::EMAIL] );
         if ($subscriber->getId () && $subscriber->getStatus () == 1) {
            $response [static::NEWSLETTER] = 1;
         } else {
            $response [static::NEWSLETTER] = 0;
         }
         
         // get customer dob yyyy-mm-dd hh:mm:ss
         $response ['dob'] = $customerData ['dob'];
         $response [static::STOREID] = $customerData [static::STOREID];
      } else {
         $response [static::SUCCESS] = 0;
      }
      
      return $response;
   }
   
   /**
    * Get product count in cart by customer
    *
    * @param int $customerId           
    * @param int $storeId           
    * @return number $itemCount
    */
   public function getCartCount($customerId, $storeId) {
      $quote = Mage::getModel ( static::SLAES_QUOTE )->getCollection ()->addFieldToFilter ( static::CUSTOMERID, $customerId )->addFieldToFilter ( static::STOREID, $storeId )->addFieldToFilter ( static::IS_ACTIVE, "1" )->setOrder ( static::ENTITYID, 'desc' );
      $quoteData = $quote->getData ();
      $itemCount = '0';
      $cartLink = Mage::getStoreConfig ( static::CHECKOUT_CART_QTY, $storeId );
      if (isset ( $quoteData )) {
         if ($cartLink) {
            $itemCount = strval ( floatval ( $quoteData [0] ['items_qty'] ) );
         } else {
            $itemCount = strval ( $quoteData [0] ['items_count'] );
         }
      }
      return $itemCount;
   }
   
   /**
    * Customer Wishlist collection
    *
    * @param int $customerId           
    *
    * @return array $wishListIds
    */
   public function getWishlistByCustomer($customerId) {
      $wishListIds = array ();
      
      /**
       *
       * @var $customer Mage_Customer_Model_Customer
       */
      $customerModel = Mage::getModel ( static::CUSTOMER_CUSTOMER );
      $customerModel->load ( $customerId );
      if (! empty ( $customerModel [static::EMAIL] ) && $customerModel [static::EMAIL] != "") {
         $wishList = Mage::getSingleton ( 'wishlist/wishlist' )->loadByCustomer ( $customerId, true );
         $wishListItemCollection = $wishList->getItemCollection ();
         if (count ( $wishListItemCollection ) > 0) {
            foreach ( $wishListItemCollection as $wishitem ) {
               $wishListIds [] = $wishitem->getProductId ();
            }
         }
      }
      return $wishListIds;
   }
   
   /**
    * Products Rating
    *
    * @param int $productId
    *           Getting the particular product id
    * @param int $storeId           
    * @return array as json message as string and success rate count
    */
   public function rateSummary($productId, $storeId) {
      // getting rate model
      $summaryData = Mage::getModel ( 'review/review_summary' )->setStoreId ( $storeId )->load ( $productId )->getRatingSummary ();
      // calculate overage for ratings
      return strval ( $summaryData / 20 );
   }
   
   /**
    * Get product reviews and Rating
    *
    * @param int $productId           
    * @param int $storeId           
    *
    * @return array $response
    */
   public function getReviews($productId, $storeId, $page, $limit) {
      // get only five revieews in product detail page
      $reviewcoll = Mage::getModel ( 'review/review' )->getResourceCollection ()->addStoreFilter ( $storeId )->addEntityFilter ( 'product', $productId )->addStatusFilter ( Mage_Review_Model_Review::STATUS_APPROVED )->setDateOrder ( 'desc' )->setPageSize ( $limit )->setCurPage ( $page )->addRateVotes ();
      
      $response = array ();
      $reviews = array ();
      
      if (count ( $reviewcoll ) > 0) {
         $j = 0;
         // get all reviews collection
         foreach ( $reviewcoll->getItems () as $review ) {
            $reviews [$j] ['title'] = $review->getTitle ();
            $reviews [$j] ['detail'] = $review->getDetail ();
            $reviews [$j] ['author'] = $review->getNickname ();
            $reviews [$j] ['date'] = date ( "Y-m-d", strtotime ( $review->getCreatedAt () ) );
            
            $rateAvg = 0;
            
            foreach ( $review->getRatingVotes () as $vote ) {
               $rateAvg += $vote->getPercent ();
            }
            // get summary rating
            $reviews [$j] [static::RATING] = floor ( $rateAvg / 60 );
            
            $j ++;
         }
      }
      
      $response ['reviews'] = $reviews;
      return $response;
   }
   
   /**
    * Get product reviews and Rating
    *
    * @param int $productId           
    * @param int $storeId           
    *
    * @return array $response
    */
   public function getRatingResult($productId, $storeId) {
      $reviewcoll = Mage::getModel ( 'review/review' )->getResourceCollection ()->addStoreFilter ( $storeId )->addEntityFilter ( 'product', $productId )->addStatusFilter ( Mage_Review_Model_Review::STATUS_APPROVED )->setDateOrder ()->addRateVotes ();
      $response = array ();
      
      $rate = array ();
      if (count ( $reviewcoll ) > 0) {
         $j = 0;
         // get all reviews collection
         foreach ( $reviewcoll->getItems () as $review ) {
            $rateAvg = 0;
            foreach ( $review->getRatingVotes () as $vote ) {
               $rateAvg += $vote->getPercent ();
            }
            // get summary rating
            $rating [$j] = trim ( floor ( $rateAvg / 60 ) );
            $j ++;
         }
         // get rating count summary
         $rating = array_count_values ( $rating );
         for($k = 1; $k <= 5; $k ++) {
            $ratingCount ['star'] = $k;
            $ratingCount ['count'] = ($rating [$k] > 0 ? $rating [$k] : 0);
            $rate [] = $ratingCount;
         }
      }
      
      $response [static::RATING] = $rate;
      return $response;
   }
   
   /**
    * Get stock detail for product
    *
    * @param object $product           
    * @return array $_proudct_data
    */
   public function getStockDetail($product) {
      $_proudct_data = array ();
      
      /**
       * get stock details for product
       *
       * @var $stockItem Mage_CatalogInventory_Model_Stock_Item
       */
      $stockItem = Mage::getModel ( static::CATALOG_STOCK )->loadByProduct ( $product->getId () );
      if ($product->getTypeId () != static::CONFIGURABLE) {
         $_proudct_data [static::IS_SALABLE] = ($product->getIsSalable () > 0);
         $_proudct_data [static::IS_STOCK] = ($stockItem->getIsInStock () > 0);
      }
      /**
       * get stock details for product
       *
       * @var $stockItem Mage_CatalogInventory_Model_Stock_Item
       */
      $instock_childrenisinstock = false;
      
      if ($product->getTypeId () == static::CONFIGURABLE) {
         
         /**
          * Get children products (all associated children products data)
          */
         $childProducts = Mage::getModel ( 'catalog/product_type_configurable' )->getUsedProducts ( null, $product );
         
         $stockItem = $product->getStockItem ();
         
         // get associated product stock detail
         $instock_childrenisinstock = $this->getChildStock ( $stockItem, $childProducts );
         
         if (! $instock_childrenisinstock) {
            $_proudct_data [static::IS_SALABLE] = false;
            $_proudct_data [static::IS_STOCK] = false;
         } else {
            $_proudct_data [static::IS_SALABLE] = true;
            $_proudct_data [static::IS_STOCK] = true;
         }
      }
      
      return $_proudct_data;
   }
   
   /**
    * Get associated product stock
    *
    * @param object $stockItem           
    * @param array $childProducts           
    * @return boolean true| false
    */
   public function getChildStock($stockItem, $childProducts) {
      $instock_childrenisinstock = false;
      if ($stockItem->getIsInStock ()) {
         /**
          * All configurable products, which are in stock
          */
         
         foreach ( $childProducts as $childProduct ) {
            $child_stockItem = $childProduct->getStockItem ();
            Mage::getModel ( static::CATALOG_STOCK )->loadByProduct ( $childProduct );
            if ($child_stockItem->getIsInStock ()) {
               $instock_childrenisinstock = true;
            }
         }
      }
      return $instock_childrenisinstock;
   }
   
   /**
    * Get Cart Amount Details
    *
    * @param array $data           
    * @return array $response
    */
   public function getCartTotal($data) {
      // get customer id
      $customerId = $data [static::CUSTOMERID];
      // get category id
      $storeId = $data [static::STOREID];
      $quoteData = $this->getQuoteIdBycustomer ( $customerId, $storeId );
      $quoteId = $quoteData [0] [static::ENTITYID];
      $cartTotal = array ();
      $quote = $this->_getQuote ( $quoteId, $storeId );
      $totals = $quote->getTotals ();
      if (isset ( $totals [static::DISCOUNT] )) {
         $discount = number_format ( abs ( $totals [static::DISCOUNT]->getValue () ), 2, '.', '' );
      } else {
         $discount = '';
      }
      // get system/configuration -checkout - cart liks value
      $cartLink = Mage::getStoreConfig ( static::CHECKOUT_CART_QTY, $storeId );
      if (isset ( $quoteData ) && ! empty ( $quoteData )) {
         
         if ($cartLink) {
            // Display Cart Summary - Display item quantities
            $itemCount = strval ( floatval ( $quoteData [0] [static::ITEMS_QTY] ) );
         } else {
            // Display Cart Summary - Display no.of items in cart
            $itemCount = strval ( $quoteData [0] [static::ITEMS_COUNT] );
         }
         
         $cartTotal [static::ITEM_COUNT] = $itemCount;
         $cartTotal [static::GRAND_TOTAL] = number_format ( $quoteData [0] [static::GRAND_TOTAL], 2, '.', '' );
         $cartTotal [static::SUBTOTAL] = number_format ( $quoteData [0] [static::SUBTOTAL], 2, '.', '' );
         $cartTotal [static::DISCOUNT] = $discount;
         $cartTotal [static::QUOTE_ID] = $quoteId;
         $cartTotal [static::COUPON_CODE] = $quoteData [0] [static::COUPON_CODE];
         $addressobj = $quote->getShippingAddress ();
         $cartTotal [static::SHIPPING_AMT] = 0;
         $cartTotal [static::TAX] = 0;
         if ($addressobj) {
            $cartTotal [static::SHIPPING_AMT] = $quote->getShippingAddress ()->getData ( static::SHIPPING_AMT );
            $cartTotal [static::TAX] = $quote->getShippingAddress ()->getData ( 'tax_amount' );
            $cartTotal [static::SHIPPING_AMT] = number_format ( $cartTotal [static::SHIPPING_AMT], 2, '.', '' );
            $cartTotal [static::TAX] = number_format ( $cartTotal ['tax'], 2, '.', '' );
         }
      } else {
         
         $message = 'No items in your cart.';
      }
      $response [static::ITEM_COUNT] = $itemCount;
      $response ['result'] = $cartTotal;
      $response [static::SUCCESS] = false;
      $response [static::MESSAGE] = $message;
      return $response;
   }
   
   /**
    * Get Quote data by customer
    *
    * @param int $customerId           
    * @param int $storeId           
    * @return array $quoteData
    */
   public function getQuoteIdBycustomer($customerId, $storeId) {
      $quote = Mage::getModel ( static::SLAES_QUOTE )->getCollection ()->addFieldToFilter ( static::CUSTOMERID, $customerId )->addFieldToFilter ( static::STOREID, $storeId )->addFieldToFilter ( static::IS_ACTIVE, '1' )->setOrder ( static::ENTITYID, 'desc' );
      return $quote->getData ();
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
    * Get cart quote by customer
    *
    * @param int $customerId           
    * @param in $storeId           
    * @return object $$quote
    */
   public function setSaleQuoteByCustomer($customerId, $storeId, $currencyCode) {
      /**
       *
       * @var $quote Mage_Sales_Model_Quote
       */
      $quote = Mage::getModel ( static::SLAES_QUOTE )->getCollection ()->addFieldToFilter ( static::CUSTOMERID, $customerId )->addFieldToFilter ( static::STOREID, $storeId )->addFieldToFilter ( static::IS_ACTIVE, '1' )->setOrder ( static::ENTITYID, 'desc' );
      $quoteData = $quote->getData ();
      
      // get base currency code
      $baseCurrency = Mage::app ()->getBaseCurrencyCode ();
      // get default currency code
      $defaultCurrency = Mage::app ()->getStore ( $storeId )->getDefaultCurrencyCode ();
      if (! $currencyCode) {
         $currencyCode = $defaultCurrency;
      }
      // get all allowed currnecy codess
      $allowedCurrencies = Mage::getModel ( 'directory/currency' )->getConfigAllowCurrencies ();
      // get base currency rate
      $baseCurrencyRates = Mage::getModel ( 'directory/currency' )->getCurrencyRates ( $baseCurrency, array_values ( $allowedCurrencies ) );
      Mage::log ( 'base currency:' . $baseCurrency );
      Mage::log ( ' currency:' . $currencyCode );
      Mage::log ( ' StoreToBaseRate:' . $baseCurrencyRates [$baseCurrency] );
      Mage::log ( ' StoreToQuoteRate:' . $baseCurrencyRates [$currencyCode] );
      Mage::log ( ' BaseToGlobalRate:' . $baseCurrencyRates [$baseCurrency] );
      Mage::log ( ' BaseToQuoteRate:' . $baseCurrencyRates [$currencyCode] );
      
      if (empty ( $quoteData )) {
         $quote = Mage::getModel ( static::SLAES_QUOTE );
         $customer = Mage::getModel ( static::CUSTOMER_CUSTOMER )->load ( $customerId );
         // Check this customer id is in record
         if ($customer->getId ()) {
            $quote->assignCustomer ( $customer );
            $quote->setStore_id ( $storeId );
            
            $quote->setGlobalCurrencyCode ( $currencyCode );
            $quote->setBaseCurrencyCode ( $baseCurrency );
            $quote->setStoreCurrencyCode ( $baseCurrency );
            $quote->setQuoteCurrencyCode ( $currencyCode );
            
            $quote->setStoreToBaseRate ( $baseCurrencyRates [$baseCurrency] );
            $quote->setStoreToQuoteRate ( $baseCurrencyRates [$currencyCode] );
            
            $quote->setBaseToGlobalRate ( $baseCurrencyRates [$baseCurrency] );
            $quote->setBaseToQuoteRate ( $baseCurrencyRates [$currencyCode] );
            
            $quote->setCustomerId ( $customerId )->setUpdatedAt ( now () )->setCreatedAt ( now () );
            $quote->setCustomerEmail ( $customer->getEmail () )->setCustomerFirstname ( $customer->getFirstname () )->setCustomerLastname ( $customer->getLastname () )->setCustomerIsGuest ( 0 )->setCustomer ( $customer );
            $quote->save ();
         } else {
            $quote = 0;
         }
      } else {
         $quote = Mage::getModel ( static::SLAES_QUOTE );
         $quote->setStoreId ( $storeId )->load ( $quoteData [0] [static::ENTITYID] );
         $quote->setGlobalCurrencyCode ( $currencyCode );
         $quote->setBaseCurrencyCode ( $baseCurrency );
         $quote->setStoreCurrencyCode ( $baseCurrency );
         $quote->setQuoteCurrencyCode ( $currencyCode );
         
         $quote->setStoreToBaseRate ( $baseCurrencyRates );
         $quote->setStoreToQuoteRate ( $baseCurrencyRates [$currencyCode] );
         
         $quote->setBaseToGlobalRate ( $baseCurrencyRates );
         $quote->setBaseToQuoteRate ( $baseCurrencyRates [$currencyCode] );
         $quote->setUpdatedAt ( now () );
      }
      return $quote;
   }
   
   /**
    *
    * @param Mage_Sales_Model_Quote $quote           
    * @param Mage_Catalog_Model_Product $product           
    * @param Varien_Object $requestInfo           
    * @return Varien_Object
    */
   public function _getQuoteItemByProduct(Mage_Sales_Model_Quote $quote, Mage_Catalog_Model_Product $product, Varien_Object $requestInfo) {
      $cartCandidates = $product->getTypeInstance ( TRUE )->prepareForCartAdvanced ( $requestInfo, $product, Mage_Catalog_Model_Product_Type_Abstract::PROCESS_MODE_FULL );
      /**
       * Error message
       */
      if (is_string ( $cartCandidates )) {
         throw Mage::throwException ( $cartCandidates );
      }
      
      /**
       * If prepare process return one object
       */
      if (! is_array ( $cartCandidates )) {
         $cartCandidates = array (
               $cartCandidates 
         );
      }
      
      /**
       *
       * @var $item Mage_Sales_Model_Quote_Item
       */
      $item = NULL;
      foreach ( $cartCandidates as $candidate ) {
         if ($candidate->getParentProductId ()) {
            Mage::log ( 'productID' . $candidate->getParentProductId () );
            continue;
         }
         
         $item = $quote->getItemByProduct ( $candidate );
      }
      
      if (is_null ( $item )) {
         $item = Mage::getModel ( 'sales/quote_item' );
      }
      
      return $item;
   }
   
   /**
    *
    * @param array $requestInfo           
    * @return Varien_Object
    */
   public function _getProductRequest($requestInfo) {
      if ($requestInfo instanceof Varien_Object) {
         $request = $requestInfo;
      } elseif (is_numeric ( $requestInfo )) {
         Mage::log ( 'else in product request' );
         $request = new Varien_Object ();
         $request->setQty ( $requestInfo );
      } else {
         $request = new Varien_Object ( $requestInfo );
      }
      
      if (! $request->hasQty ()) {
         $request->setQty ( 1 );
      }
      
      return $request;
   }
   
   /**
    * Get Cart product details
    *
    * @param array $data           
    * @return array $response
    */
   public function getCartProducts($data) {
      
      // get category id
      $storeId = $data [static::STOREID];
      // get customer id
      $customerId = $data [static::CUSTOMERID];
      
      $quoteData = $this->getQuoteIdBycustomer ( $customerId, $storeId );
      $quoteId = $quoteData [0] [static::ENTITYID];
      
      $quote = $this->_getQuote ( $quoteId, $storeId );
      $totals = $quote->getTotals ();
      if (isset ( $totals [static::DISCOUNT] )) {
         $discount = number_format ( abs ( $totals [static::DISCOUNT]->getValue () ), 2, '.', '' );
      } else {
         $discount = '';
      }
      // get system/configuration -checkout - cart liks value
      $cartLink = Mage::getStoreConfig ( static::CHECKOUT_CART_QTY, $storeId );
      $returnArray = array ();
      $returnArray ['items'] = array ();
      $items = array ();
      if (isset ( $quoteData ) && ! empty ( $quoteData )) {
         
         $itemCount = $quoteData [0] [static::ITEMS_COUNT];
         if ($itemCount > 0) {
            if ($cartLink) {
               // Display Cart Summary - Display item quantities
               $itemCount = strval ( floatval ( $quoteData [0] [static::ITEMS_QTY] ) );
            } else {
               // Display Cart Summary - Display no.of items in cart
               $itemCount = strval ( $quoteData [0] [static::ITEMS_COUNT] );
            }
            $returnArray [static::ITEM_COUNT] = $itemCount;
            $returnArray [static::SHIPPING_AMT] = 0;
            $returnArray [static::TAX] = 0;
            $returnArray [static::GRAND_TOTAL] = number_format ( $quoteData [0] [static::GRAND_TOTAL], 2, '.', '' );
            $returnArray [static::SUBTOTAL] = number_format ( $quoteData [0] [static::SUBTOTAL], 2, '.', '' );
            $returnArray [static::DISCOUNT] = $discount;
            $returnArray [static::QUOTE_ID] = $quoteId;
            $returnArray [static::COUPON_CODE] = $quoteData [0] [static::COUPON_CODE];
            $addressobj = $quote->getShippingAddress ();
            
            if ($addressobj) {
               $returnArray [static::SHIPPING_AMT] = $quote->getShippingAddress ()->getData ( static::SHIPPING_AMT );
               $returnArray [static::TAX] = $quote->getShippingAddress ()->getData ( 'tax_amount' );
               $returnArray [static::SHIPPING_AMT] = number_format ( $returnArray [static::SHIPPING_AMT], 2, '.', '' );
               $returnArray [static::TAX] = number_format ( $returnArray ['tax'], 2, '.', '' );
            }
            
            $i = 0;
            foreach ( $quote->getAllVisibleItems () as $item ) {
               $product = $item->getProduct ();
               $product->setStoreId ( $storeId )->load ( $product->getId () );
               $items [$i] ['type_id'] = $item->getProductType ();
               $items [$i] ['item_id_test'] = $item->getItemId ();
               // get product id
               $items [$i] [static::ENTITYID] = $product->getEntityId ();
               // get product name
               $items [$i] ['name'] = $product->getName ();
               // get product image
               $items [$i] ['image_url'] = ( string ) Mage::helper ( 'catalog/image' )->init ( $product, 'small_image' );
               // get product price
               $items [$i] ['price'] = number_format ( $item->getPrice (), 2, '.', '' );
               // get product qty
               $items [$i] [static::QTY] = $item->getQty ();
               // get product row total
               $items [$i] ['row_total'] = number_format ( $item->getRowTotal (), 2, '.', '' );
               
               $stockDetail = $this->getProductStock ( $product, $item );
               $items [$i] [static::STOCK_QTY] = $stockDetail [static::STOCK_QTY];
               $items [$i] [static::IS_STOCK] = $stockDetail [static::IS_STOCK];
               $items [$i] [static::MIN_SALE_QTY] = $stockDetail [static::MIN_SALE_QTY];
               $items [$i] [static::MAX_SALE_QTY] = $stockDetail [static::MAX_SALE_QTY];
               $items [$i] [static::QTY_INCR] = $stockDetail [static::QTY_INCR];
               $items [$i] [static::IS_QTY_DECIAML] = $stockDetail [static::IS_QTY_DECIAML];
               $items [$i] ['config'] = Mage::getModel ( 'catalog/product_type_configurable' )->getOrderOptions ( $product );
               $i ++;
            }
            
            $returnArray ['items'] = $items;
            $returnArray ['address'] = $this->getAddressByCustomer ( $customerId );
            $error = false;
            $message = 'Quote fetched successfully.';
         } else {
            $error = false;
            $message = static::NO_ITEM;
         }
      } else {
         $error = true;
         $message = static::NO_ITEM;
      }
      $response [static::ITEM_COUNT] = $itemCount;
      $response [static::RESULT] = $returnArray;
      $response [static::SUCCESS] = $error;
      $response [static::MESSAGE] = $message;
      return $response;
   }
   
   /**
    * Get product stock details
    *
    * @param object $product           
    * @param object $item           
    * @return array $items
    */
   public function getProductStock($product, $item) {
      $items = array ();
      if ($product->getTypeId () != static::CONFIGURABLE) {
         // get product availabile qty
         $items [static::STOCK_QTY] = floatval ( Mage::getModel ( static::CATALOG_STOCK )->loadByProduct ( $product )->getQty () );
         $stockItem = $product->getStockItem ();
         if (! $stockItem) {
            $stockItem = Mage::getModel ( static::CATALOG_STOCK );
            $stockItem->loadByProduct ( $product );
         }
         // get product stock status
         $items [static::IS_STOCK] = ($stockItem->getIsInStock () > 0);
         
         // get product stock details
         $inventoryDetail = Mage::getModel ( 'login/methods_functions' )->getinventoryDetail ( $product, $storeId );
      } else {
         
         $asso_productId = intval ( preg_replace ( '/[^0-9]+/', '', json_encode ( $item->getQtyOptions () ) ), 10 );
         // get simple product stock status
         $stockLevel = Mage::getModel ( static::CATALOG_STOCK )->loadByProduct ( $asso_productId );
         $items [static::STOCK_QTY] = floatval ( $stockLevel->getQty () );
         // get stock details for product
         // get config product stock status
         $stockItem = Mage::getModel ( static::CATALOG_STOCK )->loadByProduct ( $product->getId () );
         if ($stockLevel->getIsInStock () == 1 && $stockItem->getIsInStock () == 1) {
            $items [static::IS_STOCK] = true;
         } else {
            $items [static::IS_STOCK] = false;
         }
         //load product by associate product id
         $simpleProduct = Mage::getModel ( 'catalog/product' )->load ( $asso_productId );
         // get product stock details
         $inventoryDetail = Mage::getModel ( 'login/methods_functions' )->getinventoryDetail ( $simpleProduct, $storeId );
      }
      //get min sale qty
      $items [static::MIN_SALE_QTY] = $inventoryDetail [static::MIN_SALE_QTY];
      //get max sale qty
      $items [static::MAX_SALE_QTY] = $inventoryDetail [static::MAX_SALE_QTY];
      //get qty incremnet qty in cart
      $items [static::QTY_INCR] = $inventoryDetail [static::QTY_INCR];
      $items [static::IS_QTY_DECIAML] = $inventoryDetail [static::IS_QTY_DECIAML];
      return $items;
   }
   /**
    * Get customer default billing and shipping address
    *
    * @param int $customerId           
    */
   public function getAddressByCustomer($customerId) {
      $address = array ();
      $customerObj = Mage::getModel ( 'customer/customer' )->load ( $customerId );
      
      // get customer default billing address
      $billingAddress = $customerObj->getDefaultBillingAddress ();
      if ($billingAddress) {
         $country = Mage::getModel ( 'directory/country' )->loadByCode ( $billingAddress->country_id );
         $billingAddress->setData ( 'country_name', $country->getName () );
         $address [static::BILLING] = $billingAddress->getData ();
         $address [static::BILLING] ['street'] = $billingAddress->getStreet ();
         $address [static::BILLING] ['region_code'] = $billingAddress->getRegionCode ();
         $address ['is_billing'] = true;
      } else {
         $address ['is_billing'] = false;
      }
      // get customer default shipping address
      $shippingAddress = $customerObj->getDefaultShippingAddress ();
      if ($shippingAddress) {
         $country = Mage::getModel ( 'directory/country' )->loadByCode ( $shippingAddress->country_id );
         $shippingAddress->setData ( 'country_name', $country->getName () );
         $address [static::SHIPPING] = $shippingAddress->getData ();
         $address [static::SHIPPING] ['street'] = $shippingAddress->getStreet ();
         $address [static::SHIPPING] ['region_code'] = $shippingAddress->getRegionCode ();
         $address ['is_shipping'] = true;
      } else {
         $address ['is_shipping'] = false;
      }
      
      return $address;
   }
}
