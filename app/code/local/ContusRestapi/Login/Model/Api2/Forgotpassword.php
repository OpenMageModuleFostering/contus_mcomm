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
 * @package    ContusRestapi_Login
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
 * @since      1.0
 */
class ContusRestapi_Login_Model_Api2_Forgotpassword extends Mage_Api2_Model_Resource {
    /**
     * function that is called when post is done **
     *
     * Forgot password action for registered customers
     *
     * @param array $data            
     *
     * @return array json array
     */
    protected function _create(array $data) {
        $response = array ();
        if ($data ['website_id'] != '') {
            $websiteId = $data ['website_id'];
        } else {
            $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
        }
        
        $email = $data ['email'];
        try {
            $customer = Mage::getModel ( 'customer/customer' )->setWebsiteId ( $websiteId )->loadByEmail ( $email );
            
            if ($customer->getId ()) {
                
                $newPassword = $this->createPassword (6);
                $customer->changePassword ( $newPassword, false );
                $customer->sendPasswordReminderEmail ();
                $success = 1;
                $message = 'A new password has been sent.';
            } else {
                $success = 0;
                $message = 'Customer not found.';
            }
        } catch ( Exception $ex ) {
            $success = 0;
            $message = $ex->getMessage ();
        }
        $response ['error'] = false;
        $response ['success'] = $success;
        $response ['message'] = $message;
        $this->getResponse ()->setBody ( json_encode ( $response ) );
        return;
    }
    
    /**
     * Generate random password and if it is less than fixed characters, again call create character function.
     *
     * @param  string  $length   Length for password
     *
     * @return string $password as password
     */
    
    public function createPassword($length) {
        $password = $this->createCharacter($length);
        if(strlen($password) < $length){
            $password = $this->createCharacter($length);
        }
        return $password;
    }
    
    /**
     * Generate random password for customers login by Social network like facebook,twiter,etc,
     *
     * @param  string  $length   Length for password
     *
     * @return string $password as password
     */
    public function createCharacter($length){
        $chars = "1234567890abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $i = 0;
        $password = "";
         
        while ( $i <= $length ) {
            $password .= $chars {mt_rand ( 0, strlen ( $chars ) )};
            $i ++;
        }
        return $password;
    }   
}