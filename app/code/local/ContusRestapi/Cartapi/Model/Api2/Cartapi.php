<?php
/**
 * Contus
 * 
 * Cart Api
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
 * @package    ContusRestapi_Cartapi
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
 * @since      1.0
 */
class ContusRestapi_Cartapi_Model_Api2_Cartapi extends Mage_Api2_Model_Resource {
    // define staic variable
    const STOREID = 'store_id';
    const WEBSITEID = 'website_id';
    const CUSTOMER_ID = 'customer_id';
    const ENTITYID = 'entity_id';
    const PRODUCT_ID = 'product_id';
    const QUOTE_ID = 'quote_id';
    const SLAES_QUOTE = 'sales/quote';
    const IS_STOCK = 'is_stock';
    const STOCK_QTY = 'stock_qty';
    const CATALOG_STOCK = 'cataloginventory/stock_item';
    const SUCCESS = 'success';
    const MESSAGE = 'message';
    const ERROR = 'error';
    const RESULT = 'result';
    const TOKEN = 'token';
    const LOGIN_TOKEN = 'login/token';
    const VALID_TOKEN = 'isValidToken';
    const AUTH_FAIL = 'Authentication failed.';
    const CUSTOM_OPTION = 'custom_option';
    const OPTIONS = 'options';
    const SUPER_ATTR = 'super_attribute';
    const SUPER_GRP = 'super_group';
    const QTY = 'qty';
    const DISCOUNT = 'discount';
    const SHIPPING_AMT = 'shipping_amount';
    const TAX = 'tax';
    const ITEM_COUNT = 'item_count';
    const GRAND_TOTAL = 'grand_total';
    const SUBTOTAL = 'subtotal';
    const COUPON_CODE = 'coupon_code';
    const NO_ITEM = 'No items in your cart.';
    const ITEMS_COUNT = 'items_count';
    const LINKS = 'links';
    const ITEMS_QTY = 'items_qty';
    
    /**
     * Add product to cart
     *
     * @param array $data            
     * @return array json array
     */
    protected function _create(array $data) {
        $response = array ();
        
        // Get website id
        $websiteId = (isset ( $data [static::WEBSITEID] )) ? $data [static::WEBSITEID] : Mage::app ()->getWebsite ( 'base' )->getId ();
        // get store id
        $storeId = (isset ( $data [static::STOREID] )) ? $data [static::STOREID] : Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
        // get customer id
        $customerId = ( int ) $data [static::CUSTOMER_ID];
        // get product Id from request
        $productId = ( int ) $data [static::PRODUCT_ID];
        $qty = ( int ) $data [static::QTY];
        if ($qty <= 0) {
            $qty = 1;
        }
        // get base currency code
        $currencyCode = (isset ( $data ['currencyCode'] )) ? $data ['currencyCode'] : Mage::app ()->getStore ()->getBaseCurrencyCode ();
        
        // get cart quote by customer
        $quote = Mage::getModel ( static::LOGIN_TOKEN )->setSaleQuoteByCustomer ( $customerId, $storeId, $currencyCode );
        
        /**
         * Check this customer has valid token or not
         *
         * @var $isValidToken Mage_Login_Model_Token
         */
        $isValidToken = Mage::getModel ( static::LOGIN_TOKEN )->checkUserToken ( $customerId, $data [static::TOKEN] );
        
        $response [static::VALID_TOKEN] = true;
        $product = $this->_getProduct ( $productId, $storeId, 'id' );
        if (is_null ( $product )) {
            $error = 'Can not specify the product.';
        } else if (! $isValidToken) {
            $error = static::AUTH_FAIL;
            $response [static::VALID_TOKEN] = false;
        } else {
            $error = $this->addToCart ( $product, $quote, $qty, $data );
        }
        
        if (is_string ( $error ) || $quote == 0) {
            $message = ($quote) ? $error : 'Customer not found.';
            $error_flag = false;
            $success = 0;
        } else {
            $message = 'Product added to cart successfully.';
            $quote->collectTotals ();
            $quote->save ();
            $error_flag = false;
            $success = 1;
            
            $response [static::ITEM_COUNT] = Mage::getModel ( static::LOGIN_TOKEN )->getCartCount ( $customerId, $storeId );
        }
        $response [static::SUCCESS] = $success;
        $response [static::ERROR] = $error_flag;
        $response [static::MESSAGE] = $message;
        
        $this->getResponse ()->setBody ( json_encode ( $response ) );
        return;
    }
    
