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
class ContusRestapi_Login_Model_Methods_Functions extends Mage_Core_Model_Abstract {
   const SLAES_QUOTE = 'sales/quote';
   const RESULT = 'result';
   const MESSAGE = 'message';
   const DETAILS = 'details';
   const TITLE = 'title';
   const STOREID = 'store_id';
   const SELLER_ID = 'seller_id';
   const STATUS = 'status';
   const COUNTRY = 'country';
   const MARKETPLACE_SELLERREVIEW = 'marketplace/sellerreview';
   const STOCK_QTY = 'stock_qty';
   
   /**
    * Get active shipping methods and payment methods
    *
    * @param int $quoteId           
    * @param int $storeId           
    * @return array $response
    */
   public function getShippingPaymentMethods($quoteId, $storeId) {
      $response = array ();
      $shippings = array ();
      if ($quoteId) {
         try {
            $quote = Mage::getModel ( static::SLAES_QUOTE )->loadByIdWithoutStore ( $quoteId );
            $shippingMethods = Mage::getModel ( 'checkout/cart_shipping_api' )->getShippingMethodsList ( $quoteId, $storeId );
            $i = 0;
            foreach ( $shippingMethods as $shipping ) {
               $shippings [$i] ['carrier'] = $shipping ['carrier'];
               $shippings [$i] ['carrier_title'] = $shipping ['carrier_title'];
               $shippings [$i] ['code'] = $shipping ['code'];
               $shippings [$i] ['method'] = $shipping ['method'];
               $shippings [$i] ['method_description'] = $shipping ['method_description'];
               $shippings [$i] ['price'] = number_format ( $shipping ['price'], 2, '.', '' );
               $shippings [$i] ['error_message'] = $shipping ['error_message'];
               $shippings [$i] ['method_title'] = $shipping ['method_title'];
               $shippings [$i] ['carrierName'] = $shipping ['carrierName'];
               $i ++;
            }
            $payMentMethods = $this->getActivPaymentMethods ( $quote );
            $success = 1;
            $message = "Get shipping and payment methods successfully.";
         } catch ( Exception $e ) {
            $success = 0;
            $message = $e->getMessage ();
         }
      } else {
         $success = 0;
         $message = "Quote id not exist.";
      }
      $response ['success'] = $success;
      $response [static::MESSAGE] = $message;
      $response ['shipping_methods'] = $shippings;
      $response ['payment_methods'] = $payMentMethods;
      return $response;
   }
   
   /**
    * Get active payment methods
    *
    * @param object $quote           
    * @return $result
    */
   public function getActivPaymentMethods($quote) {
      $total = $quote->getBaseSubtotal ();
      $active_methods = Mage::getSingleton ( 'payment/config' )->getActiveMethods ();
      $result = array ();
      foreach ( $active_methods as $_code => $payment_model ) {
         
         if ($_code != 'free' && ($total) > 0) {
            $result [] = $this->getPayments ( $_code, $payment_model );
         } else if ($_code == 'free' && $total <= 0) {
            $_title = Mage::getStoreConfig ( 'payment/' . $_code . '/title' );
            $paymentMethods ['code'] = $_code;
            $paymentMethods [static::TITLE] = $_title;
            $paymentMethods ['ccTypes'] = array ();
            $result [] = $paymentMethods;
         } else {
            // no need this condition
         }
      }
      return $result;
   }
   
   /**
    * Get active payment methods
    *
    * @param object $quote           
    * @return $result
    */
   public function getPayments($_code, $payment_model) {
      $_title = Mage::getStoreConfig ( 'payment/' . $_code . '/title' );
      $pos = strpos ( strtoupper ( $_code ), 'PAYPAL' );
      if ($pos === false) {
         $paymentMethods ['code'] = $_code;
      } else {
         $paymentMethods ['code'] = 'paypal_standard';
      }
      
      $paymentMethods [static::TITLE] = $_title;
      
      $ccTypes = explode ( ',', $payment_model->getConfigData ( 'cctypes' ) );
      $aType = Mage::getSingleton ( 'payment/config' )->getCcTypes ();
      $cc = array ();
      $i = 0;
      foreach ( $ccTypes as $cctype ) {
         if ($cctype != '') {
            $cc [$i] ['card_code'] = $cctype;
            $cc [$i] ['card_name'] = $aType [$cctype];
            
            $i ++;
         }
      }
      $paymentMethods ['ccTypes'] = $cc;
      
      return $paymentMethods;
   }
   
