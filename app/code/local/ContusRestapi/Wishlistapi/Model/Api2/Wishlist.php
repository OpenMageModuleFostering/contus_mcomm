<?php
/**
 * Contus
 * 
 *  Wishlist Products 
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
 * @package    ContusRestapi_Wishlistapi
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
 * @since      1.0
 */
class ContusRestapi_Wishlistapi_Model_Api2_Wishlist extends Mage_Api2_Model_Resource {
    
    // define static variable.
    const STOREID = 'store_id';
    const WEBSITEID = 'website_id';
    const CUSTOMER_ID = 'customer_id';
    const ADDED_AT = 'added_at';
    const PRODUCT_ID = 'product_id';
    const IS_SALABLE = 'is_saleable';
    const QTY = 'qty';
    const SUCCESS = 'success';
    const MESSAGE = 'message';
    const ERROR = 'error';
    const RESULT = 'result';
    const TOKEN = 'token';
    const LOGIN_TOKEN = 'login/token';
    const VALID_TOKEN = 'isValidToken';
    const AUTH_FAIL = 'Authentication failed.';
    const TOTAL_COUNT = 'total_count';
    const WISHLIST_ITEM = 'wishlist/item';
    
    /**
     * Add product to customer wishlist
     *
     * @param array $data            
     * @return array json array
     */
    protected function _create(array $data) {
        $response = array ();
        
        $websiteId = ( int ) $data [static::WEBSITEID];
        if ($websiteId <= 0) {
            $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
        }
        // get store id from request
        $storeId = ( int ) $data [static::STOREID];
        if ($storeId <= 0) {
            Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
        }
        // get customer id
        $customerId = ( int ) $data [static::CUSTOMER_ID];
        // get product Id from request
        $productId = ( int ) $data [static::PRODUCT_ID];
        
        // get token value from request
        $token = $data [static::TOKEN];
        $qty = ( int ) $data [static::QTY];
        if ($qty <= 0) {
            $qty = 1;
        }
        
        /**
         * Check this customer has valid token or not
         *
         * @var $isValidToken Mage_Login_Model_Token
         */
        $isValidToken = Mage::getModel ( static::LOGIN_TOKEN )->checkUserToken ( $customerId, $token );
        
        if ($isValidToken) {
            
            Mage::app ()->setCurrentStore ( $storeId );
            // load wishlist model by customer id
            $wishlist = Mage::getModel ( 'wishlist/wishlist' )->loadByCustomer ( $customerId, true );
            // load product model by product id
            $product = Mage::getModel ( 'catalog/product' )->load ( $productId );
            // any possible options that are configurable and you want to save with the product
            $requestParams = array (
                    'product' => $productId,
                    static::QTY => $qty,
                    static::STOREID => $storeId 
            );
            $buyRequest = new Varien_Object ( $requestParams );
            
            try {
                $wishlist->addNewItem ( $product, $buyRequest );
                $wishlist->save ();
                if ($wishlist->save ()) {
                    $success = 1;
                    $add_message = "Product added to wishlist successfully.";
                } else {
                    $success = 0;
                    $add_message = "Sorry, we're unable to add the product to wishlist.";
                }
            } catch ( Exception $e ) {
                $success = 0;
                $add_message = $e->getMessage ();
            }
        } else {
            $success = 0;
            $add_message = static::AUTH_FAIL;
        }
        $response [static::VALID_TOKEN] = $isValidToken;
        $response [static::ERROR] = false;
        $response [static::SUCCESS] = $success;
        $response [static::MESSAGE] = $add_message;
        $this->getResponse ()->setBody ( json_encode ( $response ) );
        return;
    }
    
    /**
     * Retrieve Wishlist products collection
     *
     * @param integer $customerId            
     * @return array json array
     */
    protected function _retrieveCollection() {
        $response = array ();
        
        $customerId = ( int ) $this->getRequest ()->getParam ( static::CUSTOMER_ID );
        // get website id from request
        $websiteId = ( int ) $this->getRequest ()->getParam ( static::WEBSITEID );
        // get store id from request
        $storeId = ( int ) $this->getRequest ()->getParam ( static::STOREID );
        if ($websiteId <= 0) {
            $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
        }
        if ($storeId <= 0) {
            Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
        }
        
        // get page no
        $page = ( int ) $this->getRequest ()->getParam ( 'page' );
        if ($page <= 0) {
            $page = 1;
        }
        // get page limit
        $limit = ( int ) $this->getRequest ()->getParam ( 'limit' );
        if ($limit <= 0) {
            $limit = 20;
        }
        // get image size for resize
        $imageSize = ( int ) $this->getRequest ()->getParam ( 'image_size' );
        
        /**
         * Check this customer has valid token or not
         *
         * @var $isValidToken Mage_Login_Model_Token
         */
        $token = $this->getRequest ()->getParam ( static::TOKEN );
        $isValidToken = Mage::getModel ( static::LOGIN_TOKEN )->checkUserToken ( $customerId, $token );
        if ($isValidToken) {
            
            $wishlistProducts = $this->getWishlistProducts ( $storeId, $customerId, $page, $limit, $imageSize );
            $result = $wishlistProducts [static::RESULT];
            $totalCount = $wishlistProducts [static::TOTAL_COUNT];
            $response [static::SUCCESS] = 1;
            $response [static::ERROR] = false;
        } else {
            $result = array ();
            $response [static::ERROR] = true;
            $response [static::SUCCESS] = 0;
            $totalCount = 0;
            $response [static::MESSAGE] = static::AUTH_FAIL;
        }
        $response [static::VALID_TOKEN] = $isValidToken;
        $response [static::TOTAL_COUNT] = $totalCount;
        $response [static::RESULT] = $result;
        return json_encode ( $response );
    }
    
