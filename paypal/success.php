<?php
require '../app/Mage.php';

Mage::App (); // might be "default"
$_SERVER ['SCRIPT_NAME'] = str_replace ( basename ( __FILE__ ), 'index.php', $_SERVER ['SCRIPT_NAME'] );
$_SERVER ['SCRIPT_FILENAME'] = str_replace ( basename ( __FILE__ ), 'index.php', $_SERVER ['SCRIPT_FILENAME'] );


echo "transaction id= ".$txnid = $_REQUEST ['txn_id'];
mail("vinotha.a@contus.in",'paypal', $txnid);
$transaction_id = $_POST ['verify_sign'];
$order_id = $_POST ['invoice'];
$status = $_POST ['payment_status'];
$email = $_POST ['payer_email'];
$name = $_POST ['first_name'];
$pendingreason = $_POST ['pending_reason'];

$mcgross = $_POST ['mc_gross'];
$refundreason = $_POST ['reason_code'];

?>
<script language="javascript">
	function showAndroidToast(toast) {
		alert(toast);
        PaymentTransaction.showToast(toast);
    }
</script>
<html>
<head>
<meta content='True' name='HandheldFriendly' />
<meta
	content='width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;'
	name='viewport' />
</head>

<body onload="showAndroidToast('<?php echo $txnid; ?>')">
<input id="txnid" name="txnid" value="<?php echo $txnid;?>" type="hidden" />
	<center>
		<h2>Order Placed successfully.</h2>
	</center>

	<table>
		<tr>
			<td><b>Transcation Id :</b></td>
			<td><?php echo $txnid ;?></td>
			<td><b> Order Id :</b></td>
			<td><?php echo $order_id ;?></td>
		</tr>
	</table>
</body>
</html>