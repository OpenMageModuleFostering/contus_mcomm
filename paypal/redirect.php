<?php
require_once ('../app/Mage.php');
umask ( 0 );
Mage::app ();
Mage::getSingleton ( 'core/session', array (
        'name' => 'frontend' 
) );
Mage::getSingleton ( 'checkout/session', array (
        'name' => 'frontend' 
) );

include_once ('Paypal.php');

  $order_Id = $_GET ['id'];

$order = Mage::getModel ( 'sales/order' )->load ( $order_Id );
$order = Mage::getModel('sales/order')->loadByIncrementId($order_Id);

$orderId = $order->getIncrementId ();

$result = $order->getAllVisibleItems ();

  $currency_code = $order ['base_currency_code'];

$shipping = round ( $order ['base_shipping_amount'], 2 );
$weight = round ( $order ['Weight'], 2 );
$ship_method = $order ['Shipping_description'];
$tax = trim ( round ( $order ['tax_amount'], 2 ) );
$discountAmount = $order ['discount_amount'];
 $discountAmount = number_format($discountAmount, 2, '.', ''); 
 if($discountAmount){
     $discountAmount = $discountAmount *(-1);
 }
 $amount = round ( $order ['grand_total'], 2 );

$customerid = $order ['customer_id'];
$customer = Mage::getModel ( 'customer/customer' )->load ( $customerid );

$billingaddress = Mage::getModel ( 'customer/address' )->load ( $customer->default_billing );
$shippingaddress = Mage::getModel ( 'customer/address' )->load ( $customer->default_shipping );

$city = $billingaddress ['city'];
$country = $billingaddress ['country'];
$email = $billingaddress ['customer_email'];
$firstname = $billingaddress ['firstname'];
$lastname = $billingaddress ['lastname'];
$postcode = $billingaddress ['Postcode'];
$region = $billingaddress ['region'];
$street = $billingaddress ['street'];

$telephone = $billingaddress ['telephone'];
$regioncode = $billingaddress ['region_code'];

if ($shippingaddress ['customer_email']) {
    
    $city = $shippingaddress ['city'];
    $country = $shippingaddress ['country'];
    $email = $shippingaddress ['customer_email'];
    $firstname = $shippingaddress ['firstname'];
    $lastname = $shippingaddress ['lastname'];
    $postcode = $shippingaddress ['Postcode'];
    $region = $shippingaddress ['region'];
    $street = $shippingaddress ['street'];
    
    $telephone = $shippingaddress ['telephone'];
    $regioncode = $shippingaddress ['region_code'];
}

$post_code = preg_replace ( '@[^\d]@', '', $postcode );

$paypalEmail = Mage::getStoreConfig ( 'paypal/general/business_account' );

$demo = Mage::getStoreConfig ( 'payment/paypal_standard/sandbox_flag' );
$demo = 1;
$payAction = Mage::getStoreConfig ( 'payment/paypal_standard/payment_action' );

$websiteData = Mage::app ()->getStore ()->getWebsite ()->getData ();
$storeName = $websiteData ['name'];
$baseUrl = Mage::getStoreConfig ( 'web/secure/base_url' );

if ($demo == 1) {
    $url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
} else {
    $url = "https://www.paypal.com/cgi-bin/webscr";
}
// echo $url; exit;

