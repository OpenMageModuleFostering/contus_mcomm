<?php
/**
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
 * @package    ContusRestapi_MyOrders
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
 * @since      1.0
 */
class ContusRestapi_MyOrders_Model_Api2_MyOrderapi extends Mage_Sales_Model_Api2_Order_Rest {
   
   // Declaring the string literals variable
   const STOREID = 'store_id';
   const CUSTOMERID = 'customer_id';
   const ATTR_INFO = 'attributes_info';
   const OPTIONS = 'options';
   const SHIPPING_ADDR = 'shipping_address';
   const RESULT = 'result';
   const SUCCESS = 'success';
   const MESSAGE = 'message';
   const ERROR = 'error';
   const VALID_TOKEN = 'isValidToken';
   const LOGIN_TOKEN = 'login/token';
   const TOKEN = 'token';
   const TOTAL_COUNT = 'total_count';
   const AUTH_ERR_MSG = 'Authentication failed.';
   const SALES_ORDER = 'sales/order';
   const ORDER_ID = 'order_id';
   const STATUS = 'status';
   const ITEM_ID = 'item_id';
   const PURCHASE_ID = 'purchased_id';
   const ORDER_ITEM_ID = 'order_item_id';
   const CREATED_AT = 'created_at';
   const PRODUCT_ID = 'product_id';
   const CUSTOM_OPTION = 'custom_option';
   const SUPER_ATTR = 'super_attribute';
   const SUPER_GRP = 'super_group';
   const QTY = 'qty';
   const CATALOG_PRO = 'catalog/product';
   const WEBSITE_ID = 'website_id';
   const SELLER_STATUS = 'seller_status';
   const SELLER_ID = 'seller_id';
   
