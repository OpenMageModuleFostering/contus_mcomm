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
 * @package    Contus_Pushnoteorders
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
 * @since      1.0
 */
class Contus_Pushnoteorders_Adminhtml_PushnoteordersController extends Mage_Adminhtml_Controller_action {
    protected function _initAction() {
        $this->loadLayout ()->_setActiveMenu ( 'pushnoteproducts/items' )->_addBreadcrumb ( Mage::helper ( 'adminhtml' )->__ ( 'Items Manager' ), Mage::helper ( 'adminhtml' )->__ ( 'Item Manager' ) );
        
        return $this;
    }
    public function indexAction() {
        $this->_initAction ()->_title ( $this->__ ( 'Push Notification - Orders' ) )->renderLayout ();
    }
    
    /**
     * Get Message and order id to push message from web to app
     */
    public function getPushMsgParamsAction() {
        $isEnabled = false;
        $isEnabled = Mage::getStoreConfig ( 'contus/configuration_pushnotifications/order_pushnotification_enabled' );
        
        $params = $this->getRequest ()->getPost ();
        $message = $params ['order_detail'];
        $id = $params ['order_id'];
        $customer_id = $params ['customer_id'];
        if ($isEnabled && trim ( $customer_id ) != '') {
            $this->notification ( $message, $id, $customer_id );
        } else if (trim ( $customer_id ) == '') {
            Mage::getSingleton ( 'core/session' )->addError ( "Can't sent notification because this order placed by guest." );
            $this->_redirectReferer ();
        } else {
            Mage::getSingleton ( 'core/session' )->addError ( 'Please enable push notification option for order.' );
            $this->_redirectReferer ();
        }
    }
    /**
     * Nofication
     *
     * @param string $message
     *            message which was given by admin to display in app
     *            
     * @param int $id            
     */
    public function notification($message, $id, $customer_id) {
        
        // Information to send in the push notification
        $msg ['msg'] = $message;
        $msg ['id'] = $id;
        $msg ['type'] = 'order';
        
        // MCOM demo googel api key
        $gcmKey = Mage::getStoreConfig ( 'contus/configuration_pushnotifications/gcm_apikey' );
        $result['mode'] = false;
        $result  =  Mage::getModel ( 'configuration/config' )->getNotificationMode();
        // Send Push Notification to Android and ios
        $this->sendNotificationAndroid ( $gcmKey, $msg, $customer_id );
        
        $this->sendNotificationiOS ( $result['pemfile'], $gcmKey, $result['mode'], $msg, $customer_id );
        Mage::getSingleton ( 'core/session' )->addSuccess ( 'Notification Sent Successfully.' );
        $this->_redirectReferer ();
    }
    
