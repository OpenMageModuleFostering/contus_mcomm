<?php

/**
 * Token table model initialization
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
* @package    ContusRestapi_Login
* @author     Contus Team <developers@contus.in>
* @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
* @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
* @since      1.0
*/
class ContusRestapi_Login_Model_Methods_Functions extends Mage_Core_Model_Abstract {
    const SLAES_QUOTE = 'sales/quote';
    const RESULT = 'result';
    const MESSAGE = 'message';
    const DETAILS = 'details';
    const TITLE = 'title';
    
    /**
     * Get active shipping methods and payment methods
     *
     * @param int $quoteId            
     * @param int $storeId            
     * @return array $response
     */
    public function getShippingPaymentMethods($quoteId, $storeId) {
        $response = array ();
        $shippings = array ();
        if ($quoteId) {
            try {
                $quote = Mage::getModel ( static::SLAES_QUOTE )->loadByIdWithoutStore ( $quoteId );
                $shippingMethods = Mage::getModel ( 'checkout/cart_shipping_api' )->getShippingMethodsList ( $quoteId, $storeId );
                $i = 0;
                foreach ( $shippingMethods as $shipping ) {
                    $shippings [$i] ['carrier'] = $shipping ['carrier'];
                    $shippings [$i] ['carrier_title'] = $shipping ['carrier_title'];
                    $shippings [$i] ['code'] = $shipping ['code'];
                    $shippings [$i] ['method'] = $shipping ['method'];
                    $shippings [$i] ['method_description'] = $shipping ['method_description'];
                    $shippings [$i] ['price'] = number_format ( $shipping ['price'], 2, '.', '' );
                    $shippings [$i] ['error_message'] = $shipping ['error_message'];
                    $shippings [$i] ['method_title'] = $shipping ['method_title'];
                    $shippings [$i] ['carrierName'] = $shipping ['carrierName'];
                    $i ++;
                }
                $payMentMethods = $this->getActivPaymentMethods ( $quote );
                $success = 1;
                $message = "Get shipping and payment methods successfully.";
            } catch ( Exception $e ) {
                $success = 0;
                $message = $e->getMessage ();
            }
        } else {
            $success = 0;
            $message = "Quote id not exist.";
        }
        $response ['success'] = $success;
        $response [static::MESSAGE] = $message;
        $response ['shipping_methods'] = $shippings;
        $response ['payment_methods'] = $payMentMethods;
        return $response;
    }
    
    /**
     * Get active payment methods
     *
     * @param object $quote            
     * @return $result
     */
    public function getActivPaymentMethods($quote) {
        $total = $quote->getBaseSubtotal ();
        $active_methods = Mage::getSingleton ( 'payment/config' )->getActiveMethods ();
        $result = array ();
        foreach ( $active_methods as $_code => $payment_model ) {
            
            if ($_code != 'free' && ($total) > 0) {
                $result [] = $this->getPayments ( $_code, $payment_model );
            } else if ($_code == 'free' && $total <= 0) {
                $_title = Mage::getStoreConfig ( 'payment/' . $_code . '/title' );
                $paymentMethods ['code'] = $_code;
                $paymentMethods [static::TITLE] = $_title;
                $paymentMethods ['ccTypes'] = array ();
                $result [] = $paymentMethods;
            } else {
                // no need this condition
            }
        }
        return $result;
    }
    public function getPayments($_code, $payment_model) {
        $_title = Mage::getStoreConfig ( 'payment/' . $_code . '/title' );
        if (strtoupper ( $_code ) == 'PAYPAL_EXPRESS' || strtoupper ( $_code ) == 'PAYPAL_DIRECT' || strtoupper ( $_code ) == 'PAYPAL_STANDARD' || strtoupper ( $_code ) == 'PAYPALUK_DIRECT' || strtoupper ( $_code ) == 'PAYPALUK_EXPRESS') {
            $paymentMethods ['code'] = 'paypal_standard';
        } else {
            $paymentMethods ['code'] = $_code;
        }
        
        $paymentMethods [static::TITLE] = $_title;
        
        $ccTypes = explode ( ',', $payment_model->getConfigData ( 'cctypes' ) );
        $aType = Mage::getSingleton ( 'payment/config' )->getCcTypes ();
        $cc = array ();
        $i = 0;
        foreach ( $ccTypes as $cctype ) {
            if ($cctype != '') {
                $cc [$i] ['card_code'] = $cctype;
                $cc [$i] ['card_name'] = $aType [$cctype];
                
                $i ++;
            }
        }
        $paymentMethods ['ccTypes'] = $cc;
        
        return $paymentMethods;
    }
    
    /**
     * Update device token and type in token table
     *
     * @param int $customerId            
     * @param string $deviceToken            
     * @param string $deviceType            
     */
    public function updateDeviceToken($customerId, $deviceToken, $deviceType) {
        $tokenObj = Mage::getModel ( 'login/token' )->load ( $customerId, 'userid' );
        $tokenObj->setUserid ( $customerId );
        $tokenObj->setDevicetoken ( $deviceToken );
        $tokenObj->setDevicetype ( $deviceType );
        $tokenObj->save ();
    }
    
 
}