   /**
    * Update device token and type in token table
    *
    * @param int $customerId           
    * @param string $deviceToken           
    * @param string $deviceType           
    */
   public function updateDeviceToken($customerId, $deviceToken, $deviceType) {
      $tokenObj = Mage::getModel ( 'login/token' )->load ( $customerId, 'userid' );
      $tokenObj->setUserid ( $customerId );
      $tokenObj->setDevicetoken ( $deviceToken );
      $tokenObj->setDevicetype ( $deviceType );
      $tokenObj->save ();
   }
   
   /**
    * Function to get the seller profile data
    *
    * Passed the seller id as $sellerId to get particular seller info
    *
    * @param int $sellerId
    *           Return store title of the seller as $StoreTitle
    * @return varchar
    */
   public function sellerdisplay($sellerId, $current, $storeId, $productId) {
      $sellerInfo = array ();
      if ($sellerId > 0) {
         $sellerData = Mage::getModel ( 'marketplace/sellerprofile' )->load ( $sellerId, static::SELLER_ID );
         $sellerInfo [static::SELLER_ID] = $sellerData->getData ( static::SELLER_ID );
         $sellerInfo ['store_title'] = $sellerData->getData ( 'store_title' );
         $sellerInfo ['state'] = $sellerData->getData ( 'state' );
         $sellerInfo [static::COUNTRY] = $sellerData->getData ( static::COUNTRY );
         if ($sellerData->getData ( static::COUNTRY )) {
            $country = Mage::getModel ( 'directory/country' )->loadByCode ( $sellerData->getData ( static::COUNTRY ) );
            // get country name
            $sellerInfo ['country_name'] = $country->getName ();
         }
         $sellerInfo ['contact'] = $sellerData->getData ( 'contact' );
         $sellerInfo ['store_logo'] = Mage::getBaseUrl ( 'media' ) . DS . 'marketplace/resized/' . $sellerData->getData ( 'store_logo' );
         $sellerInfo ['current_seller'] = $current;
         $sellerInfo ['summary_rating'] = strval ( $this->averageRatings ( $sellerId, $storeId ) );
         $sellerInfo ['ratings'] = strval ( $this->getReviewsCount ( $sellerId, $storeId ) );
         $_product = Mage::getModel ( 'catalog/product' )->load ( $productId );
         
         $sellerInfo ['entity_id'] = $productId;
         $sellerInfo ['regular_price_with_tax'] = number_format ( $_product->getPrice (), 2, '.', '' );
         $sellerInfo ['final_price_with_tax'] = number_format ( $_product->getFinalPrice (), 2, '.', '' );
      }
      return $sellerInfo;
   }
   
   /**
    * Get Review Collection of the particular seller
    *
    * Passed the seller id to get the review collection
    *
    * @param int $sellerId
    *           Return the reviews count of particular seller
    * @return int
    */
   public function getReviewsCount($sellerId, $storeId) {
      $reviewsCollection = Mage::getModel ( static::MARKETPLACE_SELLERREVIEW )->getCollection ()->addFieldToFilter ( static::SELLER_ID, $sellerId )->addFieldToFilter ( static::STATUS, 1 )->addFieldToFilter ( static::STOREID, $storeId );
      return $reviewsCollection->getSize ();
   }
   
   /**
    * Calculating average rating for each seller
    *
    * Passed the seller id to get the review collection
    *
    * @param int $sellerId
    *           Return the average rating of particular seller
    * @return int
    */
   public function averageRatings($sellerId, $storeId) {
      /**
       * Review Collection to retrive the ratings of the seller
       */
      $reviews = Mage::getModel ( static::MARKETPLACE_SELLERREVIEW )->getCollection ()->addFieldToFilter ( static::SELLER_ID, $sellerId )->addFieldToFilter ( static::STATUS, 1 )->addFieldToFilter ( static::STOREID, $storeId );
      /**
       * Calculate average ratings
       */
      $ratingsVal = array ();
      $avg = 0;
      if (count ( $reviews ) > 0) {
         foreach ( $reviews as $review ) {
            $ratingsVal [] = $review->getRating ();
         }
         /**
          * Calcualte count of ratings
          */
         $count = count ( $ratingsVal );
         /**
          * Calculate average ratings from count
          */
         $avg = array_sum ( $ratingsVal ) / $count;
      }
      return round ( $avg, 1 );
   }
   