   /**
    * Retrieve information about specified order item
    *
    * @throws Mage_Api2_Exception
    * @return array
    */
   protected function _retrieve() {
      $response = array ();
      $ordersData = array ();
      $orderId = $this->getRequest ()->getParam ( 'id' );
      
      /**
       * Check this customer has valid token or not
       *
       * @var $isValidToken Mage_Login_Model_Token
       */
      $isValidToken = Mage::getModel ( static::LOGIN_TOKEN )->checkUserToken ( Mage::app ()->getRequest ()->getParam ( static::CUSTOMERID ), Mage::app ()->getRequest ()->getParam ( static::TOKEN ) );
      try {
         
         /**
          *
          * @var $order Mage_Sales_Model_Order
          */
         if (! $isValidToken) {
            throw new Exception ( static::AUTH_ERR_MSG );
         }
         
         $order = Mage::getModel ( static::SALES_ORDER )->loadByIncrementId ( $orderId );
         if ($order->getIncrementId () != '') {
            // get order id
            $ordersData [static::ORDER_ID] = $order->getIncrementId ();
            $ordersData [static::STATUS] = ucfirst ( strtolower ( $order->getStatusLabel () ) );
            $ordersData ['order_date'] = $order->getCreatedAt ();
            $ordersData ['item_count'] = $order->getTotalItemCount ();
            $ordersData ['grand_total'] = number_format ( $order->getGrandTotal (), 2, '.', '' );
            // get shipping address
            $shipping_address = $order->getShippingAddress ();
            if ($shipping_address) {
               $shippingAddresDetail = $shipping_address->getData ();
               // get shipping address details
               $ordersData [static::SHIPPING_ADDR] ['firstname'] = $shippingAddresDetail ['firstname'];
               $ordersData [static::SHIPPING_ADDR] ['lastname'] = $shippingAddresDetail ['lastname'];
               $ordersData [static::SHIPPING_ADDR] ['street'] = explode ( "\n", $shippingAddresDetail ['street'] );
               $ordersData [static::SHIPPING_ADDR] ['city'] = $shippingAddresDetail ['city'];
               $ordersData [static::SHIPPING_ADDR] ['region'] = $shippingAddresDetail ['region'];
               $ordersData [static::SHIPPING_ADDR] ['country_id'] = $shippingAddresDetail ['country_id'];
               // get country name by country id
               $country = Mage::getModel ( 'directory/country' )->loadByCode ( $shipping_address->country_id );
               $ordersData [static::SHIPPING_ADDR] ['country_name'] = $country->getName ();
               $ordersData [static::SHIPPING_ADDR] ['telephone'] = $shippingAddresDetail ['telephone'];
               $ordersData [static::SHIPPING_ADDR] ['postcode'] = $shippingAddresDetail ['postcode'];
            }
            $orderCurrencyCode = $order->getOrderCurrencyCode ();
            $ordersData ['currency_symbol'] = Mage::app ()->getLocale ()->currency ( $orderCurrencyCode )->getSymbol ();
            // get shipping method
            $ordersData ['shipping_method'] = $order->getShippingDescription ();
            // get payment method
            $ordersData ['payment_method'] = $order->getPayment ()->getMethodInstance ()->getTitle ();
            
            // get delivery information
            if (Mage::getStoreConfig ( 'deliveryschedule/general/delivery_schedule_enabled' ) == 1) {
               $ordersData ['delivery_info'] = $this->getdeliveryDetails ( $order );
            }
            $orderItems = $order->getItemsCollection ();
            // get item collection
            $ordersData ['items'] = $this->getOrderedItemDetail ( $orderItems, $order->getIncrementId () );
            
            // get shipping amount
            $ordersData ['shipping_amount'] = number_format ( $order ['base_shipping_amount'], 2, '.', '' );
            $ordersData ['discount_amount'] = number_format ( $order ['base_discount_amount'], 2, '.', '' );
            $ordersData ['tax_amount'] = number_format ( $order ['base_tax_amount'], 2, '.', '' );
            $ordersData ['sub_amount'] = number_format ( $order ['base_subtotal'], 2, '.', '' );
            $ordersData ['total_amount'] = number_format ( $order ['base_grand_total'], 2, '.', '' );
            
            // get surprise gift option
            if (! is_null ( $order->getGiftMessageId () )) {
               $ordersData ['surprise_gift'] = 1;
               $message = Mage::getModel ( 'giftmessage/message' );
               $message->load ( ( int ) $order->getGiftMessageId () );
               $ordersData ['gift_message'] = $message->getData ( 'message' );
            } else {
               $ordersData ['surprise_gift'] = 0;
               $ordersData ['gift_message'] = '';
            }
            
            $success = 1;
            $message = 'Get order details.';
         } else {
            $success = 0;
            $message = 'No data found.';
         }
      } catch ( Exception $e ) {
         $success = 0;
         $message = $e->getMessage ();
      }
      $response [static::VALID_TOKEN] = $isValidToken;
      $response [static::ERROR] = false;
      $response [static::SUCCESS] = $success;
      $response [static::MESSAGE] = $message;
      $response [static::RESULT] = $ordersData;
      return json_encode ( $response );
   }
   
   /**
    * Get Delivery information about order
    *
    * @return array $deliveryInfo
    */
   public function getdeliveryDetails($order) {
      $deliveryInfo = array ();
      // get delivery schedule type name
      $schedule_type = $order->getShippingDeliverySchedule ();
      if ($schedule_type) {
         $deliveryInfo ['schedule_type'] = $schedule_type;
         // get delivery date
         $deliveryInfo ['date'] = date ( "M d Y", strtotime ( $order->getShippingDeliveryDate () ) );
         // get delivery title
         $deliveryInfo ['schedule_title'] = $order->getShippingDeliveryTime ();
         // get delivery cost
         $deliveryInfo ['cost'] = $order->getDeliveryCost ();
         // get delivery time
         $deliveryInfo ['delivery_time'] = $order->getDeliveryTime ();
         if (Mage::getStoreConfig ( 'deliveryschedule/general/delivery_comment_enabled' ) == 1) {
            // get delivery comment
            $deliveryInfo ['comment'] = $order->getShippingDeliveryComments ();
         } else {
            $deliveryInfo ['comment'] = '';
         }
      }
      return $deliveryInfo;
   }
   
