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
 * @package    ContusRestapi_SocialLogin
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
 * @since      1.0
 */
class ContusRestapi_SocialLogin_Model_Api2_SocialLogin extends Mage_Api2_Model_Resource {
    const FIRSTNAME = 'firstname';
    const LASTNAME = 'lastname';
    const LOGIN_TOKEN = 'login/token';
    const RESULT = 'result';
    const ENTITY_ID = 'entity_id';
    
    /**
     * function that is called when post is done **
     *
     * Register or login via Social networks
     *
     * @param array $data            
     *
     * @return array json array
     */
    protected function _create(array $data) {
        $response = array ();
        // get newsletter value for customer
        if ($data ['newsletter']) {
            $newsletterid = $data ['newsletter'];
        } else {
            $newsletterid = 0;
        }
        // get website id
        if ($data ['website_id']) {
            $websiteId = $data ['website_id'];
        } else {
            $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
        }
        // get store id
        if ($data ['store_id']) {
            $storeId = $data ['store_id'];
        } else {
            $storeId = Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
        }
        $dob = $data ['dob'];
        // set currnet store id
        Mage::app ()->setCurrentStore ( $storeId );
        $email = $data ['email'];
        // getting the device token
        $deviceToken = $data ['device_token'];
        // getting the device type
        $deviceType = $data ['device_type'];
        
        /**
         *
         * @var $customer Mage_Customer_Model_Customer
         */
        $customer = Mage::getModel ( 'customer/customer' );
        $customer->setWebsiteId ( $websiteId );
        $customer->loadByEmail ( $email );
        if ($customer->getId ()) {
            try {
                
                $response [static::RESULT] = Mage::getModel ( static::LOGIN_TOKEN )->getCustomerDetail ( $customer->getId () );
                $response [static::RESULT] ['token'] = $this->getToken ( $customer->getId () );
                $result [static::RESULT] ['cart_count'] = Mage::getModel ( static::LOGIN_TOKEN )->getCartCount ( $customer->getId (), $storeId );
                // Update device token and type in token table
                Mage::getModel ( 'login/methods_functions' )->updateDeviceToken ( $customer->getId (), $deviceToken, $deviceType );
                
                $message = 'You have successfully logged in.';
                $success = 1;
            } catch ( Exception $e ) {
                $message = $e->getMessage ();
                $success = 0;
            }
        } else {
            
            try {
                // If customer email is not found, create as a new customer
                // create password for customer
                $password = $this->generatePassword ( 6 );
                $customer->setEmail ( $email );
                $customer->setFirstname ( $data [static::FIRSTNAME] );
                $customer->setLastname ( $data [static::LASTNAME] );
                $customer->setPassword ( $password );
                $customer->setWebsiteId ( $websiteId );
                $customer->setStoreId ( $storeId );
                $customer->setDob ( $dob );
                // set newsletter subscription for customer
                $customer->setIsSubscribed ( $newsletterid );
                
                $customer->save ();
                // get customer data
                $customerData = $customer->getData ();
                
                $response [static::RESULT] = Mage::getModel ( static::LOGIN_TOKEN )->getCustomerDetail ( $customerData [static::ENTITY_ID] );
                $response [static::RESULT] ['token'] = $this->getToken ( $customer->getId () );
                $result [static::RESULT] ['cart_count'] = Mage::getModel ( static::LOGIN_TOKEN )->getCartCount ( $customerData [static::ENTITY_ID], $storeId );
                // Update device token and type in token table
                Mage::getModel ( 'login/methods_functions' )->updateDeviceToken ( $customerData [static::ENTITY_ID], $deviceToken, $deviceType );
                
                $message = 'You have successfully logged in.';
                $success = 1;
                // send confirmation email to customer
                $customer->sendNewAccountEmail ();
            } catch ( Exception $e ) {
                $message = $e->getMessage ();
                $success = 0;
            }
        }
        $response ['error'] = false;
        $response ['success'] = $success;
        $response ["message"] = $message;
        $this->getResponse ()->setBody ( json_encode ( $response ) );
        return;
    }
    
    /**
     * Generate random password for customers login by Social network like facebook,twiter,etc,
     *
     * @param string $length
     *            Length for password
     *            
     * @return string $password as password
     */
    public function generatePassword($length) {
        $chars = "1234567890abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $i = 0;
        $password = "";
        while ( $i <= $length ) {
            $password .= $chars {mt_rand ( 0, strlen ( $chars ) )};
            $i ++;
        }
        return $password;
    }
    
    /**
     * create token and assign to customer
     *
     * @param int $customerId            
     *
     * @return string $tokenvalue
     */
    public function getToken($customerId) {
        try {
            $tokenvalue = Mage::getModel ( static::LOGIN_TOKEN )->getRandomString ( 6 );
            $tokenObj = Mage::getModel ( static::LOGIN_TOKEN )->load ( $customerId, 'userid' );
            $tokenObj->setUserid ( $customerId );
            $tokenObj->setToken ( $tokenvalue );
            $tokenObj->setCreated ( date ( 'Y-m-d H:i:s' ) );
            $tokenObj->setStatus ( 1 );
            $tokenObj->save ();
        } catch ( Exception $e ) {
            $tokenvalue = $e->getMessage ();
        }
        return $tokenvalue;
    }
}