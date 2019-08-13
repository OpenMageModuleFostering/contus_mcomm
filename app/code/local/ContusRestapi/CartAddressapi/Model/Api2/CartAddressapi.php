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
 * @package    ContusRestapi_CartAddressapi
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
 * @since      1.0
 */
class ContusRestapi_CartAddressapi_Model_Api2_CartAddressapi extends Mage_Api2_Model_Resource {
   const SCHEDULE_TYPE = 'schedule_type';
   const SCHEDULE_TYPE_ID = 'schedule_type_id';
   const TITLE = 'title';
   const SCHEDULE_TITLE = 'schedule_title';
   const DELIVERY_SCHEDULE_ID = 'deliveryschedule_id';
   const SCHEDULE_ID = 'schedule_id';
   const INTERVAL = 'interval';
   const TIME_SLOT = 'time_slot';
   const TIME_INTERVAL = 'time_interval';
   const DAY_INTERVAL = 'day_interval';
   
   /**
    * function that is called when post is done **
    * Get active shipping and payment Methods
    *
    * @return array json array
    */
   protected function _create(array $data) {
      $response = array ();
      $ship_payment = array ();
      // get website id
      $websiteId = ( int ) $data ['website_id'];
      if ($websiteId <= 0) {
         $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
      }
      // get website id from request
      $storeId = ( int ) $data ['store_id'];
      if ($storeId <= 0) {
         $storeId = Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
      }
      // get customer id
      $customerId = ( int ) $data ['customer_id'];
      
      // get quote id
      $quoteId = ( int ) $data ['quote_id'];
      // get default billing address id
      $billing_addressId = ( int ) $data ['billing_address_id'];
      // get default shipping address id
      $shipping_addressId = ( int ) $data ['shipping_address_id'];
      
      /**
       * Check this customer has valid token or not
       *
       * @var $isValidToken Mage_Login_Model_Token
       */
      $isValidToken = Mage::getModel ( 'login/token' )->checkUserToken ( $customerId, $data ['token'] );
      if ($quoteId && $isValidToken) {
         try {
            $quote = Mage::getModel ( 'sales/quote' );
            $quote->setStoreId ( $storeId )->load ( $quoteId );
            
            $active = $quote->getIsActive ();
            if (! $active) {
               throw new Exception ( 'Quote is invalid!.' );
            }
            
            // Set billing address
            $quoteBillingAddress = Mage::getModel ( 'sales/quote_address' );
            if ($billing_addressId > 0 && $shipping_addressId > 0) {
               $quoteBillingAddress->setStoreId ( $storeId )->setCustomerId ( $customerId )->setCustomerAddressId ( $billing_addressId );
               $billingAddress = Mage::getModel ( 'customer/address' )->load ( $billing_addressId );
               $quoteBillingAddress->setData ( $billingAddress->getData () );
               $quote->setBillingAddress ( $quoteBillingAddress );
               
               // set shipping adress
               $quoteShippingAddress = Mage::getModel ( 'sales/quote_address' );
               $quoteShippingAddress->setStoreId ( $storeId )->setCustomerId ( $customerId )->setCustomerAddressId ( $shipping_addressId );
               $shippingAddress = Mage::getModel ( 'customer/address' )->load ( $shipping_addressId );
               
               $quoteShippingAddress->setData ( $shippingAddress->getData () );
               $quote->setShippingAddress ( $quoteShippingAddress );
               $quote->getShippingAddress ()->setCollectShippingRates ( true );
               $quote->collectTotals ();
               $quote->setIsActive ( 1 );
               $quote->save ();
               
               $success = 1;
               $message = "Billing and Shipping Address added successfully.";
            } else {
               $success = 0;
               $message = "Please select billing and shipping address.";
            }
            $ship_payment = Mage::getModel ( 'login/methods_functions' )->getShippingPaymentMethods ( $quoteId, $storeId );
         } catch ( Exception $e ) {
            $success = 0;
            $message = $e->getMessage ();
         }
      } else {
         $success = 0;
         $message = "Authentication failed.";
      }
      $response ['isValidToken'] = $isValidToken;
      $response ['error'] = false;
      $response ['success'] = $success;
      $response ['message'] = $message;
      $response ['result'] = $ship_payment;
      $this->getResponse ()->setBody ( json_encode ( $response ) );
      return;
   }
   
