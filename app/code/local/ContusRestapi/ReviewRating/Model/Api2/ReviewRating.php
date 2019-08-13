<?php
/**
 * Contus
 * 
 *  Forgotpassword API
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
 * @package    ContusRestapi_ReviewRating
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
 * @since      1.0
 */
class ContusRestapi_ReviewRating_Model_Api2_ReviewRating extends Mage_Api2_Model_Resource {
   
   // define static variable.
   const STOREID = 'store_id';
   const WEBSITEID = 'website_id';
   const CUSTOMER_ID = 'customer_id';
   const PRODUCT_ID = 'product_id';
   const SUCCESS = 'success';
   const MESSAGE = 'message';
   const ERROR = 'error';
   const RESULT = 'result';
   const TOKEN = 'token';
   const LOGIN_TOKEN = 'login/token';
   const VALID_TOKEN = 'isValidToken';
   const REVIEWS = 'reviews';
   const RATING = 'rating';
   const RATINGS = 'ratings';
   const LOGIN_METHODS = 'login/methods_functions';
   const TOT_REVWS_CNT = 'total_reviews_count';
   const SUMMARY_RATING = 'summary_rating';
   
   /**
    * function that is called when post is done **
    *
    * Add new review and rating and get all review by product id
    *
    * @param array $data           
    *
    * @return array json array
    */
   protected function _create(array $data) {
      $response = array ();
      // get customer id
      $customerId = ( int ) $data [static::CUSTOMER_ID];
      // get product Id from request
      $productId = ( int ) $data [static::PRODUCT_ID];
      $websiteId = ( int ) $data [static::WEBSITEID];
      if ($websiteId <= 0) {
         $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
      }
      // get store id from request
      $storeId = ( int ) $data [static::STOREID];
      if ($storeId <= 0) {
         Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
      }
      // set review status as static 1 for approved. 2 for pending
      $reviewStatus = 1;
      // get rating value from request
      $rating = $data [static::RATING];
      // get token value from request
      $token = $data [static::TOKEN];
      
      /**
       * Check this customer has valid token or not
       *
       * @var $isValidToken Mage_Login_Model_Token
       */
      $isValidToken = Mage::getModel ( 'login/token' )->checkUserToken ( $customerId, $token );
      
      /**
       *
       * @var $review Mage_Review_Model_Review
       */
      $review = Mage::getModel ( 'review/review' );
      try {
         // check customer token
         if (! $isValidToken) {
            throw new Exception ( 'Authentication failed.' );
         }
         $review->setEntityPkValue ( $productId );
         $review->setStatusId ( $reviewStatus );
         $review->setTitle ( $data ['review_title'] );
         $review->setDetail ( $data ['review_description'] );
         $review->setEntityId ( 1 );
         $review->setStoreId ( $sotreId );
         $review->setCustomerId ( $customerId );
         $review->setNickname ( $data ['customer_name'] );
         $review->setReviewId ( $review->getId () );
         $review->setStores ( array (
               $storeId 
         ) );
         $review->save ();
         
         /**
          *
          * @var $customer Mage_Rating_Model_Rating
          */
         $ratingModel = Mage::getModel ( 'rating/rating' )->setReviewId ( $review->getId () )->setCustomerId ( $customerId );
         // save rating for quality
         $ratingModel->setRatingId ( 1 )->addOptionVote ( $rating, $productId );
         // save rating for value
         $ratingModel->setRatingId ( 2 )->addOptionVote ( $rating + 5, $productId );
         // save rating for price
         $ratingModel->setRatingId ( 3 )->addOptionVote ( $rating + 10, $productId );
         $review->aggregate ();
         if ($review->save ()) {
            $success = 1;
            // Thank you for submitting a review. Your review is awaiting moderation. this is for pendind status
            $message = 'Thank you for submitting a review. Your review is added successfully.';
         } else {
            $success = 0;
            $message = 'Sorry, we are unable to add your review at this time.';
         }
      } catch ( Exception $e ) {
         $success = 0;
         $message = $e->getMessage ();
      }
      
      /**
       * Getting average of ratings/reviews
       */
      $reviewRating = Mage::getModel ( static::LOGIN_TOKEN )->getRatingResult ( $productId, $storeId );
      // get total reviews
      $total_reviews_count = $review->getTotalReviews ( $productId, true, $storeId );
      
      $response [static::VALID_TOKEN] = $isValidToken;
      $response [static::ERROR] = false;
      $response [static::SUCCESS] = $success;
      $response [static::MESSAGE] = $message;
      $response [static::RESULT] = $reviewRating;
      $response [static::RESULT] ['review_status'] = $reviewStatus;
      $response [static::RESULT] [static::TOT_REVWS_CNT] = $total_reviews_count;
      $response [static::RESULT] [static::SUMMARY_RATING] = Mage::getModel ( static::LOGIN_TOKEN )->rateSummary ( $productId, $storeId ) ? Mage::getModel ( static::LOGIN_TOKEN )->rateSummary ( $productId, $storeId ) : '0';
      
      $this->getResponse ()->setBody ( json_encode ( $response ) );
      return;
   }
   
