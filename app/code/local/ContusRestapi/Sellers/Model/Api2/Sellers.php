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
 * @package    ContusRestapi_Sellers
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
 * @since      1.0
 */
class ContusRestapi_Sellers_Model_Api2_Sellers extends Mage_Api2_Model_Resource {
   
   /**
    * Define staic variable
    */
   const STOREID = 'store_id';
   const WEBSITEID = 'website_id';
   const CUSTOMER_ID = 'customer_id';
   const SUCCESS = 'success';
   const MESSAGE = 'message';
   const ERROR = 'error';
   const PAGE = 'page';
   const LIMIT = 'limit';
   const SELLER_ID = 'seller_id';
   const CUSTOMER_CUSTOMER = 'customer/customer';
   const CORE_EMAIL_TEMP = 'core/email_template';
   const ENTITYID = 'entity_id';
   const LOGIN_METHODS = 'login/methods_functions';
   const SMALLIMG = 'small_image';
   const RATINGS = 'ratings';
   
   /**
    * Add rating points to seller
    *
    * @param array $data           
    * @return array json array
    */
   protected function _create(array $data) {
      $response = array ();
      
      try {
         
         $saveReview = $this->saveReview ( $data );
         if ($saveReview == 1) {
            $needAdmin = Mage::getStoreConfig ( 'marketplace/seller_review/need_approval' );
            if ($needAdmin == 1) {
               $message = 'Your review has been accepted for moderation.';
            } else {
               $message = 'Your review has been successfully posted.';
            }
            $success = 1;
         }
      } catch ( Mage_Core_Exception $e ) {
         $message = $e->getMessage ();
         $success = 0;
      } catch ( Exception $e ) {
         $message = $e->getMessage ();
         $success = 0;
      }
      $response [static::ERROR] = false;
      $response [static::SUCCESS] = $success;
      $response [static::MESSAGE] = $message;
      $response [static::RATINGS] = strval ( Mage::getModel ( static::LOGIN_METHODS )->averageRatings ( $data [static::SELLER_ID], $data [static::STOREID] ) );
      $response ['summary_rating'] = strval ( Mage::getModel ( static::LOGIN_METHODS )->getReviewsCount ( $data [static::SELLER_ID], $data [static::STOREID] ) );
      
      $this->getResponse ()->setBody ( json_encode ( $response ) );
      return;
   }
   
   /**
    * Retrieve Sellers List collection
    *
    * @param integer $customerId           
    * @return array json array
    */
   protected function _retrieveCollection() {
      $response = array ();
      
      // get page from request
      $page = $this->getRequest ()->getParam ( 'page' );
      if ($page <= 0) {
         $page = 1;
      }
      // get page from request
      $limit = $this->getRequest ()->getParam ( static::LIMIT );
      if ($limit <= 0) {
         $limit = 10;
      }
      /**
       * Get website id from request
       */
      $websiteId = ( int ) $this->getRequest ()->getParam ( static::WEBSITEID );
      if ($websiteId <= 0) {
         $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
      }
      
      /**
       * Get store id
       */
      $storeId = ( int ) $this->getRequest ()->getParam ( static::STOREID );
      if ($storeId <= 0) {
         $storeId = Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
      }
      
      /**
       * Get the sellers List
       */
      $sellerList = Mage::getModel ( static::CUSTOMER_CUSTOMER )->getCollection ()->addFieldToFilter ( 'group_id', 6 );
      
      /**
       * Set pagination
       */
      $sellerList->setPage ( $page, $limit );
      $sellerList->setOrder ( 'name', 'asc' );
      /**
       * Get total products count
       */
      $totalSellers = $sellerList->getSize ();
      /**
       * Get total pages with limit
       */
      $last_page = ceil ( $totalSellers / $limit );
      if ($last_page < $page) {
         $sellers = array ();
      } else {
         $i = 0;
         foreach ( $sellerList->getData () as $_collection ) {
            $sellerId = $_collection [static::ENTITYID];
            
            $sellers [$i] = Mage::getModel ( static::LOGIN_METHODS )->sellerdisplay ( $sellerId, '0', $storeId, '' );
            
            $i ++;
         }
      }
      
      $response [static::ERROR] = false;
      $response [static::SUCCESS] = 1;
      $response ['totalCount'] = $totalSellers;
      $response [static::MESSAGE] = 'Get sellers information.';
      $response ['result'] = $sellers;
      
      return json_encode ( $response );
   }
   