    /**
     * Function to Sending Push Notification to Andriod
     *
     * @param $androidAuthKey android
     *            Auth key given by android developer
     * @param $message array
     *            information related to the push notification
     *            
     */
    public function sendNotificationAndroid($androidAuthKey, $message, $customer_id) {
        // echo $customer_id; exit;
        // database write adapter
        $db_write = Mage::getSingleton ( 'core/resource' )->getConnection ( 'core_write' );
        // database read adapter
        $db_read = Mage::getSingleton ( 'core/resource' )->getConnection ( 'core_read' );
        // get the table preix value
        $prefix = Mage::getConfig ()->getTablePrefix ();
        $userDetails = $db_read->fetchAll ( "SELECT devicetoken FROM  " . $prefix . "mcomm_token WHERE devicetype='android' and userid=" . $customer_id );
        
        foreach ( $userDetails as $users ) {
            // get the device token from the database
            $registration_ids [] = $users ['devicetoken'];
        }
        
        // Set POST variables
        $url = 'https://android.googleapis.com/gcm/send';
        
        $fields = array (
                'registration_ids' => $registration_ids,
                'data' => $message 
        );
        $headers = array (
                'Authorization: key=' . $androidAuthKey,
                'Content-Type: application/json' 
        );
        // Open connection
        $ch = curl_init ();
        
        // Set the url, number of POST vars, POST data
        curl_setopt ( $ch, CURLOPT_URL, $url );
        
        curl_setopt ( $ch, CURLOPT_POST, true );
        curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
        
        // Disabling SSL Certificate support temporarly
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );
        
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, json_encode ( $fields ) );
        
        // Execute post
        $result = curl_exec ( $ch );
        if ($result === FALSE) {
            die ( 'Curl failed: ' . curl_error ( $ch ) );
        }
        // Close connection
        curl_close ( $ch );
    }
    
    /**
     * Function to Sending Push Notification to IOS
     *
     * @param $pemfile this
     *            file user for push from server to ios device
     * @param $passphrase while
     *            creating pem file if we given password here we use. In this project we have not created
     *            
     */
    public function sendNotificationiOS($pemfile, $passphrase, $mode, $message, $customer_id) {
        // database write adapter
        $db_write = Mage::getSingleton ( 'core/resource' )->getConnection ( 'core_write' );
        // database read adapter
        $db_read = Mage::getSingleton ( 'core/resource' )->getConnection ( 'core_read' );
        // get the table preix value
        $prefix = Mage::getConfig ()->getTablePrefix ();
        $userDetails = $db_read->fetchAll ( "SELECT devicetoken FROM  " . $prefix . "mcomm_token WHERE devicetype='iphone' and userid=" . $customer_id );
        foreach ( $userDetails as $users ) {
            // get the device token from database
            $registration_ids [] = $users ['devicetoken'];
        }
        $ctx = stream_context_create ();
        stream_context_set_option ( $ctx, 'ssl', 'local_cert', $pemfile );
        
        // Type pem file name
        stream_context_set_option ( $ctx, 'ssl', 'passphrase', '' );
        
        // Open a connection to the APNS server
        if ($mode == false) {
            $fp = stream_socket_client ( 'ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx );
        } else {
            
            $fp = stream_socket_client ( 'ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx );
        }
        if (! $fp)
            $connetionStatus = ("Failed to connect: $err $errstr" . PHP_EOL);
        else
            $connetionStatus = 'Connected to APNS' . PHP_EOL;
            
            // Create the payload body
        $body ['aps'] = array (
                
                'alert' => $message ['msg'],
                'sound' => 'default',
                'id' => $message ['id'],
                'type' => $message ['type'],
                'content-available' => '1' 
        );
        
        // Encode the payload as JSON
        $payload = json_encode ( $body );
        // Build the binary notification
        for($i = 0; $i < count ( $registration_ids ); $i ++) {
            $msg = chr ( 0 ) . pack ( 'n', 32 ) . pack ( 'H*', $registration_ids [$i] ) . pack ( 'n', strlen ( $payload ) ) . $payload;
            // Send it to the server
            $result = fwrite ( $fp, $msg, strlen ( $msg ) );
            
            if (! $result)
                $deliverStatus =  'Message not delivered' . PHP_EOL;
            else
                $deliverStatus =  'Message successfully delivered->' . $message ['msg'] . PHP_EOL;
            
          //  print_r ( $message );
        }
        // Close the connection to the server
        fclose ( $fp );
        return $result;
    }
    public function editAction() {
        $id = $this->getRequest ()->getParam ( 'id' );
        $model = Mage::getModel ( 'pushnoteorders/pushnoteorders' )->load ( $id );
        
        if ($model->getId () || $id == 0) {
            $data = Mage::getSingleton ( 'adminhtml/session' )->getFormData ( true );
            if (! empty ( $data )) {
                $model->setData ( $data );
            }
            
            Mage::register ( 'pushnoteorders_data', $model );
            
            $this->loadLayout ();
            $this->_setActiveMenu ( 'pushnoteorders/items' );
            
            $this->_addBreadcrumb ( Mage::helper ( 'adminhtml' )->__ ( 'Item Manager' ), Mage::helper ( 'adminhtml' )->__ ( 'Item Manager' ) );
            $this->_addBreadcrumb ( Mage::helper ( 'adminhtml' )->__ ( 'Item News' ), Mage::helper ( 'adminhtml' )->__ ( 'Item News' ) );
            
            $this->getLayout ()->getBlock ( 'head' )->setCanLoadExtJs ( true );
            
            $this->_addContent ( $this->getLayout ()->createBlock ( 'pushnoteorders/adminhtml_pushnoteorders_edit' ) )->_addLeft ( $this->getLayout ()->createBlock ( 'pushnoteorders/adminhtml_pushnoteorders_edit_tabs' ) );
            
            $this->renderLayout ();
        } else {
            Mage::getSingleton ( 'adminhtml/session' )->addError ( Mage::helper ( 'pushnoteorders' )->__ ( 'Item does not exist' ) );
            $this->_redirect ( '*/*/' );
        }
    }
    public function newAction() {
        $this->_forward ( 'edit' );
    }
    public function saveAction() {
        if ($data = $this->getRequest ()->getPost ()) {
            
            $model = Mage::getModel ( 'pushnoteorders/pushnoteorders' );
            $model->setData ( $data )->setId ( $this->getRequest ()->getParam ( 'id' ) );
            
            try {
                if ($model->getCreatedTime == NULL || $model->getUpdateTime () == NULL) {
                    $model->setCreatedTime ( now () )->setUpdateTime ( now () );
                } else {
                    $model->setUpdateTime ( now () );
                }
                
                $model->save ();
                Mage::getSingleton ( 'adminhtml/session' )->addSuccess ( Mage::helper ( 'pushnoteorders' )->__ ( 'Item was successfully saved' ) );
                Mage::getSingleton ( 'adminhtml/session' )->setFormData ( false );
                
                if ($this->getRequest ()->getParam ( 'back' )) {
                    $this->_redirect ( '*/*/edit', array (
                            'id' => $model->getId () 
                    ) );
                    return;
                }
                $this->_redirect ( '*/*/' );
                return;
            } catch ( Exception $e ) {
                Mage::getSingleton ( 'adminhtml/session' )->addError ( $e->getMessage () );
                Mage::getSingleton ( 'adminhtml/session' )->setFormData ( $data );
                $this->_redirect ( '*/*/edit', array (
                        'id' => $this->getRequest ()->getParam ( 'id' ) 
                ) );
                return;
            }
        }
        Mage::getSingleton ( 'adminhtml/session' )->addError ( Mage::helper ( 'pushnoteorders' )->__ ( 'Unable to find item to save' ) );
        $this->_redirect ( '*/*/' );
    }
}