    /**
     * Retrieve Cart products collection
     *
     * @param integer $customerId            
     * @return array json array
     */
    protected function _retrieveCollection() {
        $response = array ();
        
        $customerId = ( int ) $this->getRequest ()->getParam ( static::CUSTOMER_ID );
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
        /**
         * Check this customer has valid token or not
         *
         * @var $isValidToken Mage_Login_Model_Token
         */
        $response [static::VALID_TOKEN] = true;
        $token = $this->getRequest ()->getParam ( static::TOKEN );
        $isValidToken = Mage::getModel ( static::LOGIN_TOKEN )->checkUserToken ( $customerId, $token );
        if ($isValidToken) {
            $prodctsList = Mage::getModel ( static::LOGIN_TOKEN )->getCartProducts ( array (
                    static::STOREID => $storeId,
                    static::CUSTOMER_ID => $customerId 
            ) );
            $result = $prodctsList ['result'];
            $response [static::SUCCESS] = 1;
            $response [static::ERROR] = false;
            $response [static::MESSAGE] = $prodctsList [static::MESSAGE];
        } else {
            $result = array ();
            $response [static::VALID_TOKEN] = false;
            $response [static::ERROR] = true;
            $response [static::SUCCESS] = 0;
            $response [static::MESSAGE] = static::AUTH_FAIL;
        }
        $response [static::RESULT] = $result;
        return $response;
    }
    
    /**
     * Update Cart products Qty
     *
     * @param array $data            
     * @return array json array
     */
    protected function _update(array $data) {
        $response = array ();
        
        // get quote id from request
        $quoteId = ( int ) $data [static::QUOTE_ID];
        // get product id from request
        $productId = ( int ) $data [static::PRODUCT_ID];
        // get qty from request
        $qty = $data [static::QTY];
        
        // get customer id from request
        $customerId = ( int ) $data [static::CUSTOMER_ID];
        
        // Get website id
        $websiteId = (isset ( $data [static::WEBSITEID] )) ? $data [static::WEBSITEID] : Mage::app ()->getWebsite ( 'base' )->getId ();
        // get store id
        $storeId = (isset ( $data [static::STOREID] )) ? $data [static::STOREID] : Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
        
        $quote = Mage::getModel ( static::SLAES_QUOTE );
        $quote->setStoreId ( $storeId )->load ( $quoteId );
        $productByItem = $this->_getProduct ( $productId, $storeId, 'id' );
        $productItem = $this->updateToCart ( $productByItem, $quote, $qty, $data );
        
        /**
         * Check this customer has valid token or not
         *
         * @var $isValidToken Mage_Login_Model_Token
         */
        $isValidToken = Mage::getModel ( static::LOGIN_TOKEN )->checkUserToken ( $customerId, $data [static::TOKEN] );
        if ($isValidToken) {
            try {
                
                if (empty ( $productByItem )) {
                    $success = 0;
                    $message = 'Item is not available.';
                } else {
                    $quoteItem = Mage::getModel ( static::LOGIN_TOKEN )->_getQuoteItemByProduct ( $quote, $productByItem, Mage::getModel ( static::LOGIN_TOKEN )->_getProductRequest ( $productItem ) );
                    
                    $update = $this->cartUpdate ( $quoteItem, $quote, $qty );
                    $success = $update [static::SUCCESS];
                    $message = $update [static::MESSAGE];
                }
            } catch ( Mage_Core_Exception $e ) {
                $success = 0;
                $message = $e->getMessage ();
            }
        } else {
            $success = 0;
            $message = static::AUTH_FAIL;
        }
        
        $cartTotal = Mage::getModel ( static::LOGIN_TOKEN )->getCartTotal ( array (
                static::STOREID => $storeId,
                static::CUSTOMER_ID => $customerId 
        ) );
        $response [static::ITEM_COUNT] = $cartTotal [static::ITEM_COUNT];
        $response [static::VALID_TOKEN] = $isValidToken;
        $response [static::ERROR] = false;
        $response [static::SUCCESS] = $success;
        $response [static::MESSAGE] = $message;
        $response [static::RESULT] = $cartTotal [static::RESULT];
        $this->getResponse ()->setBody ( json_encode ( $response ) );
        return;
    }
    