   /**
    * Get orders list
    *
    * @return array
    */
   protected function _retrieveCollection() {
      $response = array ();
      
      /**
       * Check this customer has valid token or not
       *
       * @var $isValidToken Mage_Login_Model_Token
       */
      $isValidToken = Mage::getModel ( static::LOGIN_TOKEN )->checkUserToken ( Mage::app ()->getRequest ()->getParam ( static::CUSTOMERID ), Mage::app ()->getRequest ()->getParam ( static::TOKEN ) );
      
      if ($isValidToken) {
         
         $response = $this->_getCollectionForRetrieve ();
      } else {
         $success = 0;
         $message = static::AUTH_ERR_MSG;
         $response [static::VALID_TOKEN] = $isValidToken;
         $response [static::ERROR] = false;
         $response [static::SUCCESS] = $success;
         $response [static::MESSAGE] = $message;
         $response [static::TOTAL_COUNT] = 0;
         
         $response [static::RESULT] = array ();
      }
      
      return json_encode ( $response );
   }
   
   /**
    * Retrieve collection instance for orders list
    *
    * @return Mage_Sales_Model_Resource_Order_Collection
    */
   protected function _getCollectionForRetrieve() {
      
      // get website id from request
      $websiteId = ( int ) $this->getRequest ()->getParam ( static::WEBSITE_ID );
      if ($websiteId <= 0) {
         $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
      }
      // get store id from request
      $storeId = ( int ) $this->getRequest ()->getParam ( static::STOREID );
      if ($storeId <= 0) {
         $storeId = Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
      }
      
      // get page from request
      $page = ( int ) $this->getRequest ()->getParam ( 'page' );
      // get page from request
      $limit = ( int ) $this->getRequest ()->getParam ( 'limit' );
      
      /**
       *
       * @var $collection Mage_Sales_Model_Resource_Order_Collection
       */
      $collection = Mage::getResourceModel ( 'sales/order_collection' );
      if (isset ( $_GET ) && Mage::app ()->getRequest ()->getParam ( static::CUSTOMERID )) {
         $collection->addFieldToFilter ( static::CUSTOMERID, Mage::app ()->getRequest ()->getParam ( static::CUSTOMERID ) )->addFieldToFilter ( static::STOREID, $storeId );
         $collection->setPage ( $page, $limit );
         $collection->setOrder ( static::CREATED_AT, 'DESC' );
      }
      
      if ($this->_isPaymentMethodAllowed ()) {
         $this->_addPaymentMethodInfo ( $collection );
      }
      if ($this->_isGiftMessageAllowed ()) {
         $this->_addGiftMessageInfo ( $collection );
      }
      $this->_addTaxInfo ( $collection );
      $this->_applyCollectionModifiers ( $collection );
      
      $ordersData = array ();
      $i = 0;
      $collection->getSelectSql ( true );
      
      // get total orders count
      $totalOrders = $collection->getSize ();
      $last_page = ceil ( $totalOrders / $limit );
      if ($last_page < $page) {
         $success = 1;
         $message = 'No Orders Found';
         $ordersData = array ();
      } else {
         
         $collection = $collection->getItems ();
         foreach ( $collection as $order ) {
            $name = '';
            /**
             *
             * @var $order Mage_Sales_Model_Order
             */
            $order = Mage::getModel ( static::SALES_ORDER )->load ( $order->getId () );
            $shipping_address = $order->getShippingAddress ();
            $billing_address = $order->getBillingAddress ();
            if ($shipping_address) {
               $name = $shipping_address->getFirstname () . ' ' . $shipping_address->getLastname ();
            } else {
               $name = $billing_address->getFirstname () . ' ' . $billing_address->getLastname ();
            }
            
            $ordersData [$i] [static::ORDER_ID] = $order->getIncrementId ();
            $ordersData [$i] [static::STATUS] = ucfirst ( strtolower ( $order->getStatusLabel () ) );
            $ordersData [$i] ['order_date'] = $order->getCreatedAt ();
            $ordersData [$i] ['item_count'] = $order->getTotalItemCount ();
            
            $ordersData [$i] ['grand_total'] = number_format ( $order->getGrandTotal (), 2, '.', '' );
            
            $ordersData [$i] ['ship_name'] = $name;
            $orderCurrencyCode = $order->getOrderCurrencyCode ();
            $ordersData [$i] ['currency_symbol'] = Mage::app ()->getLocale ()->currency ( $orderCurrencyCode )->getSymbol ();
            $i ++;
         }
         $success = 1;
         $message = 'Orders listed successfully.';
      }
      
      $response [static::VALID_TOKEN] = true;
      $response [static::ERROR] = false;
      $response [static::SUCCESS] = $success;
      $response [static::MESSAGE] = $message;
      $response [static::TOTAL_COUNT] = $totalOrders;
      $response [static::RESULT] = $ordersData;
      return $response;
   }
   
