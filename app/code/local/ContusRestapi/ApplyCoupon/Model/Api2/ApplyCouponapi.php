<?php
/**
 * Contus
 * 
 *  Apply Coupon API
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
 * @package    ContusRestapi_ApplyCoupon
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
 * @since      1.0
 */
class ContusRestapi_ApplyCoupon_Model_Api2_ApplyCouponapi extends Mage_Api2_Model_Resource {
    
    // define static variable
    const STOREID = 'store_id';
    const WEBSITE_ID = 'website_id';
    const CUSTOMER_ID = 'customer_id';
    const COUPON_CODE = 'coupon_code';
    const QUOTE_ID = 'quote_id';
    const SHIPPING_AMT = 'shipping_amount';
    const TAX = 'tax';
    const DISCOUNT = 'discount';
    const SALSE_QUOTE = 'sales/quote';
    const SUCCESS = 'success';
    const MESSAGE = 'message';
    const RESULT = 'result';
    const VALID_COUPON = 'valid_coupon';
    const ERR_MSG = 'Coupon code is not valid.';
    
    /**
     * function that is called when post is done **
     * Apply coupon code to cart
     *
     * @param array $data            
     * @return array json array
     */
    protected function _create(array $data) {
        $response = array ();
        
        $websiteId = ( int ) $data [static::WEBSITE_ID];
        if ($websiteId <= 0) {
            $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
        }
        // get quote id from request
        $quoteId = ( int ) $data [static::QUOTE_ID];
        // get customer id
        $customerId = ( int ) $data [static::CUSTOMER_ID];
        // get website id from request
        $storeId = ( int ) $data [static::STOREID];
        if ($storeId <= 0) {
            $storeId = Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
        }
        
        // load customer by email id
        $customer = Mage::getModel ( "customer/customer" );
        $customer->load ( $customerId );
        $customerGroupId = $customer->getGroupId ();
        
        $quote = Mage::getModel ( static::SALSE_QUOTE );
        $quote->setStoreId ( $storeId )->load ( $quoteId );
        $cartCount = $quote->getItemsCount ();
        $couponCode = $data [static::COUPON_CODE];
        $oCoupon = Mage::getModel ( 'salesrule/coupon' )->load ( $couponCode, 'code' );
        $oRule = Mage::getModel ( 'salesrule/rule' )->load ( $oCoupon->getRuleId () );
        $couponDetails = $oRule->getData ();
        // check coupon code valid for this quote
        $result = $this->validateCoupon ( $couponDetails, $customerGroupId, $websiteId, $cartCount );
        
        /**
         * Check this customer has valid token or not
         *
         * @var $isValidToken Mage_Login_Model_Token
         */
        
        $isValidToken = Mage::getModel ( 'login/token' )->checkUserToken ( $customerId, $data ['token'] );
        if (! $isValidToken) {
            $result [static::VALID_COUPON] = FALSE;
            $result [static::MESSAGE] = 'Authentication failed.';
        }
       
        if ($result [static::VALID_COUPON]) {
            
            $oldCouponCode = $quote->getCouponCode ();
            if (! strlen ( $couponCode ) && ! strlen ( $oldCouponCode )) {
                $success = 0;
                $message = static::ERR_MSG;
            }
            
            try {
                $quote->getShippingAddress ()->setCollectShippingRates ( true );
                $quote->setCouponCode ( strlen ( $couponCode ) ? $couponCode : '' )->collectTotals ()->save ();
                $success = 1;
                $message = 'Coupon code was applied.';
               
            } catch ( Exception $e ) {
                $success = 0;
                $message = $e->getMessage ();
            }
            
            if (! $couponCode == $quote->getCouponCode ()) {
                $success = 0;
                $message = static::ERR_MSG;
            }
        } else {
            $success = 0;
            $message = $result [static::MESSAGE];
        }
        
        $cartproducts = $this->getCartAmountDetails ( array (
                static::STOREID => $storeId,
                static::CUSTOMER_ID => $customerId
        ) );
        $response [static::RESULT] = $cartproducts [static::RESULT];
        
        $response ['isValidToken'] = $isValidToken;
        $response ['error'] = false;
        $response [static::SUCCESS] = $success;
        $response [static::MESSAGE] = $message;
        $this->getResponse ()->setBody ( json_encode ( $response ) );
        return;
    }
    
    /**
     * Cancel Coupon code from cart
     *
     * @param array $data            
     * @return array json array
     */
    protected function _update(array $data) {
        $response = array ();
        // get website id
        $websiteId = ( int ) $data [static::WEBSITE_ID];
        if ($websiteId <= 0) {
            $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
        }
        // get website id from request
        $storeId = ( int ) $data [static::STOREID];
        if ($storeId <= 0) {
            $storeId = Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
        }
        // get quote id from request
        $quoteId = ( int ) $data [static::QUOTE_ID];
        // get customer id
        $customerId = ( int ) $data [static::CUSTOMER_ID];
        
        Mage::helper ( 'checkout/cart' )->getQuote ()->setData ( static::COUPON_CODE, '' )->save ();
        $quote = Mage::getModel ( static::SALSE_QUOTE );
        $quote->setStoreId ( $storeId )->load ( $quoteId );
        
        /**
         * Check this customer has valid token or not
         *
         * @var $isValidToken Mage_Login_Model_Token
         */
        $isValidToken = Mage::getModel ( 'login/token' )->checkUserToken ( $customerId, $data ['token'] );
        if ($isValidToken) {
            try {
                $quote->setData ( static::COUPON_CODE, '' )->collectTotals ()->save ();
                $success = 1;
                $message = 'Coupon code was canceled.';
                $cartproducts = $this->getCartAmountDetails ( array (
                        static::STOREID => $storeId,
                        static::CUSTOMER_ID => $customerId 
                ) );
                $response [static::RESULT] = $cartproducts [static::RESULT];
            } catch ( Exception $e ) {
                $success = 0;
                $message = $e->getMessage ();
            }
        } else {
            $success = 0;
            $message = 'Authentication failed.';
        }
        $response ['isValidToken'] = $isValidToken;
        $response ['error'] = false;
        $response [static::SUCCESS] = $success;
        $response [static::MESSAGE] = $message;
        $this->getResponse ()->setBody ( json_encode ( $response ) );
        return;
    }
    
