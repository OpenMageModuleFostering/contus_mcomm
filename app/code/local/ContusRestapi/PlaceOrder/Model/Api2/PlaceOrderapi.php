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
 * @package    ContusRestapi_PlaceOrderapi
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
 * @since      1.0
 */
class ContusRestapi_PlaceOrder_Model_Api2_PlaceOrderapi extends Mage_Api2_Model_Resource {
   const MESSAGE = 'message';
   const SUCCESS = 'success';
   const ORDER_ID = 'order_id';
   const URL = 'url';
   const SUCCESS_MSG = 'Order placed successfully.';
   
   /**
    * Place order using Cash on delivery
    *
    * @return array json array
    */
   protected function _create(array $data) {
      $response = array ();
      
      // get customer id
      $customerId = ( int ) $data ['customer_id'];
      // get quote id
      $quoteId = ( int ) $data ['quote_id'];
      
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
      
      /**
       * Check this customer has valid token or not
       *
       * @var $isValidToken Mage_Login_Model_Token
       */
      $isValidToken = Mage::getModel ( 'login/token' )->checkUserToken ( $customerId, $data ['token'] );
      
      try {
         $paymentMethod = $data ['payment_method'];
         $shippingMethod = $data ['shipping_method'];
         $quoteObj = Mage::getModel ( 'sales/quote' );
         $quoteObj->setStoreId ( $storeId )->load ( $quoteId );
         // check customer token
         if (! $isValidToken) {
            throw new Exception ( 'Authentication failed.' );
         }
         // check quote id in valid
         $active = $quoteObj->getIsActive ();
         if (! $active) {
            throw new Exception ( 'Quote is invalid!.' );
         }
         $quoteObj->getAllItems ();
         $quoteObj->reserveOrderId ();
         $orderObj = $this->SetQuoteObject ( $quoteObj, $shippingMethod, $paymentMethod );
         
         // Add delivery info to order
         if (Mage::getStoreConfig ( 'deliveryschedule/general/delivery_schedule_enabled' ) == 1) {
            $this->addDeliveryInfo ( $quoteObj, $orderObj, $data );
         }
         
         // Add gift message id to this order
         if ($data ['surprise_gift'] == 1) {
            $this->addGift ( $quoteObj, $orderObj, $customerId, $data );
         }
         
         if ($paymentMethod == 'paypal_standard') {
            $result = $this->paypalStandard ( $quoteObj, $orderObj );
            $response [static::MESSAGE] = $result [static::MESSAGE];
            $response [static::ORDER_ID] = $result [static::ORDER_ID];
            $response [static::SUCCESS] = $result [static::SUCCESS];
            $response [static::URL] = $result [static::URL];
         } else {
            $orderObj->place ();
            $orderObj->save ();
            $orderObj->sendNewOrderEmail ();
            $quoteObj->setIsActive ( 0 )->save ();
            $response [static::MESSAGE] = static::SUCCESS_MSG;
            $response [static::ORDER_ID] = $quoteObj->getreservedOrderId ();
            $response [static::SUCCESS] = 1;
            $response [static::URL] = '';
         }
      } catch ( Exception $e ) {
         $response [static::SUCCESS] = 0;
         $response [static::MESSAGE] = $e->getMessage ();
      }
      $response ['isValidToken'] = $isValidToken;
      $response ['error'] = false;
      
      $this->getResponse ()->setBody ( json_encode ( $response ) );
      return;
   }
   
   /**
    * Place order using paypal standard payment
    *
    * @param object $quoteObj           
    * @param object $orderObj           
    * @return array
    */
   public function paypalStandard($quoteObj, $orderObj) {
      $response = array ();
      $transaction = Mage::getModel ( 'core/resource_transaction' );
      if ($quoteObj->getCustomerId ()) {
         $transaction->addObject ( $quoteObj->getCustomer () );
      }
      
      $transaction->addObject ( $orderObj );
      $transaction->addCommitCallback ( array (
            $orderObj,
            'place' 
      ) );
      $transaction->addCommitCallback ( array (
            $orderObj,
            'save' 
      ) );
      try {
         $transaction->save ();
      } catch ( Exception $e ) {
         // Set error message if the place order get failed transaction.
         $response [static::SUCCESS] = 0;
         $response [static::MESSAGE] = $e->getMessage ();
      }
      
      Mage::dispatchEvent ( 'checkout_type_onepage_save_order_after', array (
            'order' => $orderObj,
            'quote' => $quoteObj 
      ) );
      $quoteObj->setIsActive ( 0 )->save ();
      
      $response [static::MESSAGE] = static::SUCCESS_MSG;
      $response [static::ORDER_ID] = $quoteObj->getreservedOrderId ();
      $response [static::SUCCESS] = 1;
      $resourcePath = Mage::getBaseUrl ( Mage_Core_Model_Store::URL_TYPE_WEB ) . 'paypal/';
      $redirectUrl = $resourcePath . 'redirect.php?id=' . $response [static::ORDER_ID] . '&expType=mini';
      $response [static::URL] = $redirectUrl;
      
      return $response;
   }
   
