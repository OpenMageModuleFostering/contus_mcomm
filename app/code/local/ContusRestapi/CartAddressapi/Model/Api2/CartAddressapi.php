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
}