   /**
    * Retrieve information Delivery information
    *
    * @throws Mage_Api2_Exception
    * @return array
    */
   protected function _retrieve() {
      $response = array ();
      // get website id from request
      $websiteId = ( int ) $this->getRequest ()->getParam ( 'website_id' );
      if ($websiteId <= 0) {
         $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
      }
      // get website id from request
      $storeId = ( int ) $this->getRequest ()->getParam ( 'store_id' );
      if ($storeId <= 0) {
         Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
      }
      $deliveryInfo = array ();
      $deliveryModes = array ();
      if (Mage::getStoreConfig ( 'deliveryschedule/general/delivery_schedule_enabled' ) == 1) {
         $deliveryInfo = $this->getdeliveryDetails ( $storeId );
         $deliveryModes = $this->getDeliveryModes ( $storeId );
      }
      
      $response ['error'] = false;
      $response ['success'] = 1;
      $response ['message'] = 'Get Delivery Information';
      
      $response ['modes'] = $deliveryModes;
      $response ['result'] = $deliveryInfo;
      
      return $response;
   }
   
   /**
    * get delivery modes
    *
    * @param int $storeId           
    * @return array $modes
    */
   public function getDeliveryModes($storeId) {
      $modes = array ();
      $scheduleTypes = Mage::getModel ( 'deliveryschedule/deliveryscheduletypes' )->getCollection ()->addFieldToFilter ( "status", 1 )->setOrder ( 'sorting', 'ASC' )->addFieldToFilter ( "store_view", array (
            "in" => array (
                  '0',
                  $storeId 
            ) 
      ) );
      $i = 0;
      foreach ( $scheduleTypes as $type ) {
         $modes [$i] ['schedule_type_id'] = $type->getId ();
         $modes [$i] ['schedule_type'] = $type->getName ();
         $modes [$i] ['desc'] = $type->getDescription ();
         $i ++;
      }
      
      return $modes;
   }
   