    /**
     * Get cart amount details
     *
     * @param array $data            
     * @return mixed array $response
     */
    public function getCartAmountDetails($data) {
        
        // get store_id
        $storeId = $data [static::STOREID];
        // get customer id
        $customerId = $data [static::CUSTOMER_ID];
        
        $quote = Mage::getModel ( static::SALSE_QUOTE )->getCollection ()->addFieldToFilter ( static::CUSTOMER_ID, $customerId )->addFieldToFilter ( static::STOREID, $storeId )->addFieldToFilter ( 'is_active', '1' )->setOrder ( 'entity_id', 'desc' );
        $quoteData = $quote->getData ();
        $quoteId = $quoteData [0] ['entity_id'];
        $quote = $this->_getQuote ( $quoteId, $storeId );
        $totals = $quote->getTotals ();
        if (isset ( $totals [static::DISCOUNT] )) {
            $discount = number_format ( abs ( $totals [static::DISCOUNT]->getValue () ), 2, '.', '' );
        } else {
            $discount = '';
        }
        
        $returnArray = array ();
        
        if (isset ( $quoteData ) && ! empty ( $quoteData )) {
            $itemCount = $quoteData [0] ['items_count'];
            if ($itemCount > 0) {
                $returnArray ['item_count'] = $itemCount;
                $returnArray ['grand_total'] = number_format ( $quoteData [0] ['grand_total'], 2, '.', '' );
                $returnArray ['subtotal'] = number_format ( $quoteData [0] ['subtotal'], 2, '.', '' );
                $returnArray [static::DISCOUNT] = $discount;
                $returnArray [static::QUOTE_ID] = $quoteId;
                $returnArray [static::COUPON_CODE] = $quoteData [0] [static::COUPON_CODE];
                $addressobj = $quote->getShippingAddress ();
                $returnArray [static::SHIPPING_AMT] = 0;
                $returnArray [static::TAX] = 0;
                if ($addressobj) {
                    $returnArray [static::SHIPPING_AMT] = $quote->getShippingAddress ()->getData ( static::SHIPPING_AMT );
                    $returnArray [static::TAX] = $quote->getShippingAddress ()->getData ( 'tax_amount' );
                    
                    $returnArray [static::SHIPPING_AMT] = number_format ( $returnArray [static::SHIPPING_AMT], 2, '.', '' );
                    $returnArray [static::TAX] = number_format ( $returnArray [static::TAX], 2, '.', '' );
                }
                
                $success = 1;
                $message = 'Quote fetched successfully.';
            } else {
                
                $success = 0;
                $message = 'No items in your cart.';
            }
        } else {
            
            $success = 0;
            $message = 'No items in your cart.';
        }
        $response [static::RESULT] = $returnArray;
        $response [static::SUCCESS] = $success;
        $response [static::MESSAGE] = $message;
        return $response;
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
        $quote = Mage::getModel ( static::SALSE_QUOTE );
        $quote->setStoreId ( $storeId )->load ( $quoteId );
        return $quote;
    }
    
    /**
     * Check given coupon valid or not
     *
     * @param array $couponDetails            
     * @param int $customerGroupId            
     * @param int $websiteId            
     * @return array $result
     */
    public function validateCoupon($couponDetails, $customerGroupId, $websiteId, $cartCount) {
        $apply_coupon = TRUE;
        $errorMsg = '';
        if ($cartCount <= 0) {
            $apply_coupon = FALSE;
            $errorMsg = 'Cart is Empty';
        }
        // check coupon code is active
        if ($couponDetails [static::COUPON_CODE] == '') {
            $apply_coupon = FALSE;
            $errorMsg = 'Coupon code is wrong.';
        }
        // check coupon code is active
        if ($couponDetails ['is_active'] != 1) {
            $apply_coupon = FALSE;
            $errorMsg = static::ERR_MSG;
        }
        // check coupon is available for customer group
        if (! in_array ( $customerGroupId, $couponDetails ['customer_group_ids'] )) {
            $apply_coupon = FALSE;
            $errorMsg = static::ERR_MSG;
        }
        // check coupon is available for website
        if (! in_array ( $websiteId, $couponDetails ['website_ids'] )) {
            $apply_coupon = FALSE;
            $errorMsg = static::ERR_MSG;
        }
        // check coupon expiry date
        // Get today date
        $today_date = Date ( 'Y-m-d' );
        
        if (($couponDetails ['from_date'] != '' && $today_date <= $couponDetails ['from_date']) || ($couponDetails ['to_date'] != '' && $today_date >= $couponDetails ['to_date'])) {
            $apply_coupon = FALSE;
            $errorMsg = 'Coupon code is expired.';
        }
        
        $result [static::VALID_COUPON] = $apply_coupon;
        $result [static::MESSAGE] = $errorMsg;
        return $result;
    }
}