    /**
     * Delete product from cart
     *
     * @param integer $quoteId            
     * @param integer $productId            
     *
     * @return array json array
     */
    protected function _delete() {
        
        // get quote id from request
        $quoteId = ( int ) $this->getRequest ()->getParam ( static::QUOTE_ID );
        // get product id from request
        $productId = ( int ) $this->getRequest ()->getParam ( static::PRODUCT_ID );
        
        // get customer id from request
        $customerId = ( int ) $this->getRequest ()->getParam ( static::CUSTOMER_ID );
        // Get website id
        $websiteId = $this->getRequest ()->getParam ( static::WEBSITEID );
        // get store id
        $storeId = $this->getRequest ()->getParam ( static::STOREID );
        
        $websiteId = (isset ( $websiteId )) ? $websiteId : Mage::app ()->getWebsite ( 'base' )->getId ();
        $storeId = (isset ( $storeId )) ? $storeId : Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
        
        $quote = Mage::getModel ( static::SLAES_QUOTE );
        $quote->setStoreId ( $storeId )->load ( $quoteId );
        
        // get custom options
        $options = $this->getRequest ()->getParam ( static::CUSTOM_OPTION );
        foreach ( (json_decode ( $options )) as $key => $option ) {
            if (is_string ( $option )) {
                $option = str_replace ( '$$$$', ' ', $option );
            }
            $custom_option [$key] = $option;
        }
        $custom_option = json_encode ( $custom_option );
        // get configurable product attribute
        $super_attribute = $this->getRequest ()->getParam ( static::SUPER_ATTR );
        // get downloadble product links
        $links = $this->getRequest ()->getParam ( static::LINKS );
        
        /**
         * Check this customer has valid token or not
         *
         * @var $isValidToken Mage_Login_Model_Token
         */
        $isValidToken = Mage::getModel ( static::LOGIN_TOKEN )->checkUserToken ( $customerId, $this->getRequest ()->getParam ( static::TOKEN ) );
        if ($isValidToken) {
            
            $productByItem = $this->_getProduct ( $productId, $storeId, 'id' );
            $productItem = $this->deleteFromCart ( $productByItem, $quote, $qty, $custom_option, $super_attribute, $links );
            
            try {
                
                if (empty ( $productByItem )) {
                    $success = 0;
                    $err_message = 'Item is not available.';
                } else {
                    $quoteItem = Mage::getModel ( static::LOGIN_TOKEN )->_getQuoteItemByProduct ( $quote, $productByItem, Mage::getModel ( static::LOGIN_TOKEN )->_getProductRequest ( $productItem ) );
                    
                    $delete = $this->cartDelete ( $quoteItem, $quote );
                    $success = $delete [static::SUCCESS];
                    $err_message = $delete [static::MESSAGE];
                }
            } catch ( Mage_Core_Exception $e ) {
                $success = 0;
                $err_message = $e->getMessage ();
            }
        } else {
            $success = 0;
            $err_message = static::AUTH_FAIL;
        }
        // get cart total
        $cartTotal = Mage::getModel ( static::LOGIN_TOKEN )->getCartTotal ( array (
                static::STOREID => $storeId,
                static::CUSTOMER_ID => $customerId 
        ) );
        $response [static::ITEM_COUNT] = $cartTotal [static::ITEM_COUNT];
        $response [static::VALID_TOKEN] = $isValidToken;
        $response [static::ERROR] = false;
        $response [static::SUCCESS] = $success;
        $response [static::MESSAGE] = $err_message;
        $response [static::RESULT] = $cartTotal [static::RESULT];
        
        $this->getResponse ()->setBody ( json_encode ( $response ) );
        return;
    }
    