   /**
    * Add shipping and payment method
    * 
    * @param object $quoteObj           
    * @param string $shippingMethod           
    * @param string $paymentMethod           
    * @return object
    */
   public function SetQuoteObject($quoteObj, $shippingMethod, $paymentMethod) {
      
      // set shipping method
      $quoteObj->getShippingAddress ()->setShippingMethod ( $shippingMethod );
      $quoteObj->getShippingAddress ()->setCollectShippingRates ( false );
      $quoteObj->getShippingAddress ()->collectShippingRates ();
      
      $items = $quoteObj->getAllItems ();
      // set payment method
      $quotePaymentObj = $quoteObj->getPayment ();
      // Mage_Sales_Model_Quote_Payment
      $quotePaymentObj->setMethod ( $paymentMethod );
      $quoteObj->setPayment ( $quotePaymentObj );
      $quoteObj->collectTotals ();
      $quoteObj->save ();
      
      // convert quote to order
      $convertQuoteObj = Mage::getSingleton ( 'sales/convert_quote' );
      $orderObj = $convertQuoteObj->addressToOrder ( $quoteObj->getShippingAddress () );
      $convertQuoteObj->paymentToOrderPayment ( $quotePaymentObj );
      
      // convert quote addresses
      $orderObj->setBillingAddress ( $convertQuoteObj->addressToOrderAddress ( $quoteObj->getBillingAddress () ) );
      $orderObj->setShippingAddress ( $convertQuoteObj->addressToOrderAddress ( $quoteObj->getShippingAddress () ) );
      
      // set payment options
      $orderObj->setPayment ( $convertQuoteObj->paymentToOrderPayment ( $quoteObj->getPayment () ) );
      
      // convert quote items
      $orderObj = $this->addQuoteProduct ( $items, $convertQuoteObj, $orderObj );
      
      $orderObj->setCanShipPartiallyItem ( TRUE );
      
      return $orderObj;
   }
   
   /**
    * Add products to Sales_order_items table
    *
    * @param array $items           
    * @param object $convertQuoteObj           
    * @param object $orderObj           
    * @return object $orderObj
    */
   public function addQuoteProduct($items, $convertQuoteObj, $orderObj) {
      
      // convert quote items
      foreach ( $items as $item ) {
         $itemDesc = $item->getItemDescription ();
         $orderItem = $convertQuoteObj->itemToOrderItem ( $item );
         $options = array ();
         
         if ($productOptions = $item->getProduct ()->getTypeInstance ( TRUE )->getOrderOptions ( $item->getProduct () )) {
            $options = $productOptions;
         }
         
         if ($addOptions = $item->getOptionByCode ( 'additional_options' )) {
            $options ['additional_options'] = unserialize ( $addOptions->getValue () );
         }
         
         if ($options) {
            $orderItem->setProductOptions ( $options );
         }
         
         if ($itemDesc) {
            $orderItem->setName ( $itemDesc );
         }
         
         if ($item->getParentItem ()) {
            $orderItem->setParentItem ( $orderObj->getItemByQuoteItemId ( $item->getParentItem ()->getId () ) );
         }
         $orderObj->addItem ( $orderItem );
         Mage::getSingleton ( 'cataloginventory/stock' )->registerItemSale ( $orderItem );
      }
      
      return $orderObj;
   }
   
