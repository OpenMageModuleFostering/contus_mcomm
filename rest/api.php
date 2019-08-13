<?php
/**
 * Contus
 * This class is to demonstrate the Simple Rest Web-service and doing

 * HTTP operations like PUT,POST,GET and DELETE.
 *
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0

 */
require_once ("Rest.inc.php");
$mageFilename = '../app/Mage.php';
require_once $mageFilename;
class API extends REST {
   public $data = "";
   public function __construct() {
      parent::__construct (); // Init parent constructor
   }
   
   // Public method for access api.
   // This method dynamically call the method based on the query string
   public function processApi() {
      $func = strtolower ( trim ( str_replace ( "/", "", $_REQUEST ['req'] ) ) );
      if (( int ) method_exists ( $this, $func ) > 0)
         $this->$func ();
      else
         $this->sendResponse ( 404, json_encode ( array (
               'error' => true,
               'message' => 'Not Found' 
         ) ) );
      // If the method not exist with in this class, response would be "Page not found".
   }
   private function api() {
      // $callbackUrl is a path to your file with OAuth authentication example for the Admin user
      $baseUrl = Mage::getBaseUrl ( Mage_Core_Model_Store::URL_TYPE_WEB );
      
      $callbackUrl = $baseUrl . "rest/api.php";
      $temporaryCredentialsRequestUrl = $baseUrl . "oauth/initiate?oauth_callback=" . urlencode ( $callbackUrl );
      $adminAuthorizationUrl = $baseUrl . 'oauth/authorize';
      $accessTokenRequestUrl = $baseUrl . 'oauth/token';
      $apiUrl = $baseUrl . 'api/rest';
      // get rest key and secretkey
      $consumerKey = Mage::getStoreConfig ( 'contus/mcomapp_about/rest_apikey' );
      $consumerSecret = Mage::getStoreConfig ( 'contus/mcomapp_about/rest_secretkey' );
      
      session_start ();
      
      try {
         $oauthClient = new OAuth ( $consumerKey, $consumerSecret, OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI );
         $oauthClient->enableDebug ();
         
         if (! isset ( $_GET ['oauth_token'] )) {
            $requestToken = $oauthClient->getRequestToken ( $temporaryCredentialsRequestUrl );
            $_SESSION ['secret'] = $requestToken ['oauth_token_secret'];
            $_SESSION ['token'] = $requestToken ['oauth_token'];
            $_SESSION ['state'] = 1;
         }
         
         $oauthClient->setToken ( $_SESSION ['token'], $_SESSION ['secret'] );
         
         $action = $this->_request ['action'];
         try {
            switch ($action) {
               
               case "splashpage" :
                  
                  if ($this->get_request_method () != "GET") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $resourceUrl = "$apiUrl/splashpage/";
                     $oauthClient->fetch ( $resourceUrl, array (), OAUTH_HTTP_METHOD_GET, $headers );
                     $result = $oauthClient->getLastResponse ();
                     $this->sendResponse ( 200, json_decode ( $result ) );
                  }
                  break;
               
               case "homepage" :
                  
                  if ($this->get_request_method () != "GET") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $requestData = array (
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'image_size' => ( int ) $this->_request ['image_size'],
                           'city' => ( int ) $this->_request ['city'] 
                     );
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $resourceUrl = "$apiUrl/homepage/";
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_GET, $headers );
                     $result = $oauthClient->getLastResponse ();
                     $this->sendResponse ( 200, json_decode ( $result ) );
                  }
                  break;
               
               case "category_products" :
                  
                  $requestData = array (
                        'store_id' => ( int ) $this->_request ['store_id'],
                        'website_id' => ( int ) $this->_request ['website_id'],
                        'page' => ( int ) $this->_request ['page'],
                        'limit' => ( int ) $this->_request ['limit'],
                        'category_id' => ( int ) $this->_request ['category_id'],
                        'customer_id' => ( int ) $this->_request ['customer_id'],
                        'orderby' => $this->_request ['orderby'],
                        'sortby' => $this->_request ['sortby'],
                        
                        'filters' => $this->_request ['filters'],
                        'image_size' => ( int ) $this->_request ['image_size'],
                        'city' => ( int ) $this->_request ['city'] 
                  );
                  $headers = array (
                        'Content-Type' => 'application/json',
                        'Accept' => '*/*' 
                  );
                  $resourceUrl = "$apiUrl/products_list/";
                  $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_GET, $headers );
                  $result = $oauthClient->getLastResponse ();
                  
                  $this->sendResponse ( 200, json_decode ( $result ) );
                  break;
               
