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
 * @package    Contus_Offers
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
 * @since      1.0
 */
class Contus_Offers_Adminhtml_OffersController extends Mage_Adminhtml_Controller_action {
   
   /**
    * Initialize the current item tab with label
    *
    * @return mixed Current page breadcrumb object
    */
   protected function _initAction() {
      $this->loadLayout ()->_setActiveMenu ( 'pushnoteproducts/items' )->_addBreadcrumb ( Mage::helper ( 'adminhtml' )->__ ( 'Items Manager' ), Mage::helper ( 'adminhtml' )->__ ( 'Item Manager' ) );
      
      return $this;
   }
   
   /**
    * Initialize the layout
    * $cnt
    *
    * @return mixed Loads current page layouts
    */
   public function indexAction() {
      $this->_initAction ()->_title ( $this->__ ( 'Offers' ) )->renderLayout ();
   }
   
   /**
    * Check total count of offers
    * Restrict only 5 offers to add
    */
   public function checkCountAction() {
      $collection = Mage::getModel ( 'offers/offers' )->getCollection ();
      
      $size = $collection->getSize ();
      $cnt = count ( $collection );
      if ($cnt >= 5) {
         Mage::getSingleton ( 'core/session' )->addError ( "Can't add more than 5 offers. " );
         $this->_redirectReferer ();
      } else {
         $key = Mage::getSingleton ( 'adminhtml/url' )->getSecretKey ( "adminhtml_offers", "new" );
         
         $this->_redirect ( '*/*/new', array (
               'key' => $key 
         ) );
      }
   }
   
   /**
    * Get Message and product id to push message from web to app
    */
   public function getPushMsgParamsAction() {
      $isEnabled = true;
      
      $params = $this->getRequest ()->getPost ();
      $message = $params ['offer_desc'];
      $id = $params ['offer_id'];
      
      if ($isEnabled) {
         $this->notification ( $message, $id );
      } else {
         Mage::getSingleton ( 'core/session' )->addError ( 'Please enable push notification option for offers.' );
         $this->_redirectReferer ();
      }
   }
   
   /**
    * Nofication
    *
    * @param string $message
    *           message which was given by admin to display in app
    *           
    * @param int $id           
    */
   public function notification($message, $id) {
      
      // Information to send in the push notification
      $msg ['msg'] = $message;
      $msg ['id'] = $id;
      $msg ['type'] = 'list';
      
      // MCOM demo googel api key
      $gcmKey = Mage::getStoreConfig ( 'contus/configuration_pushnotifications/gcm_apikey' );
      $result ['mode'] = false;
      $result = Mage::getModel ( 'configuration/config' )->getNotificationMode ();
      
      // Send Push Notification to Android and ios
      $this->sendNotificationAndroid ( $gcmKey, $msg );
      $this->sendNotificationiOS ( $result ['pemfile'], $gcmKey, $result ['mode'], $msg );
      Mage::getSingleton ( 'core/session' )->addSuccess ( 'Notification Sent Successfully.' );
      $this->_redirectReferer ();
   }
   
