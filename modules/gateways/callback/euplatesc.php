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

if (!$GATEWAY["type"]) {
    die("Module Not Activated");
} # Checks gateway module is active before accepting callback

$ipsarray = explode(",", $GATEWAY['ipnips']);


if (!in_array($_SERVER['REMOTE_ADDR'], $ipsarray)) {
    die("This IP isn't allowed to make callbacks!");
}

function euplatesc_mac($data, $key)
{
    $str = null;
    foreach ($data as $d) {
        if ($d === null || strlen($d) == 0) {
            $str .= '-';
        } else {
            $str .= strlen($d).$d;
        }
    }
    return hash_hmac('MD5', $str, pack('H*', $key));
}

$key = $GATEWAY["secretkey"];

$zcrsp = array(
    'amount' => addslashes(trim(@$_POST['amount'])),  //original amount
    'curr' => addslashes(trim(@$_POST['curr'])),    //original currency
    'invoice_id' => addslashes(trim(@$_POST['invoice_id'])),//original invoice id
    'ep_id' => addslashes(trim(@$_POST['ep_id'])), //Euplatesc.ro unique id
    'merch_id' => addslashes(trim(@$_POST['merch_id'])), //your merchant id
    'action' => addslashes(trim(@$_POST['action'])), // if action ==0 transaction ok
    'message' => addslashes(trim(@$_POST['message'])),// transaction responce message
    'approval' => addslashes(trim(@$_POST['approval'])),// if action!=0 empty
    'timestamp' => addslashes(trim(@$_POST['timestamp'])),// meesage timestamp
    'nonce' => addslashes(trim(@$_POST['nonce'])),
    'sec_status' => addslashes(trim(@$_POST['sec_status'])),
    // if sec_status ==8 or 9 delivery ok (format numeric si lungime de 1)
);

$invoiceid = checkCbInvoiceID($zcrsp['invoice_id'],
    $GATEWAY["name"]); # Checks invoice ID is a valid invoice number or ends processing
checkCbTransID($zcrsp['ep_id']); # Checks transaction number isn't already in the database and ends processing if it does
$zcrsp['fp_hash'] = strtoupper(euplatesc_mac($zcrsp, $key));

$fp_hash = addslashes(trim(@$_POST['fp_hash']));
if ($zcrsp['fp_hash'] === $fp_hash) {

    if (isset($_POST['message']) and str_contains(strtolower($_POST['message']),
            "pending")) { /*to filter sms pending message*/
        exit('no pending');
    }
    // start facem update in baza de date

    if ($zcrsp['action'] == "0") {

        closeConnection("OK", 200);

        if ($zcrsp['sec_status'] == "8" || $zcrsp['sec_status'] == "9") {
            addInvoicePayment($zcrsp['invoice_id'], $zcrsp['ep_id'], $zcrsp['amount'], '0',
                $gatewaymodule); # Apply Payment to Invoice: invoiceid, transactionid, amount paid, fees, modulename
            logTransaction($GATEWAY["name"], $_POST, "Successful"); # Save to Gateway Log: name, data array, status
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

            $cartid = $zcrsp['ep_id'];
            $amountx = $zcrsp['amount'];
            $docheck = Capsule::table('euplatesc')->select('id')->where('invoiceid', $invoiceid)->get();
            if (!$docheck) {
                $real = Capsule::table('euplatesc') - insert([
                        'invoiceid' => $invoiceid,
                        'cart_id' => $cartid,
                        'amount' => $amountx,
                        'status' => 'invalidated'
                    ]);
                if (!$real) {
                    logTransaction($GATEWAY["name"], $_POST."\n".$zcrsp['cart_id'].": ".$statx,
                        "---- UNABLE TO INSERT TRANSID IN MYSQL ----");
                }
            }
            if (!$invoiceid) {
                $invoiceid = Capsule::table('euplatesc')->where('cartid', $zcrsp['ep_id'])->value('invoiceid');
            }
            logTransaction($GATEWAY["name"], $_POST."\n".$zcrsp['cart_id'].": ".$statx, "Unsuccessful");
        }
    } else {
        logTransaction($GATEWAY["name"], $_POST, "Unsuccessful"); # Save to Gateway Log: name, data array, status
    }
    // end facem update in baza de date
} else {
    echo "Invalid signature";
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