   /**
    * Get ordered item collection
    *
    * @param array $orderItems           
    * @return $items
    */
   public function getOrderedItemDetail($orderItems, $orderId) {
      $items = array ();
      $catalogProduct = Mage::getModel ( static::CATALOG_PRO );
      foreach ( $orderItems as $item ) {
         $product = array ();
         if ($item ['parent_item_id'] == '') {
            /**
             * load the product ID
             */
            
            $product ['entity_id'] = $item->product_id;
            $product ['price'] = number_format ( $item->getPrice (), 2, '.', '' );
            $product ['name'] = $item->getName ();
            $_product = Mage::getModel ( static::CATALOG_PRO )->load ( $item->product_id );
            $product ['image_url'] = ( string ) Mage::helper ( 'catalog/image' )->init ( $_product, 'thumbnail' );
            $product ['qty'] = strval ( $item ['qty_ordered'] );
            $product ['row_total'] = number_format ( $item ['base_row_total'], 2, '.', '' );
            
            $product_options = $item ['product_options'];
            $super_attribute = unserialize ( $product_options );
            if (isset ( $super_attribute [static::ATTR_INFO] )) {
               $product [static::ATTR_INFO] = $super_attribute ['attributes_info'];
            } else {
               $product [static::ATTR_INFO] = array ();
            }
            if (isset ( $super_attribute [static::OPTIONS] )) {
               $product [static::OPTIONS] = $super_attribute [static::OPTIONS];
            } else {
               $product [static::OPTIONS] = array ();
            }
            if (Mage::getStoreConfig ( 'marketplace/marketplace/activate' )) {
               
               $catalogProduct->load ( $item->product_id );
               /**
                * Get the Seller ID
                */
               $sellerId = $catalogProduct->getSellerId ();
               /**
                * Get the Item order status
                */
               $sellerStatus = $this->getMarketplaceStatus ( $sellerId, $item->product_id, $orderId );
               $product [static::SELLER_STATUS] = $sellerStatus [static::SELLER_STATUS];
               $product [static::SELLER_ID] = $sellerStatus [static::SELLER_ID];
            }
            
            $items [] = $product;
         }
      }
      return $items;
   }
   
   /**
    * Get the Seller Item order status
    *
    * @param int $sellerId           
    * @param int $productId           
    * @param int $orderId           
    * @return array $sellerStatus
    */
   public function getMarketplaceStatus($sellerId, $productId, $orderId) {
      $sellerStatus = array ();
      if ($sellerId) {
         /**
          * Get the Seller Item order status
          */
         $checkOrderStatus = Mage::getModel ( 'marketplace/commission' )->getCollection ()->addFieldToSelect ( 'item_order_status' )->addFieldToFilter ( 'increment_id', $orderId )->addFieldToFilter ( 'product_id', $productId )->addFieldToFilter ( 'seller_id', $sellerId );
         
         foreach ( $checkOrderStatus as $orderStatus ) {
            $sellerStatus [static::SELLER_STATUS] = $orderStatus ['item_order_status'];
         }
         $sellerStatus [static::SELLER_ID] = $sellerId;
      } else {
         $sellerStatus [static::SELLER_STATUS] = '';
         $sellerStatus [static::SELLER_ID] = '';
      }
      return $sellerStatus;
   }
   