   /**
    * Get delivery time slots from mwdtime table
    *
    * @param int $storeId           
    * @return array $delivery
    */
   public function getdeliveryDetails($storeId) {
      $deliveryschedule = Mage::getModel ( 'deliveryschedule/deliveryschedule' )->getCollection ();
      $deliveryschedule->getSelect ()->join ( array (
            static::SCHEDULE_TYPE => Mage::getSingleton ( "core/resource" )->getTableName ( 'deliveryschedule_types' ) 
      ), 'schedule_type.id = main_table.schedule_type_id and main_table.status= 1', array (
            'schedule_type.id',
            'schedule_type.name',
            'schedule_type.monday as mon',
            'schedule_type.tuesday as tue',
            'schedule_type.wednesday as wed',
            'schedule_type.thursday as thu',
            'schedule_type.friday as fri',
            'schedule_type.saturday as sat',
            'schedule_type.sunday as sun' 
      ) )->where ( 'schedule_type.store_view in (?)', array (
            '0',
            $storeId 
      ) )->where ( 'schedule_type.status = 1' )->order ( 'main_table.sorting asc' );
      
      $delivery = array ();
      $m = 0;
      $t = 0;
      $w = 0;
      $th = 0;
      $f = 0;
      $sat = 0;
      $sun = 0;
      foreach ( $deliveryschedule as $schedule ) {
         if ($schedule ['mon'] == 1) {
            $delivery ['mon'] [$m] [static::SCHEDULE_TYPE_ID] = $schedule [static::SCHEDULE_TYPE_ID];
            $delivery ['mon'] [$m] [static::SCHEDULE_TYPE] = $schedule ['name'];
            $delivery ['mon'] [$m] [static::SCHEDULE_TITLE] = $schedule [static::TITLE];
            $delivery ['mon'] [$m] [static::SCHEDULE_ID] = $schedule [static::DELIVERY_SCHEDULE_ID];
            $delivery ['mon'] [$m] [static::INTERVAL] = $schedule [static::TIME_SLOT];
            
            $delivery ['mon'] [$m] [static::TIME_INTERVAL] = $schedule [static::TIME_INTERVAL];
            $delivery ['mon'] [$m] [static::DAY_INTERVAL] = $schedule [static::DAY_INTERVAL];
            $delivery ['mon'] [$m] ['cost'] = $schedule ['monday_cost'];
            $m ++;
         } elseif ($m == 0) {
            $delivery ['mon'] [$m] = array ();
         } else {
            // no code here
         }
         if ($schedule ['tue'] == 1) {
            $delivery ['tue'] [$t] [static::SCHEDULE_TYPE_ID] = $schedule [static::SCHEDULE_TYPE_ID];
            $delivery ['tue'] [$t] [static::SCHEDULE_TYPE] = $schedule ['name'];
            $delivery ['tue'] [$t] [static::SCHEDULE_ID] = $schedule [static::DELIVERY_SCHEDULE_ID];
            $delivery ['tue'] [$t] [static::SCHEDULE_TITLE] = $schedule [static::TITLE];
            $delivery ['tue'] [$t] [static::INTERVAL] = $schedule [static::TIME_SLOT];
            
            $delivery ['tue'] [$t] [static::TIME_INTERVAL] = $schedule [static::TIME_INTERVAL];
            $delivery ['tue'] [$t] [static::DAY_INTERVAL] = $schedule [static::DAY_INTERVAL];
            $delivery ['tue'] [$t] ['cost'] = $schedule ['tuesday_cost'];
            $t ++;
         } elseif ($t == 0) {
            $delivery ['tue'] = array ();
         } else {
            // no code here
         }
         if ($schedule ['wed'] == 1) {
            $delivery ['wed'] [$w] [static::SCHEDULE_TYPE_ID] = $schedule [static::SCHEDULE_TYPE_ID];
            $delivery ['wed'] [$w] [static::SCHEDULE_TYPE] = $schedule ['name'];
            $delivery ['wed'] [$w] [static::SCHEDULE_ID] = $schedule [static::DELIVERY_SCHEDULE_ID];
            $delivery ['wed'] [$w] [static::SCHEDULE_TITLE] = $schedule [static::TITLE];
            $delivery ['wed'] [$w] [static::INTERVAL] = $schedule [static::TIME_SLOT];
            
            $delivery ['wed'] [$w] [static::TIME_INTERVAL] = $schedule [static::TIME_INTERVAL];
            $delivery ['wed'] [$w] [static::DAY_INTERVAL] = $schedule [static::DAY_INTERVAL];
            $delivery ['wed'] [$w] ['cost'] = $schedule ['wednesday_cost'];
            $w ++;
         } elseif ($w == 0) {
            $delivery ['wed'] = array ();
         } else {
            // no code here
         }
         if ($schedule ['thu'] == 1) {
            $delivery ['thu'] [$th] [static::SCHEDULE_TYPE_ID] = $schedule [static::SCHEDULE_TYPE_ID];
            $delivery ['thu'] [$th] [static::SCHEDULE_TYPE] = $schedule ['name'];
            $delivery ['thu'] [$th] [static::SCHEDULE_ID] = $schedule [static::DELIVERY_SCHEDULE_ID];
            $delivery ['thu'] [$th] [static::SCHEDULE_TITLE] = $schedule [static::TITLE];
            $delivery ['thu'] [$th] [static::INTERVAL] = $schedule [static::TIME_SLOT];
            
            $delivery ['thu'] [$th] [static::TIME_INTERVAL] = $schedule [static::TIME_INTERVAL];
            $delivery ['thu'] [$th] [static::DAY_INTERVAL] = $schedule [static::DAY_INTERVAL];
            $delivery ['thu'] [$th] ['cost'] = $schedule ['thursday_cost'];
            $th ++;
         } elseif ($th == 0) {
            $delivery ['thu'] = array ();
         } else {
            // no code here
         }
         if ($schedule ['fri'] == 1) {
            $delivery ['fri'] [$f] [static::SCHEDULE_TYPE_ID] = $schedule [static::SCHEDULE_TYPE_ID];
            $delivery ['fri'] [$f] [static::SCHEDULE_TYPE] = $schedule ['name'];
            $delivery ['fri'] [$f] [static::SCHEDULE_ID] = $schedule [static::DELIVERY_SCHEDULE_ID];
            $delivery ['fri'] [$f] [static::SCHEDULE_TITLE] = $schedule [static::TITLE];
            $delivery ['fri'] [$f] [static::INTERVAL] = $schedule [static::TIME_SLOT];
            
            $delivery ['fri'] [$f] [static::TIME_INTERVAL] = $schedule [static::TIME_INTERVAL];
            $delivery ['fri'] [$f] [static::DAY_INTERVAL] = $schedule [static::DAY_INTERVAL];
            
            $delivery ['fri'] [$f] ['cost'] = $schedule ['friday_cost'];
            $f ++;
         } elseif ($f == 0) {
            $delivery ['fri'] = array ();
         } else {
            // no code here
         }
         if ($schedule ['sat'] == 1) {
            $delivery ['sat'] [$sat] [static::SCHEDULE_TYPE_ID] = $schedule [static::SCHEDULE_TYPE_ID];
            $delivery ['sat'] [$sat] [static::SCHEDULE_TYPE] = $schedule ['name'];
            $delivery ['sat'] [$sat] [static::SCHEDULE_ID] = $schedule [static::DELIVERY_SCHEDULE_ID];
            $delivery ['sat'] [$sat] [static::SCHEDULE_TITLE] = $schedule [static::TITLE];
            $delivery ['sat'] [$sat] [static::INTERVAL] = $schedule [static::TIME_SLOT];
            
            $delivery ['sat'] [$sat] [static::TIME_INTERVAL] = $schedule [static::TIME_INTERVAL];
            $delivery ['sat'] [$sat] [static::DAY_INTERVAL] = $schedule [static::DAY_INTERVAL];
            $delivery ['sat'] [$sat] ['cost'] = $schedule ['saturday_cost'];
            $sat ++;
         } elseif ($sat == 0) {
            $delivery ['sat'] = array ();
         } else {
            // no code here
         }
         if ($schedule ['sun'] == 1) {
            $delivery ['sun'] [$sun] [static::SCHEDULE_TYPE_ID] = $schedule [static::SCHEDULE_TYPE_ID];
            $delivery ['sun'] [$sun] [static::SCHEDULE_TYPE] = $schedule ['name'];
            $delivery ['sun'] [$sun] [static::SCHEDULE_ID] = $schedule [static::DELIVERY_SCHEDULE_ID];
            $delivery ['sun'] [$sun] [static::SCHEDULE_TITLE] = $schedule [static::TITLE];
            $delivery ['sun'] [$sun] [static::INTERVAL] = $schedule [static::TIME_SLOT];
            
            $delivery ['sun'] [$sun] [static::TIME_INTERVAL] = $schedule [static::TIME_INTERVAL];
            $delivery ['sun'] [$sun] [static::DAY_INTERVAL] = $schedule [static::DAY_INTERVAL];
            $delivery ['sun'] [$sun] ['cost'] = $schedule ['sunday_cost'];
            $sun ++;
         } elseif ($sun == 0) {
            $delivery ['sun'] = array ();
         } else {
            // no code here
         }
      }
      return $delivery;
   }
}