   /**
    * Function to Sending Push Notification to Andriod
    *
    * @param $androidAuthKey android
    *           Auth key given by android developer
    * @param $message array
    *           information related to the push notification
    *           
    */
   public function sendNotificationAndroid($androidAuthKey, $message) {
      // database write adapter
      $db_write = Mage::getSingleton ( 'core/resource' )->getConnection ( 'core_write' );
      // database read adapter
      $db_read = Mage::getSingleton ( 'core/resource' )->getConnection ( 'core_read' );
      // get the table preix value
      $prefix = Mage::getConfig ()->getTablePrefix ();
      $userDetails = $db_read->fetchAll ( "SELECT devicetoken FROM  " . $prefix . "mcomm_token WHERE devicetype='android' group by devicetoken" );
      
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
    *           file user for push from server to ios device
    * @param $passphrase while
    *           creating pem file if we given password here we use. In this project we have not created
    *           
    */
   public function sendNotificationiOS($pemfile, $passphrase, $mode, $message) {
      // database write adapter
      $db_write = Mage::getSingleton ( 'core/resource' )->getConnection ( 'core_write' );
      // database read adapter
      $db_read = Mage::getSingleton ( 'core/resource' )->getConnection ( 'core_read' );
      // get the table preix value
      $prefix = Mage::getConfig ()->getTablePrefix ();
      $userDetails = $db_read->fetchAll ( "SELECT devicetoken FROM  " . $prefix . "mcomm_token WHERE devicetype='iphone' AND  `devicetoken` IS NOT NULL 
AND  `devicetoken` !=  ''  group by devicetoken" );
      
      foreach ( $userDetails as $users ) {
         // get the device token from database
         $registration_ids [] = $users ['devicetoken'];
      }
      echo $pemfile;
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
         $connetionStatus =  ("Failed to connect: $err $errstr" . PHP_EOL);
      else
         $connetionStatus =  'Connected to APNS' . PHP_EOL;
         
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
         
         //print_r ( $message );
      }
      // Close the connection to the server
      fclose ( $fp );
      return $result;
   }
   
   /**
    * Edit or create offer details
    *
    * @return void Created record values and redirect to current page
    */
   public function editAction() {
      $id = $this->getRequest ()->getParam ( 'id' );
      $model = Mage::getModel ( 'offers/offers' )->load ( $id );
      
      if ($model->getId () || $id == 0) {
         $data = Mage::getSingleton ( 'adminhtml/session' )->getFormData ( true );
         if (! empty ( $data )) {
            $model->setData ( $data );
         }
         
         // set offer_products data string to array to model
         if (isset ( $model ['offer_products'] ) && $model ['offer_products'] != '') {
            $model ['offer_products'] = explode ( ',', $model ['offer_products'] );
         }
         
         Mage::register ( 'offers_data', $model );
         
         $this->loadLayout ();
         $this->_setActiveMenu ( 'offers/items' );
         
         $this->_addBreadcrumb ( Mage::helper ( 'adminhtml' )->__ ( 'Item Manager' ), Mage::helper ( 'adminhtml' )->__ ( 'Item Manager' ) );
         $this->_addBreadcrumb ( Mage::helper ( 'adminhtml' )->__ ( 'Item News' ), Mage::helper ( 'adminhtml' )->__ ( 'Item News' ) );
         
         $this->getLayout ()->getBlock ( 'head' )->setCanLoadExtJs ( true );
         
         $this->_addContent ( $this->getLayout ()->createBlock ( 'offers/adminhtml_offers_edit' ) )->_addLeft ( $this->getLayout ()->createBlock ( 'offers/adminhtml_offers_edit_tabs' ) );
         
         $this->renderLayout ();
      } else {
         Mage::getSingleton ( 'adminhtml/session' )->addError ( Mage::helper ( 'offers' )->__ ( 'Item does not exist' ) );
         $this->_redirect ( '*/*/' );
      }
   }
   
   /**
    * Redirects page to edit action
    *
    * @return void Redirects to edit action
    */
   public function newAction() {
      $this->_forward ( 'edit' );
   }
   
   /**
    * Save action for current records
    *
    * @return boolean True if data saved successfull else redirects page to edit action
    */
   public function saveAction() {
      if ($data = $this->getRequest ()->getPost ()) {
         
         // convert offer_products array to string
         if (! is_array ( $data ['offer_products'] )) {
            $err_offer_product = '';
         } else if (is_array ( $data ['offer_products'] )) {
            $err_offer_product = '';
            $data ['offer_products'] = implode ( ',', $data ['offer_products'] );
         }
         
         // Format date
         $data = $this->_filterDates ( $data, array (
               'from_date',
               'to_date' 
         ) );
         
         // Save offer image
         $err_img = '';
         $err_array = array ();
         
         if (isset ( $data ['stores'] )) {
            if (in_array ( '0', $data ['stores'] )) {
               $data ['store_id'] = '0';
            } else {
               $data ['store_id'] = implode ( ",", $data ['stores'] );
            }
            unset ( $data ['stores'] );
         }
         if (isset ( $_FILES ['offer_img'] ['name'] ) && $_FILES ['offer_img'] ['name'] != '') {
            try {
               
               /* Starting upload */
               $uploader = new Varien_File_Uploader ( 'offer_img' );
               
               // for creating the directory if not exists
               $uploader->setAllowCreateFolders ( true );
               // Alloweed extention would work
               $uploader->setAllowedExtensions ( array (
                     'jpg',
                     'jpeg',
                     'gif',
                     'png' 
               ) );
               $uploader->setAllowRenameFiles ( false );
               $uploader->setFilesDispersion ( false );
               
               // We set media as the upload dir
               $path = Mage::getBaseDir ( 'media' ) . DS . 'offers' . DS;
               if ($uploader->save ( $path, $_FILES ['offer_img'] ['name'] )) {
                  
                  // this way the name is saved in DB
                  $data ['offer_img'] = 'offers/' . $_FILES ['offer_img'] ['name'];
               }
            } catch ( Exception $e ) {
               $err_img = 1;
               $err_array [] = $e->getMessage ();
            }
         } else {
            if (isset ( $data ['offer_img_view'] ) && $data ['offer_img_view'] != '') {
               $data ['offer_img'] = $data ['offer_img_view'];
            }
         }
         
         $model = Mage::getModel ( 'offers/offers' );
         $model->setData ( $data )->setId ( $this->getRequest ()->getParam ( 'id' ) );
         
         // Check validation for uploaded imgae and non empty of products before insert
         
         if (isset ( $err_offer_product ) && $err_offer_product == 1) {
            $err_array [] = 'Please select product(s) for this offer';
         }
         
         if (isset ( $err_img ) && $err_img == 1 || isset ( $err_offer_product ) && $err_offer_product == 1) {
            
            foreach ( $err_array as $msg ) {
               Mage::getSingleton ( 'adminhtml/session' )->addError ( Mage::helper ( 'offers' )->__ ( $msg ) );
            }
            $offerId = $this->getRequest ()->getParam ( 'id' );
            if (isset ( $offerId ) && $this->getRequest ()->getParam ( 'id' ) != '') {
               $this->_redirect ( '*/*/edit', array (
                     'id' => $model->getId () 
               ) );
            } else {
               
               Mage::getSingleton ( 'adminhtml/session' )->setFormData ( $data );
               $key = Mage::getSingleton ( 'adminhtml/url' )->getSecretKey ( "adminhtml_offers", "new" );
               
               $this->_redirect ( '*/*/new', array (
                     'key' => $key 
               ) );
            }
            
            return;
         }
         
         try {
            if ($model->getCreatedOn == NULL || $model->getUpdateOn () == NULL) {
               $model->setCreatedOn ( now () )->setUpdateOn ( now () );
            } else {
               $model->setUpdateOn ( now () );
            }
            
            $model->save ();
            Mage::getSingleton ( 'adminhtml/session' )->addSuccess ( Mage::helper ( 'offers' )->__ ( 'Offer was successfully saved.' ) );
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
      Mage::getSingleton ( 'adminhtml/session' )->addError ( Mage::helper ( 'offers' )->__ ( 'Unable to find item to save.' ) );
      $this->_redirect ( '*/*/' );
   }
   
   /**
    * Delete the current id
    *
    * @return void Redirects to grid page
    */
   public function deleteAction() {
      if ($this->getRequest ()->getParam ( 'id' ) > 0) {
         try {
            $model = Mage::getModel ( 'offers/offers' );
            
            $model->setId ( $this->getRequest ()->getParam ( 'id' ) )->delete ();
            
            Mage::getSingleton ( 'adminhtml/session' )->addSuccess ( Mage::helper ( 'adminhtml' )->__ ( 'Item was successfully deleted.' ) );
            $this->_redirect ( '*/*/' );
         } catch ( Exception $e ) {
            Mage::getSingleton ( 'adminhtml/session' )->addError ( $e->getMessage () );
            $this->_redirect ( '*/*/edit', array (
                  'id' => $this->getRequest ()->getParam ( 'id' ) 
            ) );
         }
      }
      $this->_redirect ( '*/*/' );
   }
   
   /**
    * Deletes selected records from database
    *
    * @return void Redirect to grid page
    */
   public function massDeleteAction() {
      $offersIds = $this->getRequest ()->getParam ( 'offers' );
      if (! is_array ( $offersIds )) {
         Mage::getSingleton ( 'adminhtml/session' )->addError ( Mage::helper ( 'adminhtml' )->__ ( 'Please select item(s).' ) );
      } else {
         try {
            foreach ( $offersIds as $offersId ) {
               $offers = Mage::getModel ( 'offers/offers' )->load ( $offersId );
               $offers->delete ();
            }
            Mage::getSingleton ( 'adminhtml/session' )->addSuccess ( Mage::helper ( 'adminhtml' )->__ ( 'Total of %d record(s) were successfully deleted', count ( $offersIds ) ) );
         } catch ( Exception $e ) {
            Mage::getSingleton ( 'adminhtml/session' )->addError ( $e->getMessage () );
         }
      }
      $this->_redirect ( '*/*/index' );
   }
   
   /**
    * Updates mass status update on selected grid records
    *
    * @return void Redirects back to index page
    */
   public function massStatusAction() {
      $offersIds = $this->getRequest ()->getParam ( 'offers' );
      if (! is_array ( $offersIds )) {
         Mage::getSingleton ( 'adminhtml/session' )->addError ( $this->__ ( 'Please select item(s)' ) );
      } else {
         try {
            foreach ( $offersIds as $offersId ) {
               $offers = Mage::getSingleton ( 'offers/offers' )->load ( $offersId )->setStatus ( $this->getRequest ()->getParam ( 'status' ) )->setIsMassupdate ( true )->save ();
            }
            $this->_getSession ()->addSuccess ( $this->__ ( 'Total of %d record(s) were successfully updated', count ( $offersIds ) ) );
         } catch ( Exception $e ) {
            $this->_getSession ()->addError ( $e->getMessage () );
         }
      }
      $this->_redirect ( '*/*/index' );
   }
   
   /**
    * CSV file export
    *
    * @return csv Returns csv file
    */
   public function exportCsvAction() {
      $fileName = 'offers.csv';
      $content = $this->getLayout ()->createBlock ( 'offers/adminhtml_offers_grid' )->getCsv ();
      
      $this->_sendUploadResponse ( $fileName, $content );
   }
   
   /**
    * XML file export
    *
    * @return xml Returns xml file to be download
    */
   public function exportXmlAction() {
      $fileName = 'offers.xml';
      $content = $this->getLayout ()->createBlock ( 'offers/adminhtml_offers_grid' )->getXml ();
      
      $this->_sendUploadResponse ( $fileName, $content );
   }
   protected function _sendUploadResponse($fileName, $content, $contentType = 'application/octet-stream') {
      $response = $this->getResponse ();
      $response->setHeader ( 'HTTP/1.1 200 OK', '' );
      $response->setHeader ( 'Pragma', 'public', true );
      $response->setHeader ( 'Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true );
      $response->setHeader ( 'Content-Disposition', 'attachment; filename=' . $fileName );
      $response->setHeader ( 'Last-Modified', date ( 'r' ) );
      $response->setHeader ( 'Accept-Ranges', 'bytes' );
      $response->setHeader ( 'Content-Length', strlen ( $content ) );
      $response->setHeader ( 'Content-type', $contentType );
      $response->setBody ( $content );
      $response->sendResponse ();
   }
}