   /**
    * Get reviews ans rating colection fro Product
    *
    * @see Mage_Api2_Model_Resource::_retrieveCollection()
    */
   protected function _retrieveCollection() {
      $response = array ();
      
      // get website id from request
      $websiteId = ( int ) $this->getRequest ()->getParam ( static::WEBSITEID );
      if ($websiteId <= 0) {
         $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
      }
      
      // get store id
      $storeId = ( int ) $this->getRequest ()->getParam ( static::STOREID );
      if ($storeId <= 0) {
         $storeId = Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
      }
      // get product id
      $productId = ( int ) $this->getRequest ()->getParam ( 'product_id' );
      
      $page = ( int ) $this->getRequest ()->getParam ( 'page' );
      if ($page <= 0) {
         $page = 1;
      }
      $limit = ( int ) $this->getRequest ()->getParam ( 'limit' );
      if ($limit <= 0) {
         $limit = 10;
      }
      try {
         /**
          *
          * @var $review Mage_Review_Model_Review
          */
         $review = Mage::getModel ( 'review/review' );
         // get total reviews
         $total_reviews_count = $review->getTotalReviews ( $productId, true, $storeId );
         
         /**
          * Getting average of ratings/reviews
          */
         $last_page = ceil ( $total_reviews_count / $limit );
         if ($last_page < $page) {
            $reviewRating [static::REVIEWS] = array ();
            $ratings [static::RATING] = array ();
         } else {
            $reviewRating = Mage::getModel ( static::LOGIN_TOKEN )->getReviews ( $productId, $storeId, $page, $limit );
            // get product rating count summary
            $ratings = Mage::getModel ( static::LOGIN_TOKEN )->getRatingResult ( $productId, $storeId );
         }
         $success = 1;
      } catch ( Exception $e ) {
         $success = 0;
      }
      
      $response [static::ERROR] = false;
      $response [static::SUCCESS] = $success;
      $response [static::RESULT] [static::TOT_REVWS_CNT] = $total_reviews_count;
      $response [static::RESULT] [static::SUMMARY_RATING] = Mage::getModel ( static::LOGIN_TOKEN )->rateSummary ( $productId, $storeId ) ? Mage::getModel ( static::LOGIN_TOKEN )->rateSummary ( $productId, $storeId ) : '0';
      $response [static::RESULT] [static::REVIEWS] = $reviewRating [static::REVIEWS];
      // get product rating count summary
      $response [static::RESULT] [static::RATING] = $ratings [static::RATING];
      return $response;
   }
   
   /**
    * Retrieve seller reviews in Seller info page
    *
    * @return array json array
    */
   protected function _retrieve() {
      $response = array ();
      
      // get website id from request
      $websiteId = ( int ) $this->getRequest ()->getParam ( static::WEBSITEID );
      if ($websiteId <= 0) {
         $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
      }
      
      // get store id
      $storeId = ( int ) $this->getRequest ()->getParam ( static::STOREID );
      if ($storeId <= 0) {
         $storeId = Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
      }
      
      $page = ( int ) $this->getRequest ()->getParam ( 'page' );
      if ($page <= 0) {
         $page = 1;
      }
      $limit = ( int ) $this->getRequest ()->getParam ( 'limit' );
      if ($limit <= 0) {
         $limit = 10;
      }
      
      /**
       * Get customer id from request
       */
      $sellerId = ( int ) $this->getRequest ()->getParam ( 'seller_id' );
      // get total review count
      $total_reviews_count = Mage::getModel ( static::LOGIN_METHODS )->getReviewsCount ( $sellerId, $storeId );
      // Getting ratings/reviews
      // get only five reviews in seller info page
      
      $last_page = ceil ( $total_reviews_count / $limit );
      if ($last_page < $page) {
         $reviews [static::REVIEWS] = array ();
      } else {
         $reviews = Mage::getModel ( static::LOGIN_METHODS )->getSellerReviews ( $sellerId, $storeId, $page, $limit );
      }
      
      $response [static::ERROR] = false;
      $response [static::SUCCESS] = 1;
      $response ['message'] = 'Get reviews list.';
      $response [static::TOT_REVWS_CNT] = strval ( $total_reviews_count );
      $response [static::RATINGS] = strval ( $total_reviews_count );
      $response [static::SUMMARY_RATING] = strval ( Mage::getModel ( static::LOGIN_METHODS )->averageRatings ( $sellerId, $storeId ) );
      $response [static::REVIEWS] = $reviews [static::REVIEWS];
      
      return json_encode ( $response );
   }
}