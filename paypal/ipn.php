<?php
require '../app/Mage.php';

Mage::App (); // might be "default"
$_SERVER ['SCRIPT_NAME'] = str_replace ( basename ( __FILE__ ), 'index.php', $_SERVER ['SCRIPT_NAME'] );
$_SERVER ['SCRIPT_FILENAME'] = str_replace ( basename ( __FILE__ ), 'index.php', $_SERVER ['SCRIPT_FILENAME'] );
// $explodeDetail = explode('-',$_SESSION['pxpayval']);

$transaction_id = $_POST ['verify_sign'];
$order_id = $_POST ['invoice'];
$status = $_POST ['payment_status'];
$email = $_POST ['payer_email'];
$name = $_POST ['first_name'];
$pendingreason = $_POST ['pending_reason'];
$txnid = $_POST ['txn_id'];
$mcgross = $_POST ['mc_gross'];
$refundreason = $_POST ['reason_code'];

$verified = false;
// Check to see there are posted variables coming into the script
if ($_SERVER ['REQUEST_METHOD'] != "POST")
    die ( "No Post Variables" );
    
    // CONFIG: Enable debug mode. This means we'll log requests into 'ipn.log' in the same directory.
    // Especially useful if you encounter network errors or other intermittent problems with IPN (validation).
    // Set this to 0 once you go live or don't require logging.
define ( "DEBUG", 1 );
// Set to 0 once you're ready to go live
define ( "USE_SANDBOX", 1 );
define ( "LOG_FILE", "./ipn.log" );
// Read POST data
// reading posted data directly from $_POST causes serialization
// issues with array data in POST. Reading raw POST data from input stream instead.
$raw_post_data = file_get_contents ( 'php://input' );
$raw_post_array = explode ( '&', $raw_post_data );
$myPost = array ();
foreach ( $raw_post_array as $keyval ) {
    $keyval = explode ( '=', $keyval );
    if (count ( $keyval ) == 2)
        $myPost [$keyval [0]] = urldecode ( $keyval [1] );
}
// read the post from PayPal system and add 'cmd'
$req = 'cmd=_notify-validate';
if (function_exists ( 'get_magic_quotes_gpc' )) {
    $get_magic_quotes_exists = true;
}
foreach ( $myPost as $key => $value ) {
    if ($get_magic_quotes_exists == true && get_magic_quotes_gpc () == 1) {
        $value = urlencode ( stripslashes ( $value ) );
    } else {
        $value = urlencode ( $value );
    }
    $req .= "&$key=$value";
}
// Post IPN data back to PayPal to validate the IPN data is genuine
// Without this step anyone can fake IPN data
if (USE_SANDBOX == true) {
    $paypal_url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
} else {
    $paypal_url = "https://www.paypal.com/cgi-bin/webscr";
}
$ch = curl_init ( $paypal_url );
if ($ch == FALSE) {
    return FALSE;
    $verified = false;
}
curl_setopt ( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
curl_setopt ( $ch, CURLOPT_POST, 1 );
curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
curl_setopt ( $ch, CURLOPT_POSTFIELDS, $req );
curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
curl_setopt ( $ch, CURLOPT_FORBID_REUSE, 1 );
if (DEBUG == true) {
    curl_setopt ( $ch, CURLOPT_HEADER, 1 );
    curl_setopt ( $ch, CURLINFO_HEADER_OUT, 1 );
}
// CONFIG: Optional proxy configuration
// curl_setopt($ch, CURLOPT_PROXY, $proxy);
// curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
// Set TCP timeout to 30 seconds
curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 30 );
curl_setopt ( $ch, CURLOPT_HTTPHEADER, array (
        'Connection: Close' 
) );
// CONFIG: Please download 'cacert.pem' from "http://curl.haxx.se/docs/caextract.html" and set the directory path
// of the certificate as shown below. Ensure the file is readable by the webserver.
// This is mandatory for some environments.
// $cert = __DIR__ . "./cacert.pem";
// curl_setopt($ch, CURLOPT_CAINFO, $cert);
$res = curl_exec ( $ch );
if (curl_errno ( $ch ) != 0) // cURL error
{
    if (DEBUG == true) {
        error_log ( date ( '[Y-m-d H:i e] ' ) . "Can't connect to PayPal to validate IPN message: " . curl_error ( $ch ) . PHP_EOL, 3, LOG_FILE );
    }
    curl_close ( $ch );
    $verified = false;
} else {
    // Log the entire HTTP response if debug is switched on.
    if (DEBUG == true) {
        error_log ( date ( '[Y-m-d H:i e] ' ) . "HTTP request of validation request:" . curl_getinfo ( $ch, CURLINFO_HEADER_OUT ) . " for IPN payload: $req" . PHP_EOL, 3, LOG_FILE );
        error_log ( date ( '[Y-m-d H:i e] ' ) . "HTTP response of validation request: $res" . PHP_EOL, 3, LOG_FILE );
    }
    curl_close ( $ch );
}
// Inspect IPN validation result and act accordingly
// Split response headers and payload, a better way for strcmp
$tokens = explode ( "\r\n\r\n", trim ( $res ) );
$res = trim ( end ( $tokens ) );
if (strcmp ( $res, "VERIFIED" ) == 0) {
    // check whether the payment_status is Completed
    // check that txn_id has not been previously processed
    // check that receiver_email is your PayPal email
    // check that payment_amount/payment_currency are correct
    // process payment and mark item as paid.
    // assign posted variables to local variables
    // $item_name = $_POST['item_name'];
    // $item_number = $_POST['item_number'];
    // $payment_status = $_POST['payment_status'];
    // $payment_amount = $_POST['mc_gross'];
    // $payment_currency = $_POST['mc_currency'];
    // $txn_id = $_POST['txn_id'];
    // $receiver_email = $_POST['receiver_email'];
    // $payer_email = $_POST['payer_email'];
    
    if (DEBUG == true) {
        error_log ( date ( '[Y-m-d H:i e] ' ) . "Verified IPN: $req " . PHP_EOL, 3, LOG_FILE );
    }
    $verified = true;
} else if (strcmp ( $res, "INVALID" ) == 0) {
    
    // log for manual investigation
    // Add business logic here which deals with invalid IPN messages
    if (DEBUG == true) {
        error_log ( date ( '[Y-m-d H:i e] ' ) . "Invalid IPN: $req" . PHP_EOL, 3, LOG_FILE );
    }
    $verified = false;
} else {
    $verified = false;
    
    error_log ( date ( '[Y-m-d H:i e] ' ) . "Error IPN: $req" . PHP_EOL, 3, LOG_FILE );
}