    /**
     * Get cart dotal qty
     *
     * @param integer $customerId            
     * @return array json array
     */
    protected function _retrieve() {
        $response = array ();
        // get customer id from request
        $customerId = Mage::app ()->getRequest ()->getParam ( static::CUSTOMER_ID );
        // get website id from request
        $websiteId = ( int ) $this->getRequest ()->getParam ( static::WEBSITEID );
        if ($websiteId <= 0) {
            $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
        }
        // get store id from request
        $storeId = ( int ) Mage::app ()->getRequest ()->getParam ( static::STOREID );
        if ($storeId <= 0) {
            $storeId = Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
        }
        /**
         * Check this customer has valid token or not
         *
         * @var $isValidToken Mage_Login_Model_Token
         */
        $token = $this->getRequest ()->getParam ( static::TOKEN );
        $isValidToken = Mage::getModel ( static::LOGIN_TOKEN )->checkUserToken ( $customerId, $token );
        if ($isValidToken) {
            $quoteData = $this->getQuoteIdBycustomer ( $customerId, $storeId );
            
            $itemCount = '0';
            if (isset ( $quoteData ) && ! empty ( $quoteData )) {
                $itemCount = $quoteData [0] [static::ITEMS_COUNT];
                $success = 1;
                $message = 'Item is listed';
            }
        } else {
            $itemCount = null;
            $success = 0;
            $message = static::AUTH_FAIL;
        }
        $response [static::VALID_TOKEN] = $isValidToken;
        $response [static::ERROR] = false;
        $response [static::ITEM_COUNT] = $itemCount;
        $response [static::SUCCESS] = $success;
        $response [static::MESSAGE] = $message;
        return $response;
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
                $request = new Varien_Object ( array (
                        static::SUPER_GRP => $qty 
                ) );
                $result = $quote->addProduct ( $product, $request );
                break;
            case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE :
                $request = new Varien_Object ( array (
                        static::SUPER_ATTR => $data [static::SUPER_ATTR],
                        static::OPTIONS => $data [static::CUSTOM_OPTION],
                        static::QTY => $qty 
                ) );
                $result = $quote->addProduct ( $product, $request );
                break;
            case Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE :
                $request = new Varien_Object ( array (
                        static::LINKS => explode ( ',', $data [static::LINKS] ),
                        static::OPTIONS => $data [static::CUSTOM_OPTION],
                        static::QTY => $qty 
                ) );
                $result = $quote->addProduct ( $product, $request );
                break;
            default :
                if (isset ( $data [static::CUSTOM_OPTION] )) {
                    $qty = new Varien_Object ( array (
                            static::OPTIONS => $data [static::CUSTOM_OPTION],
                            static::QTY => $qty 
                    ) );
                }
                $result = $quote->addProduct ( $product, $qty );
                break;
        }
        return $result;
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
        $productItem = '';
        switch ($type) {
            case Mage_Catalog_Model_Product_Type::TYPE_GROUPED :
                $productItem = new Varien_Object ( array (
                        static::SUPER_GRP => $qty 
                ) );
                $quote->addProduct ( $product, $request );
                break;
            case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE :
                if (isset ( $data [static::SUPER_ATTR] ) && $data [static::SUPER_ATTR] != '') {
                    $productItem = new Varien_Object ( array (
                            static::SUPER_ATTR => $data [static::SUPER_ATTR],
                            static::OPTIONS => $data [static::CUSTOM_OPTION],
                            static::QTY => $qty 
                    ) );
                }
                break;
            case Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE :
                if (isset ( $data [static::LINKS] ) && $data [static::LINKS] != '') {
                    $productItem = new Varien_Object ( array (
                            static::LINKS => explode ( ',', $data [static::LINKS] ),
                            static::OPTIONS => $data [static::CUSTOM_OPTION],
                            static::QTY => $qty 
                    ) );
                }
                break;
            default :
                if (isset ( $data [static::CUSTOM_OPTION] ) && $data [static::CUSTOM_OPTION] != '') {
                    $productItem = new Varien_Object ( array (
                            static::OPTIONS => $data [static::CUSTOM_OPTION],
                            static::QTY => $qty 
                    ) );
                } else {
                    $productItem = '';
                }
                break;
        }
        