    /**
     * Get wislist products by customer account.
     *
     * @param integer $storeId            
     * @param int $customerId            
     * @param int $page            
     * @param int $limit            
     * @return array result
     */
    public function getWishlistProducts($storeId, $customerId, $page, $limit, $imageSize) {
        $result = array ();
        $products = array ();
        
        // get wishlist item collection based on customer id
        $itemCollection = Mage::getModel ( static::WISHLIST_ITEM )->getCollection ()->addCustomerIdFilter ( $customerId );
        
        // set items on descending order based on prodcut added to wishlist date
        $itemCollection = $itemCollection->setOrder ( static::ADDED_AT, 'DESC' );
        // get total products count
        $totalProducts = $itemCollection->getSize ();
        // set page
        $itemCollection = $itemCollection->setCurPage ( $page );
        // 10 elements per pages
        $itemCollection = $itemCollection->setPageSize ( $limit );
        // get total pages with limit
        $last_page = ceil ( $totalProducts / $limit );
        if ($last_page < $page) {
            $result [static::TOTAL_COUNT] = 0;
            $result ['result'] = array ();
        } else {
            $i = 0;
            foreach ( $itemCollection as $item ) {
                $_product = Mage::getModel ( 'catalog/product' )->load ( $item ['product_id'] );
                $_product->setStoreId ( $storeId )->load ( $_product->getId () );
                
                // get only enabled products
                if ($_product->getId () != '') {
                    // get product id
                    $products [$i] ['entity_id'] = $_product->getId ();
                    $products [$i] ['type_id'] = $_product->getTypeId ();
                    // get product name
                    $products [$i] ['name'] = $_product->getName ();
                    // get product small image url
                    $products [$i] ['image_url'] = $this->resizeImage ( $_product, $imageSize );
                    
                    // get the product final price
                    $products [$i] ['regular_price_with_tax'] = number_format ( $_product->getPrice (), 2, '.', '' );
                    $products [$i] ['final_price_with_tax'] = number_format ( $_product->getFinalPrice (), 2, '.', '' );
                    
                    $stockDetail = Mage::getModel ( static::LOGIN_TOKEN )->getStockDetail ( $_product );
                    $products [$i] [static::IS_SALABLE] = $stockDetail [static::IS_SALABLE];
                    $products [$i] ['is_stock'] = $stockDetail ['is_stock'];
                    
                    $products [$i] ['summary_rating'] = Mage::getModel ( static::LOGIN_TOKEN )->rateSummary ( $_product->getId (), $storeId ) ? Mage::getModel ( static::LOGIN_TOKEN )->rateSummary ( $_product->getId (), $storeId ) : '0';
                    
                    // get prodcut added date
                    $products [$i] ['added_date'] = date ( "d-m-Y", strtotime ( $item [static::ADDED_AT] ) );
                    
                    $i ++;
                }
            }
            $result [static::TOTAL_COUNT] = $totalProducts;
            $result [static::RESULT] = $products;
        }
        return $result;
    }
    
    /**
     * Products Rating
     *
     * @param int $id
     *            Getting the particular product id
     *            
     * @return array as json message as string and success rate count
     */
    public function rateSummary($id) {
        // getting rate model
        $summaryData = Mage::getModel ( 'review/review_summary' )->load ( $id )->getRatingSummary ();
        // calculate overage for ratings
        return $summaryData / 20;
    }
    