/*
 * CHECK THESE 4 THINGS BEFORE PROCESSING THE TRANSACTION, HANDLE THEM AS YOU WISH 1. Make sure that business email returned is your business email 2. Make sure that the transaction�s payment status is �completed� 3. Make sure there are no duplicate txn_id 4. Make sure the payment amount matches what you charge for items. (Defeat Price-Jacking)
 */

// Check Number 1 ------------------------------------------------------------------------------------------------------------
$receiver_email = $_POST ['receiver_email'];
if ($receiver_email != "subhasini@contus.in") {
    fwrite ( $fh, 'business email returned is not  your business email' );
    $verified = false;
    // handle the wrong business url
} else {
    $verified = true;
}

// Check number 2 ------------------------------------------------------------------------------------------------------------
$order = Mage::getModel ( 'sales/order' )->loadByIncrementId ( $order_id );

if ($verified) {
    
    if ($order->getId ()) {
        
        if (! $order->getEmailSent ()) {
            $order->sendNewOrderEmail ();
            $order->setEmailSent ( true );
            $order->save ();
        }
    }
    
    $payment = $order->getPayment ();
    
    $myvalue = " case==>" . $status;
    fwrite ( $fh, $myvalue );
    try {
        switch ($status) {
            case 'Completed' :
                
                $payment->setTransactionId ( $txnid )->setParentTransactionId ( $txnid )->setShouldCloseParentTransaction ( 'Completed' === $status )->setIsTransactionClosed ( 0 )->registerCaptureNotification ( $mcgross );
                $order->save ();
                
                // notify customer
                
                $invoice = $payment->getCreatedInvoice ();
                if ($invoice && ! $order->getEmailSent ()) {
                    $order->sendNewOrderEmail ()->addStatusHistoryComment ( Mage::helper ( 'paypal' )->__ ( 'Notified customer about invoice #%s.', $invoice->getIncrementId () ) )->setIsCustomerNotified ( true )->save ();
                }
                
                break;
            case 'Denied' :
                $myvalue = "denied case" . $status;
                fwrite ( $fh, $myvalue );
                $payment->setTransactionId ( $txnid )->setNotificationResult ( true )->setIsTransactionClosed ( true )->registerPaymentReviewAction ( Mage_Sales_Model_Order_Payment::REVIEW_ACTION_DENY, false );
                $order->save ();
                
                break;
            case 'Failed' :
                
                $order->registerCancellation ( createIpnComment ( $status ), false )->save ();
                
                break;
            case 'Reversed' :
                $myvalue = "reversed case" . $status;
                fwrite ( $fh, $myvalue );
                break;
            case 'Canceled_Reversal' :
                // $myvalue = "Canceled_Reversal case".$status;
                // fwrite($fh, $myvalue);
                break;
            case 'Refunded' :
                
                $reason = $refundreason;
                $myvalue = "refund case" . $refundreason;
                fwrite ( $fh, $myvalue );
                $isRefundFinal = ! 0;
                $payment->setPreparedMessage ( createIpnComment ( $reason ) )->setTransactionId ( $txnid )->setParentTransactionId ( $txnid )->setIsTransactionClosed ( $isRefundFinal )->registerRefundNotification ( - 1 * $mcgross );
                $order->save ();
                
                // TODO: there is no way to close a capture right now
                
                if ($creditmemo = $payment->getCreatedCreditmemo ()) {
                    $creditmemo->sendEmail ();
                    $comment = $order->addStatusHistoryComment ( Mage::helper ( 'paypal' )->__ ( 'Notified customer about creditmemo #%s.', $creditmemo->getIncrementId () ) )->setIsCustomerNotified ( true )->save ();
                }
                
                break;
            case 'Pending' :
                
                $reason = $pendingreason;
                if ('authorization' === $reason) {
                    registerPaymentAuthorization ();
                    return;
                }
                if ('order' === $reason) {
                    throw new Exception ( 'The "order" authorizations are not implemented.' );
                }
                
                // case when was placed using PayPal standard
                if (Mage_Sales_Model_Order::STATE_PENDING_PAYMENT == $order->getState ()) {
                }
                
                $payment->setPreparedMessage ( createIpnComment ( explainPendingReason ( $reason ) ) )->setTransactionId ( $txnid )->setIsTransactionClosed ( 0 )->registerPaymentReviewAction ( Mage_Sales_Model_Order_Payment::REVIEW_ACTION_UPDATE, false );
                $order->save ();
                
                $order->setState ( Mage_Sales_Model_Order::STATE_PENDING_PAYMENT );
                $order->setStatus ( Mage_Sales_Model_Order::STATE_PENDING_PAYMENT );
                
                $message = Mage::helper ( 'paypal' )->__ ( 'Payment pending#%s.', $order_id );
                $order->addStatusToHistory ( $status, $message, true );
                $order->save ();
                
                break;
            case 'Processed' :
                $myvalue = "Processed";
                fwrite ( $fh, $myvalue );
                $comment = createIpnComment ( '', true );
                $comment->save ();
                break;
            case 'Voided' :
                $myvalue = "Voided";
                fwrite ( $fh, $myvalue );
                $payment->getPayment ()->setPreparedMessage ( createIpnComment ( '' ) )->setParentTransactionId ( $txnid )->registerVoidNotification ();
                
                $order->save ();
                
                break;
            default :
                $myvalue2 = "default";
                fwrite ( $fh, $myvalue2 );
                $order->setState ( Mage_Sales_Model_Order::STATE_CANCELED );
                $order->setStatus ( Mage_Sales_Model_Order::STATE_CANCELED );
                
                break;
        }
    } catch ( Mage_Core_Exception $e ) {
        $myvalue1 = "ex" . $e->getMessage ();
        fwrite ( $fh, $myvalue1 );
        $comment = createIpnComment ( Mage::helper ( 'paypal' )->__ ( 'Note: %s', $e->getMessage () ), true );
        $comment->save ();
        throw $e;
    }
} else {
}
function registerPaymentAuthorization() {
    $order->getPayment ()->setPreparedMessage ( $createIpnComment ( '' ) )->setTransactionId ( $txnid )->setParentTransactionId ( $txnid )->setIsTransactionClosed ( 0 )->registerAuthorizationNotification ( $mcgross );
    if (! $_order->getEmailSent ()) {
        $_order->sendNewOrderEmail ();
    }
    $order->save ();
}
function createIpnComment($paymentStatus) {
    $comment = '';
    $addToHistory = false;
    
    $message = Mage::helper ( 'paypal' )->__ ( 'IPN ' . $order_id . '"%s".', $paymentStatus );
    if ($comment) {
        $message .= ' ' . $comment;
    }
    if ($addToHistory) {
        $message = $order->addStatusHistoryComment ( $message );
        $message->setIsCustomerNotified ( null );
    }
    return $message;
}

?>