        return $productItem;
    }
    
    /**
     * Delete product from cart
     *
     * @param object $productByItem            
     * @param object $quote            
     * @param int $qty            
     * @param string $custom_option            
     * @param string $super_attribute            
     * @return $productItem
     */
    public function deleteFromCart($productByItem, $quote, $qty, $custom_option, $super_attribute, $links) {
        $type = $productByItem->getTypeId ();
        $productItem = '';
        switch ($type) {
            case Mage_Catalog_Model_Product_Type::TYPE_GROUPED :
                $request = new Varien_Object ( array (
                        static::SUPER_GRP => $qty 
                ) );
                $quote->addProduct ( $product, $request );
                break;
            case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE :
                if (isset ( $super_attribute ) && $super_attribute != '') {
                    $productItem = new Varien_Object ( array (
                            static::SUPER_ATTR => json_decode ( $super_attribute, true ),
                            static::OPTIONS => json_decode ( $custom_option, true ) 
                    ) );
                }
                break;
            case Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE :
                if (isset ( $links ) && $links != '') {
                    $productItem = new Varien_Object ( array (
                            static::LINKS => explode ( ',', $links ),
                            static::OPTIONS => json_decode ( $custom_option, true ) 
                    ) );
                }
                break;
            default :
                if (isset ( $custom_option ) && $custom_option != '') {
                    $productItem = new Varien_Object ( array (
                            static::OPTIONS => json_decode ( $custom_option, true ) 
                    ) );
                } else {
                    $productItem = '';
                }
                break;
        }
        return $productItem;
    }
    
    /**
     * Delete product from cart
     *
     * @param object $quoteItem            
     * @param object $quote            
     * @return array $response
     */
    public function cartDelete($quoteItem, $quote) {
        $response = array ();
        if ($quoteItem != NULL) {
            if (is_null ( $quoteItem->getId () )) {
                $response [static::SUCCESS] = 0;
                $response [static::MESSAGE] = 'Item is not added in cart.';
            }
            // remove product from cart
            $quote->removeItem ( $quoteItem->getId () );
            // save quote address details
            $quote->getBillingAddress ();
            $quote->getShippingAddress ()->setCollectShippingRates ( TRUE );
            $quote->collectTotals ();
            $quote->save ();
            
            $response [static::SUCCESS] = 1;
            $response [static::MESSAGE] = 'Product deleted from cart successfully.';
        } else {
            $response [static::SUCCESS] = 0;
            $response [static::MESSAGE] = 'Item is not added in cart.';
        }
        
        return $response;
    }
    
    /**
     * Update product in cart
     *
     * @param object $quoteItem            
     * @param object $quote            
     * @return array $response
     */
    public function cartUpdate($quoteItem, $quote, $qty) {
        $response = array ();
        
        if (empty ( $quoteItem )) {
            $success = 0;
            $message = 'Item is not added in cart';
        } else {
            if ($qty > 0) {
                $quoteItem->setQty ( $qty );
            } else {
                $quote->removeItem ( $quoteItem->getId () );
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