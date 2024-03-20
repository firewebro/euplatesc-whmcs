<?php
###############################################
##        EuPlatesc.ro - WHMCS Module        ##
##				  Ver. 2.4.15                ##
##	   Site URL: https://www.euplatesc.ro 	 ##
###############################################
# PHP 8.1 Compatibility Update by FireWeb
# March 2024
# Site URL: https://fireweb.ro
###############################################

// WHMCS Capsule
use Illuminate\Database\Capsule\Manager as Capsule;


# Required File Includes
include("../../../init.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");


$gatewaymodule = "euplatesc"; # Enter your gateway module name here replacing template

$GATEWAY = getGatewayVariables($gatewaymodule);
$ipsarray = explode(",", $GATEWAY['ipnips']);


if (!in_array($_SERVER['REMOTE_ADDR'], $ipsarray)) {
    die("This IP isn't allowed to make callbacks!");
}


$key = $GATEWAY["secretkey"];


$zcrsp = array(
    'cart_id' => addslashes(trim(@$_POST['cart_id'])), //Euplatesc.ro unique id
    'mid' => addslashes(trim(@$_POST['mid'])), //your merchant id
    'timestamp' => addslashes(trim(@$_POST['timestamp'])),// meesage timestamp
    'sec_status' => addslashes(trim(@$_POST['sec_status'])),
    // if sec_status ==8 or 9 delivery ok (format numeric si lungime de 1)
);

$cartid = $zcrsp['cart_id'];
$relx = Capsule::table('euplatesc')->where('cart_id', $cartid)->where('status', 'invalidated')->first();
$qry = (array) $relx;

if (!empty($cartid)) {
    echo "OK";
}

$invoiceid = $qry['invoiceid'];
logTransaction($GATEWAY["name"], $qry, "MySQL QRY"); # Save to Gateway Log: name, data array, status
if ($zcrsp['sec_status'] == "8" || $zcrsp['sec_status'] == "9") {
    closeConnection("OK", 200);
    addInvoicePayment($invoiceid, $qry['cart_id'], $qry['amount'], '0',
        $gatewaymodule); # Apply Payment to Invoice: invoiceid, transactionid, amount paid, fees, modulename
    logTransaction($GATEWAY["name"], $_POST, "Successful"); # Save to Gateway Log: name, data array, status
    Capsule::table('euplatesc')->where('cart_id', $cartid)->where('invoiceid', $invoiceid)->update([
        'status' => 'validated'
    ]);
} else {
    switch ($zcrsp['sec_status']) {
        case 1:
            $statx = "Transaction valid, but not finished";
            break;
        case 2:
            $statx = "Transaction Failed";
            break;
        case 3:
            $statx = "Transaction is being manually verified";
            break;
        case 4:
            $statx = "Suspicious transaction: Awaiting client response";
            break;
        case 5:
            $statx = "Fraud";
            break;
        case 6:
            $statx = "Transaction is not okay. Shipping must be canceled.";
            break;
        case 7:
            $statx = "Insecure transaction.";
            break;
    }

    logTransaction($GATEWAY["name"], $_POST."\n".$zcrsp['cart_id'].": ".$statx, "Unsuccessful");
}

function closeConnection($body, $responseCode)
{
    set_time_limit(0);
    ignore_user_abort(true);
    @ob_end_clean();
    ob_start();
    echo $body;
    $size = ob_get_length();
    header("Connection: close\r\n");
    header("Content-Encoding: none\r\n");
    header("Content-Length: $size");
    http_response_code($responseCode);
    ob_end_flush();
    @ob_flush();
    flush();
}

?>