   /**
    * Retrieve Seller profile collection
    *
    * @param integer $customerId           
    * @return array json array
    */
   protected function _retrieve() {
      $response = array ();
      
      /**
       * Get website id from request
       */
      $websiteId = ( int ) $this->getRequest ()->getParam ( static::WEBSITEID );
      if ($websiteId <= 0) {
         $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
      }
      
      /**
       * Get store id from request
       */
      $storeId = ( int ) Mage::app ()->getRequest ()->getParam ( static::STOREID );
      if ($storeId <= 0) {
         $storeId = Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
      }
      /**
       * Get page from request
       */
      $page = ( int ) $this->getRequest ()->getParam ( 'page' );
      if ($page <= 0) {
         $page = 1;
      }
      /**
       * Get page from request
       */
      $limit = ( int ) $this->getRequest ()->getParam ( static::LIMIT );
      if ($limit <= 0) {
         $limit = 10;
      }
      
      /**
       * Get customer id from request
       */
      $sellerId = ( int ) $this->getRequest ()->getParam ( static::SELLER_ID );
      
      /**
       * Get the seller data
       */
      $response ['seller_info'] = $this->sellerdisplay ( $sellerId, $storeId );
      
      /**
       * Get seller Product
       */
      $sellerproduct = Mage::getModel ( 'catalog/product' )->setStoreId ( $storeId )->getCollection ()->addFieldToFilter ( static::SELLER_ID, $sellerId );
      $sellerproduct->addAttributeToFilter ( 'status', array (
            'eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED 
      ) );
      $sellerproduct->addAttributeToFilter ( 'visibility', array (
            'neq' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE 
      ) );
      
      // Get total products count
      $totalProducts = $sellerproduct->getSize ();
      $sellerproduct->setPage ( $page, $limit );
      $products = $sellerproduct->load ();
      
      /**
       * Get total pages with limit
       */
      $last_page = ceil ( $totalProducts / $limit );
      if ($last_page < $page) {
         $response ['seller_product'] = array ();
      } else {
         $response ['seller_product'] = $this->sellerproduct ( $products, $storeId );
      }
      
      // Getting ratings/reviews
      // get only five reviews in seller info page
      $reviews = Mage::getModel ( static::LOGIN_METHODS )->getSellerReviews ( $sellerId, $storeId, 1, 5 );
      $response ['reviews'] = $reviews ['reviews'];
      $response ['total_reviews_count'] = $reviews ['total_reviews_count'];
      
      $response [static::ERROR] = false;
      $response [static::SUCCESS] = 1;
      $response ['total_count'] = $totalProducts;
      
      return json_encode ( $response );
   }
   function saveReview($data) {
      $needAdmin = Mage::getStoreConfig ( 'marketplace/seller_review/need_approval' );
      if ($data) {
         
         $collection = Mage::getModel ( 'marketplace/sellerreview' );
         $collection->setSellerId ( $data [static::SELLER_ID] );
         $collection->setProductId ( $data ['product_id'] );
         $collection->setCustomerId ( $data [static::CUSTOMER_ID] );
         $collection->setRating ( $data [static::RATINGS] );
         $collection->setReview ( $data ['feedback'] );
         $collection->setStoreId ( $data [static::STOREID] );
         if ($needAdmin == 1) {
            $collection->setStatus ( 0 );
         } else {
            $collection->setStatus ( 1 );
         }
         $collection->save ();
         if ($needAdmin == 1) {
            $templateId = ( int ) Mage::getStoreConfig ( 'marketplace/seller_review/admin_notify_review' );
         } else {
            $templateId = ( int ) Mage::getStoreConfig ( 'marketplace/seller_review/notify_new_review' );
         }
         $adminEmailId = Mage::getStoreConfig ( 'marketplace/marketplace/admin_email_id' );
         $toMailId = Mage::getStoreConfig ( "trans_email/ident_$adminEmailId/email" );
         $toName = Mage::getStoreConfig ( "trans_email/ident_$adminEmailId/name" );
         if ($templateId) {
            $emailTemplate = Mage::getModel ( static::CORE_EMAIL_TEMP )->load ( $templateId );
         } else {
            if ($needAdmin == 1) {
               $emailTemplate = Mage::getModel ( static::CORE_EMAIL_TEMP )->loadDefault ( 'marketplace_seller_review_admin_notify_review' );
            } else {
               $emailTemplate = Mage::getModel ( static::CORE_EMAIL_TEMP )->loadDefault ( 'marketplace_seller_review_notify_new_review' );
            }
         }
         $adminurl = Mage::helper ( 'adminhtml' )->getUrl ( 'marketplaceadmin/adminhtml_sellerreview/index' );
         $customer = Mage::getModel ( static::CUSTOMER_CUSTOMER )->load ( $data [static::CUSTOMER_ID] );
         $cname = $customer->getName ();
         $cemail = $customer->getEmail ();
         $emailTemplate->setSenderEmail ( $cemail );
         $emailTemplate->setSenderName ( ucwords ( $cname ) );
         $emailTemplate->setDesignConfig ( array (
               'area' => 'frontend' 
         ) );
         $emailTemplateVariables = (array (
               'ownername' => ucwords ( $toName ),
               'cname' => ucwords ( $cname ),
               'cemail' => $cemail,
               'adminurl' => $adminurl 
         ));
         $emailTemplate->getProcessedTemplate ( $emailTemplateVariables );
         $emailTemplate->send ( $toMailId, ucwords ( $toName ), $emailTemplateVariables );
         return true;
      } else {
         return false;
      }
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
   public function sellerdisplay($sellerId, $storeId) {
      $sellerDetail = array ();
      $sellerDetail = Mage::getModel ( 'marketplace/sellerprofile' )->load ( $sellerId, static::SELLER_ID )->getData ();
      
      /**
       * load customer info
       */
      $customerInfo = Mage::getModel ( 'customer/customer' )->load ( $sellerDetail [static::SELLER_ID] );
      // get seller email
      $sellerDetail ['email'] = $customerInfo->getEmail ();
      // get seller name
      $sellerDetail ['name'] = $customerInfo->getName ();
      
      if ($sellerDetail ['country']) {
         $country = Mage::getModel ( 'directory/country' )->loadByCode ( $sellerDetail ['country'] );
         // get country name
         $sellerDetail ['country_name'] = $country->getName ();
      }
      $sellerDetail ['store_logo'] = Mage::getBaseUrl ( 'media' ) . DS . 'marketplace/resized/' . $sellerDetail ['store_logo'];
      $sellerDetail ['store_banner'] = Mage::getBaseUrl ( 'media' ) . DS . 'marketplace/resized/' . $sellerDetail ['store_banner'];
      $sellerDetail ['summary_rating'] = strval ( Mage::getModel ( static::LOGIN_METHODS )->averageRatings ( $sellerId, $storeId ) );
      $sellerDetail [static::RATINGS] = strval ( Mage::getModel ( static::LOGIN_METHODS )->getReviewsCount ( $sellerId, $storeId ) );
      return $sellerDetail;
   }
   
   /**
    * Function to get seller product
    *
    * Passed the seller id as $sellerId to get particular seller products
    *
    * @param int $sellerId
    *           Return products of the seller
    */
   public function sellerproduct($products, $storeId) {
      $productDetail = array ();
      
      /**
       * Get image size for resize
       */
      $imageSize = ( int ) $this->getRequest ()->getParam ( 'image_size' );
      
      $i = 0;
      foreach ( $products->getData () as $product ) {
         $_product = Mage::getModel ( 'catalog/product' )->load ( $product [static::ENTITYID] );
         $productDetail [$i] [static::ENTITYID] = $_product->getId ();
         $productDetail [$i] ['type_id'] = $_product->getTypeId ();
         $productDetail [$i] ['name'] = $_product->getName ();
         /**
          * Get product image
          */
         if ($imageSize <= 0) {
            $productDetail [$i] ['image_url'] = ( string ) Mage::helper ( 'catalog/image' )->init ( $_product, static::SMALLIMG );
         } else {
            $productDetail [$i] ['image_url'] = ( string ) Mage::helper ( 'catalog/image' )->init ( $_product, static::SMALLIMG )->constrainOnly ( TRUE )->keepAspectRatio ( TRUE )->keepFrame ( FALSE )->resize ( $imageSize, null );
         }
         $productDetail [$i] ['regular_price_with_tax'] = number_format ( $_product->getPrice (), 2, '.', '' );
         $productDetail [$i] ['final_price_with_tax'] = number_format ( $_product->getFinalPrice (), 2, '.', '' );
         $i ++;
      }
      
      return $productDetail;
   }
   
   /**
    * Get all sellers list
    */
   public function sellerList() {
      $users = Mage::getModel ( static::CUSTOMER_CUSTOMER )->getCollection ()->addFieldToFilter ( 'group_id', 6 );
      return $users->getData ();
   }
}