    /**
     * Remove product from cstomer wishlist.
     *
     * @param integer $productId            
     * @param int $customerId            
     * @return array result
     */
    protected function _delete() {
        
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
        
        $response = array ();
        // get wishlist item collection based on customer id
        $itemCollection = Mage::getModel ( static::WISHLIST_ITEM )->getCollection ()->addCustomerIdFilter ( $customerId );
        $itemCollection = $itemCollection->setOrder ( static::ADDED_AT, 'DESC' );
        
        /**
         * Check this customer has valid token or not
         *
         * @var $isValidToken Mage_Login_Model_Token
         */
        $isValidToken = Mage::getModel ( static::LOGIN_TOKEN )->checkUserToken ( $customerId, $this->getRequest ()->getParam ( static::TOKEN ) );
        if ($isValidToken) {
            try {
                $result = $this->deleteWishlist ( $itemCollection, $productId );
                
                $success = $result [static::SUCCESS];
                $message = $result [static::MESSAGE];
            } catch ( Exception $e ) {
                $success = 0;
                $message = "Sorry, we're unable to remove the product from wishlist.";
            }
        } else {
            $success = 0;
            $message = static::AUTH_FAIL;
        }
        $response [static::VALID_TOKEN] = $isValidToken;
        $response [static::ERROR] = false;
        $response [static::SUCCESS] = $success;
        $response [static::MESSAGE] = $message;
        
        $this->getResponse ()->setBody ( json_encode ( $response ) );
        return;
    }
    
    /**
     * Delete Product from wishlist collection
     *
     * @param object $item            
     * @param int $itemId            
     * @param int $productId            
     * @return array :number string
     */
    public function deleteWishlist($itemCollection, $productId) {
        $result = array ();
        $delete = false;
        foreach ( $itemCollection as $item ) {
            
            if ($item->getProduct ()->getId () == $productId) {
                // delete product from wishlist
                $item->delete ();
                if ($item->delete ()) {
                    $success = 1;
                    $message = "Product deleted from wishlist successfully.";
                } else {
                    $success = 0;
                    $message = "Sorry, we're unable to remove the product from wishlist.";
                }
                $delete = true;
            }
        }
        if (! $delete) {
            $success = 0;
            $message = "Item is not available. ";
        }
        $result [static::SUCCESS] = $success;
        $result [static::MESSAGE] = $message;
        return $result;
    }
    
    /**
     * Resize product image
     *
     * @param object $_product            
     * @param int $imageSize            
     * @return string $image
     */
    public function resizeImage($_product, $imageSize) {
        if ($imageSize <= 0) {
            $image = ( string ) Mage::helper ( 'catalog/image' )->init ( $_product, 'small_image' );
        } else {
            $image = ( string ) Mage::helper ( 'catalog/image' )->init ( $_product, 'small_image' )->constrainOnly ( TRUE )->keepAspectRatio ( TRUE )->keepFrame ( FALSE )->resize ( $imageSize, null );
        }
        return $image;
    }
    
    /**
     * function that is called when put is done **
     * Remove all products from customer wishlist
     *
     * @param array $data            
     * @return array json array
     */
    protected function _update(array $data) {
        $response = array ();
       
        $websiteId = ( int ) $data [static::WEBSITEID];
        if ($websiteId <= 0) {
            $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
        }
        // get store id from request
        $storeId = ( int ) $data [static::STOREID];
        if ($storeId <= 0) {
            Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
        }
        // get token value from request
        $token = $data [static::TOKEN];
        // get customer id
        $customerId = ( int ) $data [static::CUSTOMER_ID];
       
        /**
         * Check this customer has valid token or not
         *
         * @var $isValidToken Mage_Login_Model_Token
         */
        $isValidToken = Mage::getModel ( static::LOGIN_TOKEN )->checkUserToken ( $customerId, $token );
        
        // get wishlist item collection based on customer id
        $itemCollection = Mage::getModel ( static::WISHLIST_ITEM )->getCollection ()->addCustomerIdFilter ( $customerId );
        $itemCollection = $itemCollection->setOrder ( static::ADDED_AT, 'DESC' );
        if(! is_array($itemCollection)){
            $success = 0;
            $message = 'There are no products in your wishlist.';
        }
        try {
            if (! $isValidToken) {
                throw new Exception ( static::AUTH_FAIL );
            }
            foreach ( $itemCollection as $item ) {
                
                // delete product from wishlist
                $item->delete ();
                if ($item->delete ()) {
                    $success = 1;
                    $message = "Products deleted from wishlist successfully.";
                } else {
                    $success = 0;
                    $message = "Sorry, we're unable to remove the products from wishlist.";
                }
            }
        } catch ( Exception $e ) {
            $success = 0;
            $message = $e->getMessage ();
        }
        
        $response [static::VALID_TOKEN] = $isValidToken;
        $response [static::ERROR] = false;
        $response [static::SUCCESS] = $success;
        $response [static::MESSAGE] = $message;
        
        $this->getResponse ()->setBody ( json_encode ( $response ) );
        
        return;
    }
}