$redirecturl = $baseUrl . "paypal/";
?>
<html>
<body>
	You will be redirected to the PayPal website in a few seconds.
	<form action="<?php echo $url;?>" id="paypal_standard_checkout"
		name="paypal_standard_checkout" method="POST">
		<div>
			<input name="form_key" type="hidden" value="<?php echo Mage::helper('core')->getRandomString(16); ?>" />
		</div>

		<input id="business" name="business" value="<?php echo $paypalEmail;?>" type="hidden" />
	    <input id="invoice" name="invoice" value="<?php echo $orderId;?>" type="hidden" /> 
		<input id="currency_code" name="currency_code" value="<?php echo $currency_code; ?>" type="hidden" /> 
		<input id="paymentaction" name="paymentaction" value="<?php echo strtolower($payAction);?>" type="hidden" />
		<input id="return" name="return" value="<?php echo $redirecturl.'success.php'?>" type="hidden" /> 
		<input id="cancel_return" name="cancel_return" value="<?php echo $redirecturl.'cancel.php'?>" type="hidden" /> 
		 <input	id="notify_url" name="notify_url" value="<?php echo $redirecturl.'ipn.php'?>" type="hidden" /> 
		<!--<input	id="notify_url" name="notify_url" value="<?php echo $redirecturl.'my_ipn.php'?>" type="hidden" />-->
		<input	id="bn" name="bn" value="Varien_Cart_WPS_IT" type="hidden" /> 
		<input id="item_name" name="item_name" value="Main Website Store" type="hidden" /> 
		<input id="lc" name="lc" value="en_US" type="hidden" />
		<input id="charset" name="charset" value="utf-8" type="hidden" />
		
		<input	id="amount" name="amount" value="<?php echo $amount;?>" type="hidden" />
		<input id="tax" name="tax" value="<?php echo number_format($tax, 2, '.', '');?>" type="hidden" /> 
		<input id="shipping1" name="shipping1"	value="<?php echo sprintf("%.2f", $shipping);?>" type="hidden" /> 
		
		
         <input type="hidden" name="rm" value="2">
         <input type="hidden" name="cbt" value="Return to The Store">
         <input type="hidden" name="custom" value="custom_value">

		 
<?php //echo "<pre>";print_r($result);
foreach ($result as $key => $item) { ?>

            <input id="item_number_<?php echo $key + 1; ?>" name="item_number_<?php echo $key + 1; ?>" value="<?php echo $item->getSku(); ?>" type="hidden" /> 
			<input id="item_name_<?php echo $key + 1; ?>" name="item_name_<?php echo $key + 1; ?>" value="<?php echo $item->getName(); ?>" type="hidden" /> 
			<input 	id="quantity_<?php echo $key + 1; ?>" name="quantity_<?php echo $key + 1; ?>" value="<?php echo intval($item->getQtyOrdered()); ?>" type="hidden" />
            <input 	id="amount_<?php echo $key + 1; ?>" name="amount_<?php echo $key + 1; ?>" value="<?php echo number_format($item->getPrice(),2,'.',''); ?>" type="hidden" />
         
<?php } //exit;?>

         <input id="shipping_1" name="shipping_1"	value="<?php echo sprintf("%.2f", $shipping);?>" type="hidden" /> 
        
		<input id="cmd" name="cmd" value="_cart" type="hidden" />
	    <input	id="upload" name="upload" value="1" type="hidden" />

		<input id="tax_cart" name="tax_cart" value="<?php echo number_format($tax, 2, '.', '');?>" type="hidden"/>
	     <input id="discount_amount_cart" name="discount_amount_cart" value="<?php echo number_format($discountAmount, 2, '.', '');?>" type="hidden"/>

		<input id="city" name="city" value="<?php echo $city; ?>" type="hidden" />
		<input id="country" name="country" value="<?php echo $country; ?>" 	type="hidden" />
		<input id="email" name="email"  value="<?php echo $email; ?>" type="hidden" /> 
		<input id="first_name" name="first_name" value="<?php echo $firstname; ?>" type="hidden" />
		<input id="last_name" name="last_name" value="<?php echo $lastname; ?>" type="hidden" /> 
	    <input id="zip" name="zip" value="<?php echo $post_code; ?>" type="hidden" />
	    <input id="state" name="state" value="<?php echo $regioncode; ?>" type="hidden" />
	    <input id="address1" name="address1" value="<?php echo $streetvalue; ?>" 	type="hidden" />
	    <input id="address2" name="address2" value="" type="hidden" /> 
	    <input id="address_override" name="address_override" value="0" type="hidden" />

<?php //exit;?>
</form>
	<script type="text/javascript">document.getElementById("paypal_standard_checkout").submit();</script>
</body>
</html>