   /**
    * Add delivery information to quote,address and order
    *
    * @param object $quoteObj           
    * @param object $orderObj           
    * @param array $data           
    */
   public function addDeliveryInfo($quoteObj, $orderObj, $data) {
      $desiredDeliverySchedule = $data ['delivery_schedule_types'];
      $shippingDeliveryTimeId = $data ['shipping_delivery_time'];
      $shippingDeliveryCost = $data ['shipping_delivery_cost'];
      $desireDeliveryDate = $data ['shipping_delivery_date'];
      $desireDeliveryComments = $data ['shipping_delivery_comments'];
      $delivery_time = $data ['delivery_time'];
      /**
       * shipping delivery time slot using id
       */
      $getTimeById = Mage::helper ( 'deliveryschedule' )->getTimeById ( $shippingDeliveryTimeId );
      /**
       * get delivery schedule type by id
       */
      $getScheduleTypeById = Mage::helper ( 'deliveryschedule' )->getScheduleTypeById ( $desiredDeliverySchedule );
      $title = $scheduleTypeName = "";
      foreach ( $getTimeById as $row ) {
         $title = $row->getTitle ();
      }
      foreach ( $getScheduleTypeById as $rows ) {
         $scheduleTypeName = $rows->getName ();
      }
      if (isset ( $desireDeliveryDate ) && ! empty ( $desireDeliveryDate )) {
         // save deliver info to sales_flat_quote table
         $quoteObj->setShippingDeliverySchedule ( $scheduleTypeName );
         $quoteObj->setShippingDeliveryComments ( $desireDeliveryComments );
         $quoteObj->setShippingDeliveryDate ( $desireDeliveryDate );
         $quoteObj->setShippingDeliveryTime ( $title );
         $quoteObj->setShippingDeliveryCost ( $shippingDeliveryCost );
         $quoteObj->setDeliveryTime ( $delivery_time );
         $quoteObj->save ();
         
         // save delivery info to sales_flat_order table
         $orderObj->setShippingDeliverySchedule ( $scheduleTypeName );
         $orderObj->setShippingDeliveryComments ( $desireDeliveryComments );
         $orderObj->setShippingDeliveryDate ( $desireDeliveryDate );
         $orderObj->setShippingDeliveryTime ( $title );
         $orderObj->setShippingDeliveryCost ( $shippingDeliveryCost );
         $orderObj->setDeliveryTime ( $delivery_time );
         if ($shippingDeliveryCost != '') {
            $address = $quoteObj->getShippingAddress ();
            // save delivery info to sales/quote_address table
            $address->setDeliveryCost ( $shippingDeliveryCost );
            $address->setBaseDeliveryCost ( $shippingDeliveryCost );
            // calculate GrandTotal
            $address->setGrandTotal ( $address->getGrandTotal () + $address->getDeliveryCost () );
            $address->setBaseGrandTotal ( $address->getBaseGrandTotal () + $address->getBaseDeliveryCost () );
            
            // save delivery cost to sales_flat_order table
            $orderObj->setDeliveryCost ( $shippingDeliveryCost );
            $orderObj->setBaseDeliveryCost ( $shippingDeliveryCost );
            $orderObj->setGrandTotal ( $address->getGrandTotal () );
            $orderObj->setBaseGrandTotal ( $address->getBaseGrandTotal () );
            $orderObj->save ();
         }
      }
   }
   
   /**
    * Add Gift mesage to this order and quote
    *
    * @param object $quoteObj           
    * @param object $orderObj           
    */
   public function addGift($quoteObj, $orderObj, $customerId, $data) {
      
      /**
       * load customer info
       */
      $customerInfo = Mage::getModel ( 'customer/customer' )->load ( $customerId );
      // get sender name from customer info
      $fromName = $customerInfo->getName ();
      // get recepient name from shipping address
      $toName = $quoteObj->getShippingAddress ()->getName ();
      
      /**
       * Laod gift message model
       *
       * @var $giftMessage Mage_Giftmessage_Model_Message
       */
      $giftMessage = Mage::getModel ( 'giftmessage/message' );
      $giftMessage->setCustomerId ( $customerId );
      $giftMessage->setSender ( $fromName );
      $giftMessage->setRecipient ( $toName );
      $giftMessage->setMessage ( $data ['gift_message'] );
      $giftObj = $giftMessage->save ();
      
      // save gift message id to quote
      $quoteObj->setGiftMessageId ( $giftObj->getId () );
      $quoteObj->save ();
      // save gift message id to order
      $orderObj->setGiftMessageId ( $giftObj->getId () );
      $orderObj->save ();
   }
}