   /**
    * Get Seller reviews and Rating
    *
    * @param int $sellerId           
    * @param int $storeId           
    *
    * @return array $response
    */
   public function getSellerReviews($sellerId, $storeId, $page, $limit) {
      $reviews = array ();
      $response = array ();
      /**
       * Review Collection to retrive the ratings of the seller
       */
      $reviewcoll = Mage::getModel ( static::MARKETPLACE_SELLERREVIEW )->getCollection ()->addFieldToFilter ( static::SELLER_ID, $sellerId )->addFieldToFilter ( static::STATUS, 1 )->addFieldToFilter ( static::STOREID, $storeId );
      $totalReviews = $reviewcoll->getSize ();
      $reviewcoll->setPageSize ( $limit )->setCurPage ( $page );
      if (count ( $reviewcoll ) > 0) {
         $j = 0;
         foreach ( $reviewcoll as $review ) {
            
            $reviews [$j] ['feedback'] = $review->getReview ();
            $reviews [$j] ['product_id'] = $review->getProductId ();
            
            /**
             * load customer info
             */
            $customerInfo = Mage::getModel ( 'customer/customer' )->load ( $review->getCustomerId () );
            // get customer name
            $reviews [$j] ['customer_name'] = $customerInfo->getName ();
            $reviews [$j] ['date'] = date ( "Y-m-d", strtotime ( $review->getCreatedAt () ) );
            $reviews [$j] ['rating'] = $review->getRating ();
            
            $j ++;
         }
      }
      $response ['total_reviews_count'] = $totalReviews;
      $response ['reviews'] = $reviews;
      return $response;
   }
   
   /**
    * Get stock information for product from inventory tab
    *
    * @param int $storeId           
    * @param object $product           
    * @return array $_proudct_data
    */
   public function getinventoryDetail($product, $storeId) {
      $inventoryData = array ();
      // get stock available qty
      $inventory = Mage::getModel ( 'cataloginventory/stock_item' )->loadByProduct ( $product );
      if ($product->getTypeId () != 'configurable') {
         // get product availabile qty
         $inventoryData [static::STOCK_QTY] = floatval ( $inventory->getQty () );
         $minCartQty = $inventory->getMinSaleQty ();
         $inventoryData ['min_sale_qty'] = (isset ( $minCartQty )) ? floatval ( $minCartQty ) : floatval ( 1 );
         
         $maxCartQty = $inventory->getMaxSaleQty ();
         $inventoryData ['max_sale_qty'] = (isset ( $maxCartQty )) ? floatval ( $maxCartQty ) : floatval ( Mage::getStoreConfig ( 'cataloginventory/item_options/max_sale_qty', $storeId ) );
         
         $qtyIncr = $inventory->getQtyIncrements ();
         $enable_qty_increments = Mage::getStoreConfig ( 'cataloginventory/item_options/enable_qty_increments', $storeId );
         if ($qtyIncr == false) {
            if ($enable_qty_increments) {
               $qtyIncr = Mage::getStoreConfig ( 'cataloginventory/item_options/qty_increments', $storeId );
            }
            if ($qtyIncr == 0) {
               $qtyIncr = 1;
            }
         }
         
         $inventoryData ['qty_increments'] = floatval ( $qtyIncr );
         $inventoryData ['is_qty_decimal'] = floatval ( $inventory->getIsQtyDecimal () );
      } else {
         $inventoryData [static::STOCK_QTY] = floatval ( '' );
         $inventoryData ['min_sale_qty'] = floatval ( '' );
         $inventoryData ['max_sale_qty'] = floatval ( '' );
         $inventoryData ['qty_increments'] = floatval ( '' );
         $inventoryData ['is_qty_decimal'] = floatval ( '' );
      }
      
      return $inventoryData;
   }
}