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

function euplatesc_config()
{
    $configarray = array(
        "FriendlyName" => array(
            "Type" => "System",
            "Value" => "EuPlatesc.ro - WHMCS Module"
        ),
        "username" => array("FriendlyName" => "Merchant ID", "Type" => "text", "Size" => "30",),
        "secretkey" => array("FriendlyName" => "Merchant KEY", "Type" => "text", "Size" => "30",),
        "ipnips" => array(
            "FriendlyName" => "EuPlatesc IPs (comma separated)<br/><u>NO SPACES!</b>", "Type" => "text",
            "Value" => "128.140.229.226,128.140.229.227,128.140.229.228,128.140.229.229,128.140.229.230,128.140.229.231,128.140.229.232,128.140.229.233,128.140.229.234,128.140.229.235,128.140.229.236,128.140.229.237,128.140.229.238,128.140.229.239,128.140.229.240,128.140.229.241,128.140.229.242,128.140.229.243,128.140.229.244,128.140.229.245,128.140.229.246,128.140.229.247,128.140.229.248,128.140.229.249,128.140.229.250,128.140.229.251,128.140.229.252,128.140.231.226,128.140.231.227,128.140.231.228,128.140.231.229,128.140.231.230,128.140.231.231,128.140.231.232,128.140.231.233,128.140.231.234,128.140.231.235,128.140.231.236",
            "Size" => "30",
        ),
        //   "testmode"		=> array(	"FriendlyName"	=> "Use sandbox", "Type"			=> "yesno",	"Description"	=> "tick this to test payments on the sandbox server"), // intrucat nu exista un URL separat pentru testmode, nu are rost sa-l includem degeaba.
    );
    return $configarray;
}

/**
 * the main function
 * this will handle the payment
 **/
function euplatesc_link($params)
{


    #	gateway specific variables
    # ------------------------------------------------------------- #
    #$gatewaySignature	= $params['signature'];
    #$gatewayTestMode	= $params['testmode'];
    $mid = $params['username'];
    $mkey = $params['secretkey'];
    # ------------------------------------------------------------- #


    #	invoice variables
    # ------------------------------------------------------------- #
    $invoiceId = $params['invoiceid'];
    $description = $params["description"];
    $amount = $params['amount'];                // Format: ##.##
    $currency = $params['currency'];                // Currency Code
    $baseCurrencyAmount = $params['basecurrencyamount'];    // Format: ##.##
    $baseCurrency = $params['basecurrency'];            // Currency Code
    # ------------------------------------------------------------- #


    #   customer variables
    # pay attention to custom fields below
    # ------------------------------------------------------------- #
    $firstName = $params['clientdetails']['firstname'];
    $lastName = $params['clientdetails']['lastname'];
    $country = $params['clientdetails']['country'];
    $county = $params['clientdetails']['state'];
    $city = $params['clientdetails']['city'];
    $zipCode = $params['clientdetails']['postcode'];
    $address1 = $params['clientdetails']['address1'];
    $email = $params['clientdetails']['email'];
    $mobilePhone = $params['clientdetails']['phonenumber'];
    # this is the place where you shoud match your WHMCS instance
    $isCompany = $params['clientdetails']['customfields1']; // setati ID-ul customfield-ului aferent; trebuie sa fie de tip dropdown yes/no.

    # ------------------------------------------------------------- #


    #	system variables
    # ------------------------------------------------------------- #
    $companyName = $params['companyname'];
    $systemUrl = $params['systemurl'];
    $confirmUrl = $params['systemurl'].'/modules/gateways/callback/euplatesc.php';
    $returnUrl = $params['returnurl'];
    $currency = $params['currency'];
    /* ----------------------------------------------------------- */

    $paymentUrl = 'https://secure.euplatesc.ro/tdsprocess/tranzactd.php';

    /* ----------------------------------------------------------- */


    try {

        if (!function_exists("euplatesc_mac")) {
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
        }

        $dataAll = array(
            'amount' => $amount,                                                   //suma de plata
            'curr' => $currency,                                                   // moneda de plata
            'invoice_id' => $invoiceId,  // numarul comenzii este generat aleator. inlocuiti cuu seria dumneavoastra
            'order_desc' => trim($companyName.' - '.$invoiceId),//descrierea comenzii
            'merch_id' => $mid,
            'timestamp' => gmdate("YmdHis"),
            'nonce' => md5(microtime().mt_rand()),
        );

        $dataAll['fp_hash'] = strtoupper(euplatesc_mac($dataAll, $mkey));

        if (!empty($address2)) {
            $finaladdress = $address1.", ".$address2;
        } else {
            $finaladdress = $address1;
        }

        if ($isCompany != 'yes') {
            $companynamem = '';
        } else {
            $companynamem = $companyName;
        }
        //completati cu valorile dvs
        $dataBill = array(
            'fname' => $firstName,      // nume
            'lname' => $lastName,   // prenume
            'country' => $country,      // tara
            'company' => $companynamem,   // firma
            'city' => $city,      // oras
            'add' => $finaladdress,    // adresa
            'email' => $email,     // email
            'phone' => $mobilePhone,   // telefon
        );
    } catch (Exception $e) {
    }

    if (!($e instanceof Exception)) {

        $code = "
<form ACTION='https://secure.euplatesc.ro/tdsprocess/tranzactd.php' METHOD='POST' name='gateway' target='_blank'>
<input name=\"lang\" type=\"hidden\" value=\"en\" />
<!-- begin billing details -->
    <input name='fname' type='hidden' value='{$dataBill['fname']}' />
    <input name='lname' type='hidden' value='{$dataBill['lname']}' />
    <input name='country' type='hidden' value='{$dataBill['country']}' />
    <input name='company' type='hidden' value='{$dataBill['company']}' />
    <input name='city' type='hidden' value='{$dataBill['city']}' />
    <input name='add' type='hidden' value='{$dataBill['add']}' />
    <input name='email' type='hidden' value='{$dataBill['email']}' />
    <input name='phone' type='hidden' value='{$dataBill['phone']}' /> 
<!-- snd billing details -->

<input type='hidden' NAME='amount' VALUE='{$dataAll['amount']}' SIZE='12' MAXLENGTH='12' />
<input TYPE='hidden' NAME='curr' VALUE='{$dataAll['curr']}' SIZE='5' MAXLENGTH='3' />
<input type='hidden' NAME='invoice_id' VALUE='{$dataAll['invoice_id']}' SIZE='32' MAXLENGTH='32' />
<input type='hidden' NAME='order_desc' VALUE='{$dataAll['order_desc']}' SIZE='32' MAXLENGTH='50' />
<input TYPE='hidden' NAME='merch_id' SIZE='15' VALUE='{$dataAll['merch_id']}' />
<input TYPE='hidden' NAME='timestamp' SIZE='15' VALUE='{$dataAll['timestamp']}' />
<input TYPE='hidden' NAME='nonce' SIZE='35' VALUE='{$dataAll['nonce']}' />
<input TYPE='hidden' NAME='fp_hash' SIZE='40' VALUE='{$dataAll['fp_hash']}' />
	<input type=\"submit\"  class=\"btn btn-default btn-small btn-danger\" style=\"width:100%;margin-top:1.5%;font-weight:bold;\"  value=\"Pay Now\" />
</form>".
            "</form>";
        return $code;

    } else {
        return $e->getMessage();
    }
}

?>