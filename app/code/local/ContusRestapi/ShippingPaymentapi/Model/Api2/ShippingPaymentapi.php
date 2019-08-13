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
 * @package    ContusRestapi_ShippingPaymentapi
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
 * @since      1.0
 */
class ContusRestapi_ShippingPaymentapi_Model_Api2_ShippingPaymentapi extends Mage_Api2_Model_Resource {
    
    // define static variables
    const MESSAGE = 'message';
    const SUCCESS = 'success';
    /**
     * Add Ahipping and Payment Methods to cart quote
     *
     * @return array json array
     */
    protected function _create(array $data) {
        $response = array ();
        
        // get website id
        $websiteId = ( int ) $data ['website_id'];
        if ($websiteId <= 0) {
            $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
        }      
        // get customer id
        $customerId = ( int ) $data ['customer_id'];       
        // get quote id
        $quoteId = ( int ) $data ['quote_id'];
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
        if ($quoteId) {
            
            try {
                
                $quote = Mage::getModel ( 'sales/quote' );
                $quote->setStoreId ( $storeId )->load ( $quoteId );
                $active = $quote->getIsActive ();
                if (! $active) {
                    throw new Exception ( 'Quote is invalid!.' );
                }
                
                if (! $isValidToken) {
                    throw new Exception ( 'Authentication failed.' );
                }
                // set shipping method
                
                $quote->getShippingAddress ()->setShippingMethod ( $data ['shipping_method'] );
                $quote->getShippingAddress ()->setCollectShippingRates ( TRUE );
                $quote->getShippingAddress ()->collectShippingRates ();
                
                // Set Payment method
                // Mage_Sales_Model_Quote_Payment
                $quotePaymentObj = $quote->getPayment ();
                $quotePaymentObj->setMethod ( $data ['payment_method'] );
                $quote->setPayment ( $quotePaymentObj );
                
                $quote->collectTotals ();
                $quote->save ();
                $response [static::SUCCESS] = 1;
                $response [static::MESSAGE] = 'Shipping and payment methods added successfully.';
            } catch ( exception $e ) {
                $response [static::SUCCESS] = 0;
                $response [static::MESSAGE] = $e->getMessage ();
            }
        } else {
            $response [static::SUCCESS] = 0;
            $response [static::MESSAGE] = "Quote id not exist.";
        }
        $response ['isValidToken'] = $isValidToken;
        $response ['error'] = false;
        $this->getResponse ()->setBody ( json_encode ( $response ) );
        return;
    }
}