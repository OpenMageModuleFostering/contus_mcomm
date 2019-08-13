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
 * @package    ContusRestapi_Login
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
 * @since      1.0
 */
class ContusRestapi_Registration_Model_Api2_Registration_Rest extends Mage_Customer_Model_Api2_Customer_Rest {
    const STOREID = 'store_id';
    const PASSWORD = 'password';
    const WEBSITEID = 'website_id';
    const FIRSTNAME = 'firstname';
    const LASTNAME = 'lastname';
    const NEWSLETTER = 'newsletter';
    const ENTITYID = 'entity_id';
    const CUSTOMERID = 'customer_id';
    const EMAIL = 'email';
    const SUCCESS = 'success';
    const MESSAGE = 'message';
    const TOKEN = 'token';
    const LOGIN_TOKEN = 'login/token';
    const VALID_TOKEN = 'isValidToken';
    const ERROR = 'error';
    const RESULT = 'result';
    
    /**
     * function that is called when post is done **
     *
     * Customer Registration
     *
     * @param array $data            
     *
     * @return array json array
     */
    protected function _create(array $data) {
        $response = array ();
        $is_error = 0;
        // getting the device token
        $deviceToken = $data ['device_token'];
        // getting the device type
        $deviceType = $data ['device_type'];
        $params = $this->getParams ( $data );
        $newsletterid = $params [static::NEWSLETTER];
        $websiteid = $params [static::WEBSITEID];
        $storeId = $params [static::STOREID];
       
        /**
         *
         * @var $validator Mage_Api2_Model_Resource_Validator_Eav
         */
        $validator = Mage::getResourceModel ( 'api2/validator_eav', array (
                'resource' => $this 
        ) );
        $passArray = array ();
        if (array_key_exists ( static::PASSWORD, $data )) {
            $passArray = $data;
        }
        
        $data = $validator->filter ( $data );
        if (! $validator->isValidData ( $data )) {
            foreach ( $validator->getErrors () as $error ) {
                $error_message = $error;
                $is_error = 1;
            }
        }
        
        /**
         *
         * @var $customer Mage_Customer_Model_Customer
         */
        $customer = Mage::getModel ( 'customer/customer' );
        $customer->setData ( $data );
        if (array_key_exists ( static::PASSWORD, $passArray )) {
            $customer->setPassword ( $passArray [static::PASSWORD] );
            $customer->setConfirmation ( null );
            // set newsletter subscription for customer
            $customer->setIsSubscribed ( $newsletterid );
        }
        
        try {
            
            if ($is_error) {
                throw new Exception ( $error_message );
            }
            // set customer website id
            $customer->setWebsiteId ( $websiteid );
            // set store id for customer
            $customer->setStoreId ( $storeId );
            $customer->save ();
            $customer->sendNewAccountEmail ();
            // get customer data
            $customerData = $customer->getData ();
            $success = 1;
            $register_message = 'You are successfully logged in.';
            
            $tokenvalue = Mage::getModel ( static::LOGIN_TOKEN )->getRandomString ( 6 );
            $tokenObj = Mage::getModel ( static::LOGIN_TOKEN )->load ( $customerData [static::ENTITYID], 'userid' );
            $tokenObj->setUserid ( $customerData [static::ENTITYID] );
            $tokenObj->setToken ( $tokenvalue );
            $tokenObj->setCreated ( date ( 'Y-m-d H:i:s' ) );
            $tokenObj->setStatus ( 1 );
            $tokenObj->save ();
            //Update device token and type in token table
            Mage::getModel ( 'login/methods_functions' )->updateDeviceToken ( $customerData [static::ENTITYID], $deviceToken, $deviceType );
            
            $response [static::RESULT] = Mage::getModel ( static::LOGIN_TOKEN )->getCustomerDetail ( $customerData [static::ENTITYID] );
            $response [static::RESULT] [static::TOKEN] = $tokenvalue;
            $response [static::RESULT] ['cart_count'] = 0;
        } catch ( Mage_Core_Exception $e ) {
            $success = 0;
            $register_message = $e->getMessage ();
        } catch ( Exception $e ) {
            $success = 0;
            $register_message = $e->getMessage ();
        }
        $response [static::ERROR] = false;
        $response [static::SUCCESS] = $success;
        $response [static::MESSAGE] = $register_message;
        $this->getResponse ()->setBody ( json_encode ( $response ) );
        return;
    }
    