   /**
    * (non-PHPdoc)
    *
    * @see Mage_Api2_Model_Resource::_create($filteredData)
    */
   public function _create(array $data) {
      $response = array ();
      $download_items = array ();
      // get customer id from request
      $customerId = ( int ) $data [static::CUSTOMERID];
      
      /**
       * Check this customer has valid token or not
       *
       * @var $isValidToken Mage_Login_Model_Token
       */
      $isValidToken = Mage::getModel ( static::LOGIN_TOKEN )->checkUserToken ( $customerId, $data [static::TOKEN] );
      
      try {
         // check customer token
         if (! $isValidToken) {
            throw new Exception ( static::AUTH_ERR_MSG );
         }
         
         $purchased = Mage::getResourceModel ( 'downloadable/link_purchased_collection' )->addFieldToFilter ( static::CUSTOMERID, $customerId )->addOrder ( static::CREATED_AT, 'desc' );
         
         $purchasedIds = array ();
         // form array
         foreach ( $purchased as $_item ) {
            $purchasedIds [] = $_item->getId ();
         }
         if (empty ( $purchasedIds )) {
            $purchasedIds = array (
                  null 
            );
         }
         
         /**
          * $purchasedItems Mage_Downloadble_Model_Link_Purchase_Item_Collection
          */
         $purchasedItems = Mage::getResourceModel ( 'downloadable/link_purchased_item_collection' )->addFieldToFilter ( static::PURCHASE_ID, array (
               'in' => $purchasedIds 
         ) )->addFieldToFilter ( static::STATUS, array (
               'nin' => array (
                     Mage_Downloadable_Model_Link_Purchased_Item::LINK_STATUS_PENDING_PAYMENT,
                     Mage_Downloadable_Model_Link_Purchased_Item::LINK_STATUS_PAYMENT_REVIEW 
               ) 
         ) )->setOrder ( static::ITEM_ID, 'desc' );
         
         $success = 1;
         $message = '';
         $download_items = $this->getdownloadItemsLik ( $purchasedItems->getData () );
      } catch ( Exception $e ) {
         $success = 0;
         $message = $e->getMessage ();
      }
      
      $response [static::VALID_TOKEN] = $isValidToken;
      $response [static::ERROR] = false;
      $response [static::SUCCESS] = $success;
      $response [static::MESSAGE] = $message;
      $response [static::RESULT] = $download_items;
      
      $this->getResponse ()->setBody ( json_encode ( $response ) );
      return;
   }
   
   /**
    * Get Downloadable product collection
    *
    * @param array $items           
    * @return array:
    */
   public function getdownloadItemsLik($items) {
      $data = array ();
      $j = 0;
      foreach ( $items as $item ) {
         $data [$j] [static::ITEM_ID] = $item [static::ITEM_ID];
         $data [$j] [static::PURCHASE_ID] = $item [static::PURCHASE_ID];
         $data [$j] [static::ORDER_ITEM_ID] = $item [static::ORDER_ITEM_ID];
         // get product id
         $data [$j] [static::PRODUCT_ID] = $item [static::PRODUCT_ID];
         // get download link id
         $data [$j] ['link_id'] = $item ['link_id'];
         // get download title
         $data [$j] ['link_title'] = $item ['link_title'];
         // get media url
         $mediaUrl = Mage::getBaseUrl ( Mage_Core_Model_Store::URL_TYPE_MEDIA );
         // get download url
         $data [$j] ['link_file'] = $mediaUrl . "downloadable/files/links" . $item ['link_file'];
         // get status
         $data [$j] [static::STATUS] = $item [static::STATUS];
         // number_of_downloads_bought 0 ->unlimited
         $data [$j] ['number_of_downloads_bought'] = $item ['number_of_downloads_bought'];
         $data [$j] ['number_of_downloads_used'] = $item ['number_of_downloads_used'];
         $data [$j] [static::CREATED_AT] = $item [static::CREATED_AT];
         
         /**
          *
          * @var $orderItem Mage_Sales_Model_Order_Item
          */
         $orderItem = Mage::getModel ( 'sales/order_item' )->getCollection ()->addFieldToFilter ( 'item_id', $item [static::ORDER_ITEM_ID] )->getFirstItem ();
         
         /**
          *
          * @var $order Mage_Sales_Model_Order
          */
         $order = Mage::getModel ( static::SALES_ORDER )->getCollection ()->addFieldToFilter ( 'entity_id', $orderItem->getOrderId () )->getFirstItem ();
         
         $data [$j] [static::ORDER_ID] = $order->getIncrementId ();
         
         $productModel = Mage::getModel ( static::CATALOG_PRO )->load ( $item [static::PRODUCT_ID] );
         $data [$j] ['name'] = $productModel->getName ();
         $j ++;
      }
      return $data;
   }
   
