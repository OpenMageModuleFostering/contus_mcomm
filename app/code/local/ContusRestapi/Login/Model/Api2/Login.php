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
class ContusRestapi_Login_Model_Api2_Login extends Mage_Api2_Model_Resource {
    const STOREID = 'store_id';
    const EMAIL = 'email';
    const NEWSLETTER = 'newsletter';
    const LOGIN_TOKEN = 'login/token';
    const SUCCESS = 'success';
    const MESSAGE = 'message';
    
    /**
     * function that is called when post is done **
     * Login action for registered customers
     *
     * @param array $data            
     *
     * @return array json array
     */
    public function _create(array $data) {
        $response = array ();
        
        // get website id from request
        $websiteId = ( int ) $data ['website_id'];
        if ($websiteId <= 0) {
            $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
        }
        // get store id from request
        $storeId = ( int ) $data [static::STOREID];
        if ($storeId <= 0) {
            $storeId = Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
        }
        
        $email = $data ['email'];
        $password = $data ['password'];
        // getting the device token
        $deviceToken = $data ['device_token'];
        // getting the device type
        $deviceType = $data ['device_type'];
        
        /**
         *
         * @var $customer Mage_Customer_Model_Customer
         */
        $customerObj = Mage::getModel ( 'customer/customer' );
        $validated = 0;
        // check customer
        $validCustomer = $this->CheckCustomer ( $customerObj, $email, $password, $websiteId );
        $success = $validCustomer [static::SUCCESS];
        $message = $validCustomer [static::MESSAGE];
        $validated = $validCustomer ['validate'];
        
        if ($validated) {
            Mage::getSingleton ( 'customer/session' )->loginById ( $customerObj->getEntityId () );
            // get customer email
            $result [static::EMAIL] = $customerObj->getEmail ();
            $result ['firstname'] = $customerObj->getFirstname ();
            $result ['lastname'] = $customerObj->getLastname ();
            $result ['customer_id'] = $customerObj->getEntityId ();
            
            $subscriber = Mage::getModel ( 'newsletter/subscriber' )->loadByEmail ( $customerObj->getEmail () );
            if ($subscriber->getId () && $subscriber->getStatus () == 1) {
                $result [static::NEWSLETTER] = 1;
            } else {
                $result [static::NEWSLETTER] = 0;
            }
            // get customer dob yyyy-mm-dd hh:mm:ss
            $result ['dob'] = $customerObj->getDob ();
            
            $tokenvalue = Mage::getModel ( static::LOGIN_TOKEN )->getRandomString ( 6 );
            $tokenObj = Mage::getModel ( static::LOGIN_TOKEN )->load ( $customerObj->getEntityId (), 'userid' );
            $tokenObj->setUserid ( $customerObj->getEntityId () );
            $tokenObj->setToken ( $tokenvalue );
            $tokenObj->setCreated ( date ( 'Y-m-d H:i:s' ) );
            $tokenObj->setStatus ( 1 );
            $tokenObj->save ();
            //Update device token and type in token table
            Mage::getModel ( 'login/methods_functions' )->updateDeviceToken ( $customerObj->getEntityId (), $deviceToken, $deviceType );
            
            $result ['token'] = $tokenvalue;
            $result ['cart_count'] = Mage::getModel ( static::LOGIN_TOKEN )->getCartCount ( $customerObj->getEntityId (), $storeId );
            $response ['result'] = $result;
        }
        $response ['error'] = false;
        $response [static::SUCCESS] = $success;
        $response [static::MESSAGE] = $message;
        $this->getResponse ()->setBody ( json_encode ( $response ) );
        return;
    }
    public function CheckCustomer($customerObj, $email, $password, $websiteId) {
        $result = array ();
        if (! empty ( $email ) && ! empty ( $password )) {
            try {
                
                if ($customerObj->setWebsiteId ( $websiteId )->authenticate ( $email, $password )) {
                    $validated = 1;
                    $success = 1;
                    $message = 'You are successfully logged in.';
                }
            } catch ( Mage_Core_Exception $e ) {
                switch ($e->getCode ()) {
                    case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED :
                        $message = 'This account is not confirmed.';
                        break;
                    case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD :
                        $message = 'Invalid email or password.';
                        break;
                    default :
                        $message = $e->getMessage ();
                }
                $success = 0;
            } catch ( Exception $e ) {
                $message = $e->getMessage ();
                $success = 0;
            }
        } else {
            $message = 'Email and password are required.';
            $success = 0;
        }
        $result [static::SUCCESS] = $success;
        $result [static::MESSAGE] = $message;
        $result ['validate'] = $validated;
        
        return $result;
    }
    
    /**
     * function that is called when post is done **
     * Update GCM device token
     *
     * @param array $data
     *
     * @return array json array
     */
    public function _update(array $data) {
        $response = array ();
        // getting the device token
        $deviceToken = $data ['device_token'];
        // getting the device type
        $deviceType = $data ['device_type'];
        $customerId = $data['customer_id'];
        //Update device token and type in token table
        Mage::getModel ( 'login/methods_functions' )->updateDeviceToken ( $customerId, $deviceToken, $deviceType );
        $response ['error'] = false;
        $response [static::SUCCESS] = 1;
        $response [static::MESSAGE] = 'Device token updated successfully.';
        $this->getResponse ()->setBody ( json_encode ( $response ) );
        return;
    }
}