    /**
     * function that is called when post is done **
     *
     * Customer Registration
     *
     * @param array $data            
     *
     * @return array json array
     */
    protected function _update(array $data) {
        $response = array ();
        
        $params = $this->getParams ( $data );
        $newsletter = $params [static::NEWSLETTER];
        $storeId = $params [static::STOREID];
        
        /**
         * Check this customer has valid token or not
         *
         * @var $isValidToken Mage_Login_Model_Token
         */
        $isValidToken = Mage::getModel ( static::LOGIN_TOKEN )->checkUserToken ( $this->getRequest ()->getParam ( 'id' ), $data ['token'] );
        
        /**
         *
         * @var $customer Mage_Customer_Model_Customer
         */
        $customer = $this->_loadCustomerById ( $this->getRequest ()->getParam ( 'id' ) );
        
        /**
         *
         * @var $validator Mage_Api2_Model_Resource_Validator_Eav
         */
        $validator = Mage::getResourceModel ( 'api2/validator_eav', array (
                'resource' => $this 
        ) );
        
        $data = $validator->filter ( $data );
        // website is not allowed to change
        unset ( $data [static::WEBSITEID] );
        
        if (! $validator->isValidData ( $data, true )) {
            foreach ( $validator->getErrors () as $error ) {
                $error_message = $error;
                $is_error = 1;
            }
        }
        
        $customer->addData ( $data );
        
        $response [static::VALID_TOKEN] = true;
        
        try {
            
            if (! $isValidToken) {
                $response [static::VALID_TOKEN] = false;
                throw new Exception ( 'Authentication failed.' );
            }
            
            if ($is_error) {
                throw new Exception ( $error_message );
            }
            // set store id for customer
            $customer->setStoreId ( $storeId );
            // set newsletter subscription for customer
            $customer->setIsSubscribed ( $newsletter );
            $customer->save ();
            // get customer data
            $customerData = $customer->getData ();
            $success = 1;
            $message = 'Customer information updated successfully';
            
            $response [static::RESULT] = Mage::getModel ( static::LOGIN_TOKEN )->getCustomerDetail ( $customerData [static::ENTITYID] );
        } catch ( Mage_Core_Exception $e ) {
            $success = 0;
            $message = $e->getMessage ();
        } catch ( Exception $e ) {
            $success = 0;
            $message = $e->getMessage ();
        }
        
        $response [static::ERROR] = false;
        $response [static::SUCCESS] = $success;
        $response [static::MESSAGE] = $message;
        $this->getResponse ()->setBody ( json_encode ( $response ) );
        return;
    }
    
    /**
     * function that is called when post is done
     * Get customer details
     *
     * @see Mage_Api2_Model_Resource::_retrieve() **
     */
    public function _retrieve() {
        $result = array ();
        $customerId = $this->getRequest ()->getParam ( 'id' );
        
        /**
         * Check this customer has valid token or not
         *
         * @var $isValidToken Mage_Login_Model_Token
         */
        $isValidToken = Mage::getModel ( static::LOGIN_TOKEN )->checkUserToken ( $customerId, $this->getRequest ()->getParam ( static::TOKEN ) );
        if ($isValidToken) {
            
            $result ['result'] = Mage::getModel ( static::LOGIN_TOKEN )->getCustomerDetail ( $customerId );
            $result [static::VALID_TOKEN] = true;
            $result [static::SUCCESS] = 1;
            $result [static::ERROR] = false;
        } else {
            $result [static::VALID_TOKEN] = false;
            $result [static::SUCCESS] = 0;
            $result [static::MESSAGE] = 'Authentication failed.';
        }
        
        return $result;
    }
    public function getParams($data) {
        $result = array ();
        // get newsletter value for customer
        $result [static::NEWSLETTER] = (isset ( $data [static::NEWSLETTER] )) ? $data [static::NEWSLETTER] : 0;
        // Get website id
        $result [static::WEBSITEID] = (isset ( $data [static::WEBSITEID] )) ? $data [static::WEBSITEID] : Mage::app ()->getWebsite ( 'base' )->getId ();
        // get store id
        $result [static::STOREID] = (isset ( $data [static::STOREID] )) ? $data [static::STOREID] : Mage::app ()->getWebsite ( $result [static::WEBSITEID] )->getDefaultGroup ()->getDefaultStoreId ();
        return $result;
    }
}