   /**
    * Reorder option
    *
    * @see Mage_Api2_Model_Resource::_update($filteredData)
    */
   public function _update(array $data) {
      $response = array ();
      $message = array ();
      // get customer id from request
      $customerId = ( int ) $data [static::CUSTOMERID];
      
      // Get website id
      $websiteId = (isset ( $data [static::WEBSITE_ID] )) ? $data [static::WEBSITE_ID] : Mage::app ()->getWebsite ( 'base' )->getId ();
      // get store id
      $storeId = (isset ( $data [static::STOREID] )) ? $data [static::STOREID] : Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
      
      $orderId = $data ['order_id'];
      /**
       * Check this customer has valid token or not
       *
       * @var $isValidToken Mage_Login_Model_Token
       */
      $isValidToken = Mage::getModel ( static::LOGIN_TOKEN )->checkUserToken ( $customerId, $data [static::TOKEN] );
      $error_flag = true;
      try {
         if (! $isValidToken) {
            throw new Exception ( static::AUTH_ERR_MSG );
         }
         
         $order = Mage::getModel ( static::SALES_ORDER )->loadByIncrementId ( $orderId );
         $orderCurrencyCode = $order->getOrderCurrencyCode ();
         
         // get cart quote by customer
         $quote = Mage::getModel ( static::LOGIN_TOKEN )->setSaleQuoteByCustomer ( $customerId, $storeId, $orderCurrencyCode );
         
         $i = 0;
         // oop for all order items
         $products = $order->getAllVisibleItems ();
         foreach ( $products as $item ) {
            
            $super_attribute = unserialize ( $item ['product_options'] );
            $config [static::SUPER_ATTR] = $super_attribute ['info_buyRequest'] [static::SUPER_ATTR];
            $config [static::OPTIONS] = $super_attribute ['info_buyRequest'] [static::OPTIONS];
            
            $product = Mage::getModel ( 'multiCartapi/api2_multiCartapi' )->_getProduct ( $item->getProductId (), $storeId, 'id' );
            if (is_null ( $product )) {
               $error = $item->getProductId () . ' - Can not specify the product.';
            } else {
               $error = Mage::getModel ( 'multiCartapi/api2_multiCartapi' )->addToCart ( $product, $quote, $item->getQtyToInvoice (), $config );
            }
            
            if (is_string ( $error )) {
               $messages [$i] [static::MESSAGE] = $item->getName () . '- ' . $error;
               $messages [$i] [static::SUCCESS] = 0;
               $error_flag = true;
            } else {
               $messages [$i] [static::MESSAGE] = $item->getName () . ' was added to your cart successfully.';
               $messages [$i] [static::SUCCESS] = 1;
               $error_flag = false;
            }
            
            $i ++;
         }
         $quote->collectTotals ();
         $quote->save ();
         if ($error_flag) {
            $success = 0;
            $message = 'Can not added to cart.';
         } else {
            $success = 1;
            $message = 'Products added to cart successfully.';
         }
      } catch ( Mage_Core_Exception $e ) {
         $success = 0;
         $message = $e->getMessage ();
      }
      
      $response [static::VALID_TOKEN] = $isValidToken;
      $response [static::ERROR] = false;
      $response [static::SUCCESS] = $success;
      $response [static::MESSAGE] = $message;
      
      $response [static::RESULT] ['errors'] = $messages;
      $this->getResponse ()->setBody ( json_encode ( $response ) );
      return;
   }
}