               case "productdetail" :
                  if ($this->get_request_method () != "GET") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     
                     $requestData = array (
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'customer_id' => ( int ) $this->_request ['customer_id'],
                           'id' => ( int ) $this->_request ['product_id'],
                           'image_size' => ( int ) $this->_request ['image_size'] 
                     );
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $resourceUrl = "$apiUrl/product_detail/";
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_GET, $headers );
                     $result = $oauthClient->getLastResponse ();
                     $this->sendResponse ( 200, ($result) );
                  }
                  break;
               
               case "static_page" :
                  if ($this->get_request_method () != "GET") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     
                     $requestData = array (
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'page_key' => $this->_request ['page_key'] 
                     );
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $resourceUrl = "$apiUrl/staticpages";
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_GET, $headers );
                     $result = $oauthClient->getLastResponse ();
                     $this->sendResponse ( 200, json_decode ( $result ) );
                  }
                  break;
               
               case "login" :
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     
                     $requestData = json_encode ( array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'email' => $this->_request ['email'],
                           'password' => $this->_request ['login_signature'],
                           'device_token' => $this->_request ['device_token'],
                           'device_type' => $this->_request ['device_type'] 
                     ) );
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $resourceUrl = "$apiUrl/customer/login/";
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_POST, $headers );
                     $result = $oauthClient->getLastResponse ();
                     $this->sendResponse ( 200, ($result) );
                  }
                  break;
               
               case "forgot_signature" :
                  
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     
                     $requestData = json_encode ( array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'email' => $this->_request ['email'] 
                     ) );
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $oauthClient->fetch ( "$apiUrl/customer/forgotpassword/", $requestData, OAUTH_HTTP_METHOD_POST, $headers );
                     $result = $oauthClient->getLastResponse ();
                     $this->sendResponse ( 200, ($result) );
                  }
                  break;
               
               case "change_signature" :
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     
                     $requestData = json_encode ( array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'customer_id' => ( int ) $this->_request ['customer_id'],
                           'old_password' => $this->_request ['old_signature'],
                           'new_password' => $this->_request ['new_signature'],
                           'token' => $this->_request ['token'] 
                     ) );
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $resourceUrl = "$apiUrl/customer/changepassword/";
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_POST, $headers );
                     $result = json_decode ( $oauthClient->getLastResponse (), true );
                     
                     if (! $result ['isValidToken']) {
                        $this->sendResponse ( 401, json_encode ( array (
                              'error' => true,
                              'message' => 'Unauthorized' 
                        ) ) );
                     } else {
                        $this->sendResponse ( 200, json_encode ( $result ) );
                     }
                  }
                  break;
               
               case "customer_register" :
                  
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     
                     $requestData = json_encode ( array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'email' => $this->_request ['email'],
                           'password' => $this->_request ['login_signature'],
                           'firstname' => $this->_request ['firstname'],
                           'lastname' => $this->_request ['lastname'],
                           'newsletter' => ( int ) $this->_request ['newsletter'],
                           'dob' => $this->_request ['dob'],
                           'group_id' => ( int ) $this->_request ['group_id'],
                           'device_token' => $this->_request ['device_token'],
                           'device_type' => $this->_request ['device_type'] 
                     ) );
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $oauthClient->fetch ( "$apiUrl/customer/register/", $requestData, OAUTH_HTTP_METHOD_POST, $headers );
                     $result = $oauthClient->getLastResponse ();
                     $this->sendResponse ( 200, ($result) );
                  }
                  break;
               
               case "customer_update" :
                  
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     
                     $requestData = json_encode ( array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'email' => $this->_request ['email'],
                           'firstname' => $this->_request ['firstname'],
                           'lastname' => $this->_request ['lastname'],
                           'newsletter' => ( int ) $this->_request ['newsletter'],
                           'dob' => $this->_request ['dob'],
                           'token' => $this->_request ['token'] 
                     ) );
                     
                     $customerId = ( int ) $this->_request ['customer_id'];
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $oauthClient->fetch ( "$apiUrl/customer/update/$customerId", $requestData, OAUTH_HTTP_METHOD_PUT, $headers );
                     $result = json_decode ( $oauthClient->getLastResponse (), true );
                     if (! $result ['isValidToken']) {
                        $this->sendResponse ( 401, json_encode ( array (
                              'error' => true,
                              'message' => 'Unauthorized' 
                        ) ) );
                     } else {
                        $this->sendResponse ( 200, json_encode ( $result ) );
                     }
                  }
                  break;
               
               case "personal_detail" :
                  
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     
                     $requestData = array (
                           'id' => ( int ) $this->_request ['customer_id'],
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'token' => $this->_request ['token'] 
                     );
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $resourceUrl = "$apiUrl/customer/detail/";
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_GET, $headers );
                     $result = json_decode ( $oauthClient->getLastResponse (), true );
                     if (! $result ['isValidToken']) {
                        $this->sendResponse ( 401, json_encode ( array (
                              'error' => true,
                              'message' => 'Unauthorized' 
                        ) ) );
                     } else {
                        $this->sendResponse ( 200, json_encode ( $result ) );
                     }
                  }
                  break;
               
               case "social_login" :
                  
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $requestData = json_encode ( array (
                           'email' => $this->_request ['email'],
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'firstname' => $this->_request ['firstname'],
                           'lastname' => $this->_request ['lastname'],
                           'confirmation' => FALSE,
                           'newsletter' => $this->_request ['newsletter'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'dob' => $this->_request ['dob'],
                           'device_token' => $this->_request ['device_token'],
                           'device_type' => $this->_request ['device_type'] 
                     ) );
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $resourceUrl = "$apiUrl/customer/social_login/";
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_POST, $headers );
                     $result = $oauthClient->getLastResponse ();
                     
                     $this->sendResponse ( 200, ($result) );
                  }
                  break;
               
               case "add_tocart" :
                  
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $requestData = json_encode ( array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'customer_id' => ( int ) $this->_request ['customer_id'],
                           'product_id' => ( int ) $this->_request ['product_id'],
                           'token' => $this->_request ['token'],
                           'quote_id' => ( int ) $this->_request ['quote_id'],
                           'qty' => ( int ) $this->_request ['qty'],
                           'currencyCode' => $this->_request ['currencyCode'],
                           'super_attribute' => json_decode ( $this->_request ['super_attribute'] ),
                           'links' => ($this->_request ['links']),
                           'custom_option' => json_decode ( $this->_request ['custom_option'] ) 
                     ) );
                     
                     $resourceUrl = "$apiUrl/cart/add/";
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_POST, $headers );
                     $result = json_decode ( $oauthClient->getLastResponse (), true );
                     if (! $result ['isValidToken']) {
                        $this->sendResponse ( 401, json_encode ( array (
                              'error' => true,
                              'message' => 'Unauthorized' 
                        ) ) );
                     } else {
                        $this->sendResponse ( 200, json_encode ( $result ) );
                     }
                  }
                  break;
               case "cart_list" :
                  
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     
                     $requestData = array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'customer_id' => ( int ) $this->_request ['customer_id'],
                           'token' => $this->_request ['token'] 
                     );
                     
                     $resourceUrl = "$apiUrl/cart/productlist/";
                     
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_GET, $headers );
                     $result = json_decode ( $oauthClient->getLastResponse (), true );
                     if (! $result ['isValidToken']) {
                        $this->sendResponse ( 401, json_encode ( array (
                              'error' => true,
                              'message' => 'Unauthorized' 
                        ) ) );
                     } else {
                        $this->sendResponse ( 200, json_encode ( $result ) );
                     }
                  }
                  break;
               
               case "update_cart" :
                  
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $requestData = json_encode ( array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'customer_id' => ( int ) $this->_request ['customer_id'],
                           'product_id' => ( int ) $this->_request ['product_id'],
                           'token' => $this->_request ['token'],
                           'quote_id' => ( int ) $this->_request ['quote_id'],
                           'qty' => ( int ) $this->_request ['qty'],
                           'currencyCode' => $this->_request ['currencyCode'],
                           'super_attribute' => json_decode ( $this->_request ['super_attribute'] ),
                           'custom_option' => json_decode ( $this->_request ['custom_option'] ),
                           'links' => ($this->_request ['links']) 
                     ) );
                     
                     $resourceUrl = "$apiUrl/cart/update/";
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_PUT, $headers );
                     $result = json_decode ( $oauthClient->getLastResponse (), true );
                     
                     if (! $result ['isValidToken']) {
                        $this->sendResponse ( 401, json_encode ( array (
                              'error' => true,
                              'message' => 'Unauthorized' 
                        ) ) );
                     } else {
                        $this->sendResponse ( 200, json_encode ( $result ) );
                     }
                  }
                  break;
               case "deletefromcart" :
                  
                  if ($this->get_request_method () != "DELETE") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     
                     if ($this->_request ['website_id'] && $this->_request ['website_id'] != '') {
                        $websiteId = $this->_request ['website_id'];
                     } else {
                        $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
                     }
                     if ($this->_request ['store_id'] && $this->_request ['store_id'] != '') {
                        $storeId = $this->_request ['store_id'];
                     } else {
                        $storeId = Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
                     }
                     if ($this->_request ['quote_id'] && $this->_request ['quote_id'] != '') {
                        $quote_id = $this->_request ['quote_id']; // asc desc
                     } else {
                        $quote_id = '';
                     }
                     if ($this->_request ['product_id'] && $this->_request ['product_id'] != '') {
                        $product_id = $this->_request ['product_id'];
                     } else {
                        $product_id = '';
                     }
                     if ($this->_request ['customer_id'] && $this->_request ['customer_id'] != '') {
                        $customer_id = $this->_request ['customer_id'];
                     } else {
                        $customer_id = '';
                     }
                     if ($this->_request ['custom_option'] && $this->_request ['custom_option'] != '') {
                        $custom_option = ($this->_request ['custom_option']);
                        foreach ( (json_decode ( $custom_option )) as $key => $option ) {
                           if (is_string ( $option )) {
                              $option = str_replace ( ' ', '$$$$', $option );
                           }
                           $new_option [$key] = $option;
                        }
                        $custom_option = json_encode ( $new_option );
                     } else {
                        $custom_option = '';
                     }
                     
                     if ($this->_request ['super_attribute'] && $this->_request ['super_attribute'] != '') {
                        $super_attribute = ($this->_request ['super_attribute']);
                     } else {
                        $super_attribute = '';
                     }
                     if ($this->_request ['links'] && $this->_request ['links'] != '') {
                        $links = ($this->_request ['links']);
                     } else {
                        $links = '';
                     }
                     
                     $token = $this->_request ['token'];
                     $resourceUrl = "$apiUrl/cart/delete/links/" . $links . "/super_attribute/" . $super_attribute . "/custom_option/" . $custom_option . "/quote_id/" . $quote_id . "/product_id/" . $product_id . "/customer_id/" . $customer_id . "/store_id/" . $storeId . "/website_id/" . $websiteId . "/token/" . $token;
                     
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $oauthClient->fetch ( $resourceUrl, '', OAUTH_HTTP_METHOD_DELETE, $headers );
                     $result = json_decode ( $oauthClient->getLastResponse (), true );
                     if (! $result ['isValidToken']) {
                        $this->sendResponse ( 401, json_encode ( array (
                              'error' => true,
                              'message' => 'Unauthorized' 
                        ) ) );
                     } else {
                        $this->sendResponse ( 200, json_encode ( $result ) );
                     }
                  }
                  break;
               
               case "cart_count" :
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     
                     $requestData = array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'customer_id' => ( int ) $this->_request ['customer_id'],
                           'token' => $this->_request ['token'] 
                     );
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     
                     $resourceUrl = "$apiUrl/cart/item_count/";
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_GET, $headers );
                     $result = json_decode ( $oauthClient->getLastResponse (), true );
                     if (! $result ['isValidToken']) {
                        $this->sendResponse ( 401, json_encode ( array (
                              'error' => true,
                              'message' => 'Unauthorized' 
                        ) ) );
                     } else {
                        $this->sendResponse ( 200, json_encode ( $result ) );
                     }
                  }
                  break;
               case "searchproducts" :
                  $search_term = str_replace ( ' ', '%20', $this->_request ['search_term'] );
                  if ($this->get_request_method () != "GET") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $requestData = array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'customer_id' => ( int ) $this->_request ['customer_id'],
                           'category_id' => ( int ) $this->_request ['category_id'],
                           
                           'search_term' => $this->_request ['search_term'],
                           'page' => $this->_request ['page'],
                           'limit' => $this->_request ['limit'],
                           'image_size' => ( int ) $this->_request ['image_size'],
                           'city' => ( int ) $this->_request ['city'] 
                     );
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $oauthClient->fetch ( "$apiUrl/search/products/", $requestData, OAUTH_HTTP_METHOD_GET, $headers );
                     
                     $result = $oauthClient->getLastResponse ();
                     
                     $this->sendResponse ( 200, json_decode ( $result ) );
                  }
                  break;
               
               case "country" :
                  if ($this->get_request_method () != "GET") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     
                     $oauthClient->fetch ( "$apiUrl/countrycollection/", array (), OAUTH_HTTP_METHOD_GET, $headers );
                     $result = $oauthClient->getLastResponse ();
                     $result = $oauthClient->getLastResponse ();
                     
                     $this->sendResponse ( 200, json_decode ( $result ) );
                  }
                  break;
               
               case "state" :
                  if ($this->get_request_method () != "GET") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     if ($this->_request ['countrycode'] && $this->_request ['countrycode'] != '') {
                        $countryCode = $this->_request ['countrycode'];
                     } else {
                        $countryCode = '';
                     }
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $oauthClient->fetch ( "$apiUrl/statecollection/$countryCode", array (), OAUTH_HTTP_METHOD_GET, $headers );
                     $result = $oauthClient->getLastResponse ();
                     
                     $this->sendResponse ( 200, json_decode ( $result ) );
                  }
                  break;
               
               case "address_collection" :
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $requestData = array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'customer_id' => ( int ) $this->_request ['customer_id'],
                           'token' => $this->_request ['token'] 
                     );
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $oauthClient->fetch ( "$apiUrl/addressbook/", $requestData, OAUTH_HTTP_METHOD_GET, $headers );
                     $result = json_decode ( $oauthClient->getLastResponse (), true );
                     $result1 = json_decode ( $result, true );
                     
                     if (! $result1 ['isValidToken']) {
                        $this->sendResponse ( 401, json_encode ( array (
                              'error' => true,
                              'message' => 'Unauthorized' 
                        ) ) );
                     } else {
                        $this->sendResponse ( 200, ($result) );
                     }
                  }
                  break;
               
               case "address_add" :
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $requestData = json_encode ( array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'customer_id' => ( int ) $this->_request ['customer_id'],
                           'token' => $this->_request ['token'],
                           'firstname' => $this->_request ['firstname'],
                           'lastname' => $this->_request ['lastname'],
                           'street' => $this->_request ['street'],
                           'city' => $this->_request ['city'],
                           'region' => $this->_request ['region'],
                           'country_id' => $this->_request ['country_id'],
                           'postcode' => $this->_request ['postcode'],
                           'telephone' => $this->_request ['telephone'],
                           'is_default_billing' => ( int ) $this->_request ['is_default_billing'],
                           'is_default_shipping' => ( int ) $this->_request ['is_default_shipping'] 
                     ) );
                     $resourceUrl = "$apiUrl/addressbook/add/";
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_POST, $headers );
                     $result = json_decode ( $oauthClient->getLastResponse (), true );
                     if (! $result ['isValidToken']) {
                        $this->sendResponse ( 401, json_encode ( array (
                              'error' => true,
                              'message' => 'Unauthorized' 
                        ) ) );
                     } else {
                        $this->sendResponse ( 200, json_encode ( $result ) );
                     }
                  }
                  break;
               
               case "address_detail" :
                  
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $requestData = (array (
                           'customer_id' => ( int ) $this->_request ['customer_id'],
                           'token' => $this->_request ['token'],
                           'id' => ( int ) $this->_request ['address_id'] 
                     ));
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $resourceUrl = "$apiUrl/address_detail/";
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_GET, $headers );
                     $result = json_decode ( $oauthClient->getLastResponse (), true );
                     if (! $result ['isValidToken']) {
                        $this->sendResponse ( 401, json_encode ( array (
                              'error' => true,
                              'message' => 'Unauthorized' 
                        ) ) );
                     } else {
                        $this->sendResponse ( 200, json_encode ( $result ) );
                     }
                  }
                  break;
               case "address_update" :
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $requestData = json_encode ( array (
                           'address_id' => ( int ) $this->_request ['address_id'],
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'customer_id' => ( int ) $this->_request ['customer_id'],
                           'token' => $this->_request ['token'],
                           'firstname' => $this->_request ['firstname'],
                           'lastname' => $this->_request ['lastname'],
                           'street' => $this->_request ['street'],
                           'city' => $this->_request ['city'],
                           'region' => $this->_request ['region'],
                           'country_id' => $this->_request ['country_id'],
                           'postcode' => $this->_request ['postcode'],
                           'telephone' => $this->_request ['telephone'],
                           'is_default_billing' => ( int ) $this->_request ['is_default_billing'],
                           'is_default_shipping' => ( int ) $this->_request ['is_default_shipping'] 
                     ) );
                     $resourceUrl = "$apiUrl/addressbook/update/";
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_PUT, $headers );
                     $result = json_decode ( $oauthClient->getLastResponse (), true );
                     if (! $result ['isValidToken']) {
                        $this->sendResponse ( 401, json_encode ( array (
                              'error' => true,
                              'message' => 'Unauthorized' 
                        ) ) );
                     } else {
                        $this->sendResponse ( 200, json_encode ( $result ) );
                     }
                  }
                  break;
               case "address_delete" :
                  
                  if ($this->get_request_method () != "DELETE") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     
                     $storeId = $this->_request ['store_id'];
                     $websiteId = $this->_request ['website_id'];
                     
                     if ($this->_request ['customer_id'] && $this->_request ['customer_id'] != '') {
                        $customerId = $this->_request ['customer_id'];
                     } else {
                        $customerId = '';
                     }
                     
                     if ($this->_request ['address_id'] && $this->_request ['address_id'] != '') {
                        $addressId = $this->_request ['address_id'];
                     } else {
                        $addressId = '';
                     }
                     $token = $this->_request ['token'];
                     
                     $resourceUrl = "$apiUrl/addressbook/delete/address_id/$addressId/customer_id/$customerId/store_id/$storeId/website_id/$websiteId/token/$token";
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $oauthClient->fetch ( $resourceUrl, '', OAUTH_HTTP_METHOD_DELETE, $headers );
                     $result = json_decode ( $oauthClient->getLastResponse (), true );
                     if (! $result ['isValidToken']) {
                        $this->sendResponse ( 401, json_encode ( array (
                              'error' => true,
                              'message' => 'Unauthorized' 
                        ) ) );
                     } else {
                        $this->sendResponse ( 200, json_encode ( $result ) );
                     }
                  }
                  break;
               case "apply_coupon" :
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $requestData = json_encode ( array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'customer_id' => ( int ) $this->_request ['customer_id'],
                           'token' => $this->_request ['token'],
                           'quote_id' => ( int ) $this->_request ['quote_id'],
                           'coupon_code' => $this->_request ['coupon_code'] 
                     ) );
                     $resourceUrl = "$apiUrl/coupon/apply/";
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_POST, $headers );
                     $result = json_decode ( $oauthClient->getLastResponse (), true );
                     if (! $result ['isValidToken']) {
                        $this->sendResponse ( 401, json_encode ( array (
                              'error' => true,
                              'message' => 'Unauthorized' 
                        ) ) );
                     } else {
                        $this->sendResponse ( 200, json_encode ( $result ) );
                     }
                  }
                  break;
               
               case "cancel_coupon" :
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $requestData = json_encode ( array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'customer_id' => ( int ) $this->_request ['customer_id'],
                           'token' => $this->_request ['token'],
                           'quote_id' => ( int ) $this->_request ['quote_id'] 
                     ) );
                     $resourceUrl = "$apiUrl/coupon/cancel/";
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_PUT, $headers );
                     $result = json_decode ( $oauthClient->getLastResponse (), true );
                     if (! $result ['isValidToken']) {
                        $this->sendResponse ( 401, json_encode ( array (
                              'error' => true,
                              'message' => 'Unauthorized' 
                        ) ) );
                     } else {
                        $this->sendResponse ( 200, json_encode ( $result ) );
                     }
                  }
                  break;
               
               case "add_address_tocart" :
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $requestData = json_encode ( array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'customer_id' => ( int ) $this->_request ['customer_id'],
                           'token' => $this->_request ['token'],
                           'quote_id' => ( int ) $this->_request ['quote_id'],
                           'billing_address_id' => ( int ) $this->_request ['billing_address_id'],
                           'shipping_address_id' => ( int ) $this->_request ['shipping_address_id'] 
                     ) );
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $resourceUrl = "$apiUrl/cart_address/add/";
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_POST, $headers );
                     $result = json_decode ( $oauthClient->getLastResponse (), true );
                     if (! $result ['isValidToken']) {
                        $this->sendResponse ( 401, json_encode ( array (
                              'error' => true,
                              'message' => 'Unauthorized' 
                        ) ) );
                     } else {
                        $this->sendResponse ( 200, json_encode ( $result ) );
                     }
                  }
                  break;
               
               case "add_shipping_payment_tocart" :
                  
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $requestData = json_encode ( array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'customer_id' => ( int ) $this->_request ['customer_id'],
                           'token' => $this->_request ['token'],
                           'quote_id' => ( int ) $this->_request ['quote_id'],
                           'shipping_method' => $this->_request ['shipping_method'],
                           'payment_method' => $this->_request ['payment_method'] 
                     ) );
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $oauthClient->fetch ( "$apiUrl/cart/add_shipping_payment/", $requestData, OAUTH_HTTP_METHOD_POST, $headers );
                     $result = json_decode ( $oauthClient->getLastResponse (), true );
                     if (! $result ['isValidToken']) {
                        $this->sendResponse ( 401, json_encode ( array (
                              'error' => true,
                              'message' => 'Unauthorized' 
                        ) ) );
                     } else {
                        $this->sendResponse ( 200, json_encode ( $result ) );
                     }
                  }
                  break;
               case "place_order" :
                  
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $surprise_gift = (( int ) $this->_request ['surprise_gift'] == 1 ? ( int ) $this->_request ['surprise_gift'] : NULL);
                     $requestData = json_encode ( array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'customer_id' => ( int ) $this->_request ['customer_id'],
                           'token' => $this->_request ['token'],
                           'quote_id' => ( int ) $this->_request ['quote_id'],
                           'shipping_method' => $this->_request ['shipping_method'],
                           'payment_method' => $this->_request ['payment_method'],
                           'currencyCode' => $this->_request ['currencyCode'],
                           'delivery_schedule_types' => $this->_request ['delivery_schedule_types'],
                           'shipping_delivery_time' => $this->_request ['shipping_delivery_time'],
                           'shipping_delivery_cost' => $this->_request ['shipping_delivery_cost'],
                           'shipping_delivery_date' => $this->_request ['shipping_delivery_date'],
                           'shipping_delivery_comments' => $this->_request ['shipping_delivery_comments'],
                           'delivery_time' => $this->_request ['delivery_time'],
                           'surprise_gift' => $surprise_gift,
                           'gift_message' => $this->_request ['gift_message'] 
                     ) );
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $resourceUrl = "$apiUrl/place_order/";
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_POST, $headers );
                     // echo $oauthClient->getLastResponse (); exit;
                     $result = json_decode ( $oauthClient->getLastResponse (), true );
                     if (! $result ['isValidToken']) {
                        $this->sendResponse ( 401, json_encode ( array (
                              'error' => true,
                              'message' => 'Unauthorized' 
                        ) ) );
                     } else {
                        
                        $this->sendResponse ( 200, json_encode ( $result ) );
                     }
                  }
                  break;
               
               case "myorders_list" :
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $page = (( int ) $this->_request ['page'] > 0 ? ( int ) $this->_request ['page'] : 1);
                     $limit = (( int ) $this->_request ['limit'] > 0 ? ( int ) $this->_request ['limit'] : 10);
                     $requestData = (array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'customer_id' => ( int ) $this->_request ['customer_id'],
                           'token' => $this->_request ['token'],
                           'page' => $page,
                           'limit' => $limit 
                     ));
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $oauthClient->fetch ( "$apiUrl/myorders/", $requestData, OAUTH_HTTP_METHOD_GET, $headers );
                     $result = json_decode ( $oauthClient->getLastResponse (), true );
                     $result1 = json_decode ( $result, true );
                     if (! $result1 ['isValidToken']) {
                        $this->sendResponse ( 401, json_encode ( array (
                              'error' => true,
                              'message' => 'Unauthorized' 
                        ) ) );
                     } else {
                        
                        $this->sendResponse ( 200, (($result)) );
                     }
                  }
                  break;
               
               case "myorder_detail" :
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $requestData = (array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'customer_id' => ( int ) $this->_request ['customer_id'],
                           'token' => $this->_request ['token'],
                           'id' => ( int ) $this->_request ['order_id'] 
                     ));
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $oauthClient->fetch ( "$apiUrl/myorder_detail/", $requestData, OAUTH_HTTP_METHOD_GET, $headers );
                     $result = json_decode ( $oauthClient->getLastResponse (), true );
                     $result1 = json_decode ( $result, true );
                     if (! $result1 ['isValidToken']) {
                        $this->sendResponse ( 401, json_encode ( array (
                              'error' => true,
                              'message' => 'Unauthorized' 
                        ) ) );
                     } else {
                        
                        $this->sendResponse ( 200, ($result) );
                     }
                  }
                  break;
               
               case "add_wishlist" :
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $requestData = json_encode ( array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'customer_id' => ( int ) $this->_request ['customer_id'],
                           'token' => $this->_request ['token'],
                           'product_id' => ( int ) $this->_request ['product_id'],
                           'qty' => ( int ) $this->_request ['qty'] 
                     ) );
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $resourceUrl = "$apiUrl//wishlist/add/";
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_POST, $headers );
                     
                     $result = json_decode ( $oauthClient->getLastResponse (), true );
                     
                     if (! $result ['isValidToken']) {
                        $this->sendResponse ( 401, json_encode ( array (
                              'error' => true,
                              'message' => 'Unauthorized' 
                        ) ) );
                     } else {
                        
                        $this->sendResponse ( 200, json_encode ( $result ) );
                     }
                  }
                  break;
               case "wishlist_list" :
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $requestData = (array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'customer_id' => ( int ) $this->_request ['customer_id'],
                           'token' => $this->_request ['token'],
                           'page' => ( int ) $this->_request ['page'],
                           'limit' => ( int ) $this->_request ['limit'],
                           'image_size' => ( int ) $this->_request ['image_size'] 
                     ));
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $resourceUrl = "$apiUrl/wishlist/productlist/";
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_GET, $headers );
                     
                     $result = json_decode ( $oauthClient->getLastResponse (), true );
                     if (! $result ['isValidToken']) {
                        $this->sendResponse ( 401, json_encode ( array (
                              'error' => true,
                              'message' => 'Unauthorized' 
                        ) ) );
                     } else {
                        
                        $this->sendResponse ( 200, ($result) );
                     }
                  }
                  break;
               
               case "delete_wishlist" :
                  if ($this->get_request_method () != "DELETE") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     
                     $storeId = $this->_request ['store_id'];
                     $websiteId = $this->_request ['website_id'];
                     
                     if ($this->_request ['customer_id'] && $this->_request ['customer_id'] != '') {
                        $customerId = $this->_request ['customer_id'];
                     } else {
                        $customerId = '';
                     }
                     
                     if ($this->_request ['product_id'] && $this->_request ['product_id'] != '') {
                        $productId = $this->_request ['product_id'];
                     } else {
                        $productId = '';
                     }
                     $token = $this->_request ['token'];
                     
                     $resourceUrl = "$apiUrl/wishlist/delete/token/$token/product_id/$productId/customer_id/$customerId/store_id/$storeId/website_id/$websiteId/";
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $oauthClient->fetch ( $resourceUrl, '', OAUTH_HTTP_METHOD_DELETE, $headers );
                     
                     $result = json_decode ( $oauthClient->getLastResponse (), true );
                     if (! $result ['isValidToken']) {
                        $this->sendResponse ( 401, json_encode ( array (
                              'error' => true,
                              'message' => 'Unauthorized' 
                        ) ) );
                     } else {
                        
                        $this->sendResponse ( 200, json_encode ( $result ) );
                     }
                  }
                  break;
               
               case "add_review" :
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $requestData = json_encode ( array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'customer_id' => ( int ) $this->_request ['customer_id'],
                           'token' => $this->_request ['token'],
                           'product_id' => ( int ) $this->_request ['product_id'],
                           'customer_name' => $this->_request ['customer_name'],
                           'review_title' => $this->_request ['review_title'],
                           'review_description' => $this->_request ['review_description'],
                           'review_status' => ( int ) $this->_request ['review_status'],
                           'rating' => $this->_request ['rating'] 
                     ) );
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $resourceUrl = "$apiUrl/add/reviews/";
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_POST, $headers );
                     $result = json_decode ( $oauthClient->getLastResponse (), true );
                     if (! $result ['isValidToken']) {
                        $this->sendResponse ( 401, json_encode ( array (
                              'error' => true,
                              'message' => 'Unauthorized' 
                        ) ) );
                     } else {
                        
                        $this->sendResponse ( 200, json_encode ( $result ) );
                     }
                  }
                  break;
               
               case "reviews_list" :
                  if ($this->get_request_method () != "GET") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $requestData = (array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'product_id' => ( int ) $this->_request ['product_id'],
                           'page' => ( int ) $this->_request ['page'],
                           'limit' => ( int ) $this->_request ['limit'] 
                     ));
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $resourceUrl = "$apiUrl/reviews/list/";
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_GET, $headers );
                     $result = $oauthClient->getLastResponse ();
                     $this->sendResponse ( 200, ($result) );
                  }
                  break;
               
               case "filters" :
                  
                  if ($this->get_request_method () != "GET") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $requestData = array (
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'category_id' => ( int ) $this->_request ['category_id'] 
                     );
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $resourceUrl = "$apiUrl/category/filters/";
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_GET, $headers );
                     $result = $oauthClient->getLastResponse ();
                     
                     $this->sendResponse ( 200, json_decode ( $result ) );
                  }
                  break;
               
               case "my_downloadable" :
                  
                  $page = (( int ) $this->_request ['page'] > 0 ? ( int ) $this->_request ['page'] : 1);
                  $limit = (( int ) $this->_request ['limit'] > 0 ? ( int ) $this->_request ['limit'] : 10);
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $requestData = json_encode ( array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'customer_id' => ( int ) $this->_request ['customer_id'],
                           'token' => $this->_request ['token'],
                           
                           'page' => $page,
                           'limit' => $limit 
                     ) );
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $resourceUrl = "$apiUrl/my_download/products/";
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_POST, $headers );
                     $result = json_decode ( $oauthClient->getLastResponse (), true );
                     if (! $result ['isValidToken']) {
                        $this->sendResponse ( 401, json_encode ( array (
                              'error' => true,
                              'message' => 'Unauthorized' 
                        ) ) );
                     } else {
                        
                        $this->sendResponse ( 200, json_encode ( $result ) );
                     }
                  }
                  break;
               
               case "bulk_add_tocart" :
                  
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $requestData = json_encode ( array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'customer_id' => ( int ) $this->_request ['customer_id'],
                           'token' => $this->_request ['token'],
                           'quote_id' => ( int ) $this->_request ['quote_id'],
                           'currencyCode' => $this->_request ['currencyCode'],
                           'detail' => json_decode ( $this->_request ['detail'] ) 
                     ) );
                     
                     $resourceUrl = "$apiUrl/cart/bulk_add/";
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_POST, $headers );
                     // echo $oauthClient->getLastResponse (); exit;
                     $result = json_decode ( $oauthClient->getLastResponse (), true );
                     if (! $result ['isValidToken']) {
                        $this->sendResponse ( 401, json_encode ( array (
                              'error' => true,
                              'message' => 'Unauthorized' 
                        ) ) );
                     } else {
                        
                        $this->sendResponse ( 200, json_encode ( $result ) );
                     }
                  }
                  break;
               
               case "bulk_update_cart" :
                  
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $requestData = json_encode ( array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'customer_id' => ( int ) $this->_request ['customer_id'],
                           'token' => $this->_request ['token'],
                           'quote_id' => ( int ) $this->_request ['quote_id'],
                           'currencyCode' => $this->_request ['currencyCode'],
                           'detail' => json_decode ( $this->_request ['detail'] ) 
                     ) );
                     
                     $resourceUrl = "$apiUrl/cart/bulk_update/";
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_PUT, $headers );
                     $result = json_decode ( $oauthClient->getLastResponse (), true );
                     if (! $result ['isValidToken']) {
                        $this->sendResponse ( 401, json_encode ( array (
                              'error' => true,
                              'message' => 'Unauthorized' 
                        ) ) );
                     } else {
                        
                        $this->sendResponse ( 200, json_encode ( $result ) );
                     }
                  }
                  break;
               
               case "cart_add_address" :
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $requestData = json_encode ( array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'customer_id' => ( int ) $this->_request ['customer_id'],
                           'token' => $this->_request ['token'],
                           'quote_id' => ( int ) $this->_request ['quote_id'],
                           'billing_address_id' => ( int ) $this->_request ['billing_address_id'],
                           'shipping_address_id' => ( int ) $this->_request ['shipping_address_id'] 
                     ) );
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $resourceUrl = "$apiUrl/cart_add_address/";
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_POST, $headers );
                     $result = json_decode ( $oauthClient->getLastResponse (), true );
                     if (! $result ['isValidToken']) {
                        $this->sendResponse ( 401, json_encode ( array (
                              'error' => true,
                              'message' => 'Unauthorized' 
                        ) ) );
                     } else {
                        
                        $this->sendResponse ( 200, json_encode ( $result ) );
                     }
                  }
                  break;
               
               case "clear_wishlist" :
                  
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     
                     $requestData = json_encode ( array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'customer_id' => ( int ) $this->_request ['customer_id'],
                           'token' => $this->_request ['token'] 
                     ) );
                     
                     $resourceUrl = "$apiUrl/wishlist/clear/";
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_PUT, $headers );
                     $result = json_decode ( $oauthClient->getLastResponse (), true );
                     if (! $result ['isValidToken']) {
                        $this->sendResponse ( 401, json_encode ( array (
                              'error' => true,
                              'message' => 'Unauthorized' 
                        ) ) );
                     } else {
                        $this->sendResponse ( 200, json_encode ( $result ) );
                     }
                  }
                  break;
               
               case "offer_products" :
                  
                  if ($this->get_request_method () != "GET") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     
                     $requestData = array (
                           'offer_id' => ( int ) $this->_request ['offer_id'],
                           'customer_id' => ( int ) $this->_request ['customer_id'],
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'page' => ( int ) $this->_request ['page'],
                           'limit' => ( int ) $this->_request ['limit'],
                           'image_size' => ( int ) $this->_request ['image_size'],
                           'city' => ( int ) $this->_request ['city'] 
                     );
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $resourceUrl = "$apiUrl/offer_products/";
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_GET, $headers );
                     
                     $this->sendResponse ( 200, $oauthClient->getLastResponse () );
                  }
                  break;
               
               case "gcmtoken_update" :
                  
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $requestData = json_encode ( array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'customer_id' => ( int ) $this->_request ['customer_id'],
                           'device_token' => $this->_request ['device_token'],
                           'device_type' => $this->_request ['device_type'] 
                     ) );
                     
                     $resourceUrl = "$apiUrl/customer/gcmtoken_update/";
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_PUT, $headers );
                     $this->sendResponse ( 200, $oauthClient->getLastResponse () );
                  }
                  break;
               case "contact_details" :
                  
                  if ($this->get_request_method () != "GET") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     
                     $requestData = array (
                           
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'] 
                     );
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $resourceUrl = "$apiUrl/contact_details/";
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_GET, $headers );
                     
                     $this->sendResponse ( 200, $oauthClient->getLastResponse () );
                  }
                  break;
               case "contact_form" :
                  
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $requestData = json_encode ( array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'email' => $this->_request ['email'],
                           'name' => $this->_request ['name'],
                           'telephone' => $this->_request ['telephone'],
                           'comment' => $this->_request ['comment'] 
                     ) );
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $resourceUrl = "$apiUrl/contactus/";
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_POST, $headers );
                     $result = $oauthClient->getLastResponse ();
                     $this->sendResponse ( 200, ($result) );
                  }
                  break;
               
               case "reorder" :
                  
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $requestData = json_encode ( array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'customer_id' => ( int ) $this->_request ['customer_id'],
                           'token' => $this->_request ['token'],
                           'order_id' => $this->_request ['order_id'] 
                     ) );
                     
                     $resourceUrl = "$apiUrl/reorder/";
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_PUT, $headers );
                     // echo $oauthClient->getLastResponse (); exit;
                     $result = json_decode ( $oauthClient->getLastResponse (), true );
                     if (! $result ['isValidToken']) {
                        $this->sendResponse ( 401, json_encode ( array (
                              'error' => true,
                              'message' => 'Unauthorized' 
                        ) ) );
                     } else {
                        $this->sendResponse ( 200, json_encode ( $result ) );
                     }
                  }
                  break;
               
               case "seller_info" :
                  
                  if ($this->get_request_method () != "GET") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $page = (( int ) $this->_request ['page'] > 0 ? ( int ) $this->_request ['page'] : 1);
                     $limit = (( int ) $this->_request ['limit'] > 0 ? ( int ) $this->_request ['limit'] : 10);
                     $requestData = array (
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'seller_id' => ( int ) $this->_request ['seller_id'],
                           'image_size' => ( int ) $this->_request ['image_size'],
                           'city' => ( int ) $this->_request ['city'],
                           'page' => $page,
                           'limit' => $limit 
                     );
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $resourceUrl = "$apiUrl/sellers/profile/";
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_GET, $headers );
                     if (Mage::getStoreConfig ( 'marketplace/marketplace/activate' )) {
                        $result = $oauthClient->getLastResponse ();
                     } else {
                        $result ['error'] = true;
                        $result ['success'] = 0;
                        $result ['total_count'] = 0;
                        $result ['message'] = "Enable the market place";
                        $result ['result'] = [ ];
                        echo $result = json_encode ( $result );
                     }
                     $this->sendResponse ( 200, json_decode ( $result ) );
                  }
                  break;
               
               case "sellerslist" :
                  $page = (( int ) $this->_request ['page'] > 0 ? ( int ) $this->_request ['page'] : 1);
                  $limit = (( int ) $this->_request ['limit'] > 0 ? ( int ) $this->_request ['limit'] : 10);
                  if ($this->get_request_method () != "GET") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     
                     $requestData = array (
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'page' => $page,
                           'limit' => $limit 
                     );
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     
                     $resourceUrl = "$apiUrl/sellers/list/";
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_GET, $headers );
                     if (Mage::getStoreConfig ( 'marketplace/marketplace/activate' )) {
                        $result = $oauthClient->getLastResponse ();
                     } else {
                        // echo $result = "Enable the market place";
                        $result ['error'] = true;
                        $result ['success'] = 0;
                        $result ['total_count'] = 0;
                        $result ['message'] = "Enable the market place";
                        $result ['result'] = [ ];
                        echo $result = json_encode ( $result );
                     }
                     $this->sendResponse ( 200, json_decode ( $result ) );
                  }
                  break;
               
               case "sellerreview" :
                  
                  if ($this->get_request_method () != "POST") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $requestData = json_encode ( array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'seller_id' => ( int ) $this->_request ['seller_id'],
                           'ratings' => ( int ) $this->_request ['ratings'],
                           'feedback' => $this->_request ['feedback'],
                           'product_id' => ( int ) $this->_request ['product_id'],
                           'customer_id' => ( int ) $this->_request ['customer_id'] 
                     ) );
                     
                     $resourceUrl = "$apiUrl/sellers/rating/";
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_POST, $headers );
                     if (Mage::getStoreConfig ( 'marketplace/marketplace/activate' )) {
                        $result = $oauthClient->getLastResponse ();
                     } else {
                        $result ['error'] = true;
                        $result ['success'] = 0;
                        
                        $result ['message'] = "Enable the market place";
                        $result ['result'] = [ ];
                        echo $result = json_encode ( $result );
                     }
                     $this->sendResponse ( 200, ($result) );
                  }
                  break;
               
               case "sellerreview_list" :
                  
                  if ($this->get_request_method () != "GET") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $page = (( int ) $this->_request ['page'] > 0 ? ( int ) $this->_request ['page'] : 1);
                     $limit = (( int ) $this->_request ['limit'] > 0 ? ( int ) $this->_request ['limit'] : 10);
                     $requestData = array (
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'seller_id' => ( int ) $this->_request ['seller_id'],
                           'page' => $page,
                           'limit' => $limit 
                     );
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $resourceUrl = "$apiUrl/seller/reviews/list/";
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_GET, $headers );
                     if (Mage::getStoreConfig ( 'marketplace/marketplace/activate' )) {
                        $result = $oauthClient->getLastResponse ();
                     } else {
                        $result ['error'] = true;
                        $result ['success'] = 0;
                        $result ['message'] = "Enable the market place";
                        $result ['result'] = [ ];
                        echo $result = json_encode ( $result );
                     }
                     $this->sendResponse ( 200, json_decode ( $result ) );
                  }
                  break;
               
               case "pre_search" :
                  $search_term = str_replace ( ' ', '%20', $this->_request ['search_term'] );
                  if ($this->get_request_method () != "GET") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $requestData = array (
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'],
                           // 'category_id' => ( int ) $this->_request ['category_id'],
                           
                           'search_term' => $this->_request ['search_term'] 
                     // 'page' => $this->_request ['page'],
                     // 'limit' => $this->_request ['limit'],
                     // 'image_size' => ( int ) $this->_request ['image_size']
                                          );
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     
                     $resourceUrl = "$apiUrl/predictive/search/";
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_GET, $headers );
                     $result = $oauthClient->getLastResponse ();
                     
                     $this->sendResponse ( 200, ($result) );
                  }
                  break;
               
               case "delivery_info" :
                  
                  if ($this->get_request_method () != "GET") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $requestData = (array (
                           
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'store_id' => ( int ) $this->_request ['store_id'] 
                     ));
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $resourceUrl = "$apiUrl/delivery/info/";
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_GET, $headers );
                     $result = $oauthClient->getLastResponse ();
                     // echo $oauthClient->getLastResponse (); exit;
                     
                     $this->sendResponse ( 200, ($result) );
                  }
                  break;
               
               case "products" :
                  
                  if ($this->get_request_method () != "GET") {
                     $this->sendResponse ( 400, json_encode ( array (
                           'error' => true,
                           'message' => 'Bad Request' 
                     ) ) );
                  } else {
                     $requestData = array (
                           'store_id' => ( int ) $this->_request ['store_id'],
                           'website_id' => ( int ) $this->_request ['website_id'],
                           'page' => ( int ) $this->_request ['page'],
                           'limit' => ( int ) $this->_request ['limit'],
                           'category_id' => ( int ) $this->_request ['category_id'],
                           'customer_id' => ( int ) $this->_request ['customer_id'],
                           'orderby' => $this->_request ['orderby'],
                           'sortby' => $this->_request ['sortby'],
                           
                           'filters' => $this->_request ['filters'],
                           'image_size' => ( int ) $this->_request ['image_size'] 
                     );
                     $headers = array (
                           'Content-Type' => 'application/json',
                           'Accept' => '*/*' 
                     );
                     $resourceUrl = "$apiUrl/productlist/";
                     $oauthClient->fetch ( $resourceUrl, $requestData, OAUTH_HTTP_METHOD_GET, $headers );
                     $result = $oauthClient->getLastResponse ();
                     $this->sendResponse ( 200, json_decode ( $result ) );
                  }
                  break;
               
               default :
                  $this->sendResponse ( 405, json_encode ( array (
                        'error' => true,
                        'message' => 'Method Not Allowed' 
                  ) ) );
                  break;
            }
         } catch ( OAuthException $e ) {
            
            $errorMessage = json_decode ( $e->lastResponse );
            $error ['message'] = $errorMessage->messages->error [0]->message;
            $error ['code'] = $errorMessage->messages->error [0]->code;
            
            $this->sendResponse ( $error ['code'], json_encode ( array (
                  'error' => true,
                  'message' => (isset ( $error ['message'] )) ? $error ['message'] : $e->getMessage (),
                  'success' => 0 
            ) ) );
         }
      } catch ( OAuthException $e ) {
         $errorMessage = json_decode ( $e->lastResponse );
         $error ['message'] = $errorMessage->messages->error [0]->message;
         $error ['code'] = $errorMessage->messages->error [0]->code;
         $this->sendResponse ( $error ['code'], json_encode ( array (
               'error' => true,
               'message' => (isset ( $error ['message'] )) ? $error ['message'] : $e->getMessage (),
               'success' => 0 
         ) ) );
      }
   }
   
   // Encode array into JSON
   private function json($data) {
      if (is_array ( $data )) {
         return json_encode ( $data );
      }
   }
}
// instantiated Library
$api = new API ();
$api->processApi ();
?>