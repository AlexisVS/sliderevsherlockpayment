<?php

include('sealCalculationPaypageJson.php');

/*
 * This function generates a payment request.
 *
 * @param $requestData
*/
function generate_the_payment_request($requestData)
{
    true == Configuration::get('SLIDEREVSHERLOCKPAYMENT_TEST_MODE')
        ? $secretKey = Configuration::get('SLIDEREVSHERLOCKPAYMENT_TEST_SECRET_KEY')
        : $secretKey = Configuration::get('SLIDEREVSHERLOCKPAYMENT_SECRET_KEY');
    $requestTable = $requestData;
    $requestTable['seal'] = compute_payment_init_seal("HMAC-SHA-256", $requestData, $secretKey);
    $requestTable['keyVersion'] = "1";
    $requestTable['sealAlgorithm'] = "HMAC-SHA-256";

    return $requestTable;
}

/*
 * This function initializes the payment and redirects the client to Sips server
 *
 * @param $requestTale
 *
 * @param $urlForPaymentInitialisation
 *
 */
function send_payment_request($requestTable, $urlForPaymentInitialisation): array
{
    $requestJson = json_encode($requestTable, JSON_UNESCAPED_UNICODE, '512');

    //SENDING OF THE PAYMENT REQUEST

    $option = array(
        'http' => array(
            'method' => 'POST',
            'header' => "content-type: application/json",
            'content' => $requestJson
        ),
    );
    $context = stream_context_create($option);
    $responseJson = file_get_contents($urlForPaymentInitialisation, false, $context);
    $responseTable = json_decode($responseJson, true);

    //RECALCULATION OF SEAL
    $responseData = [];
    foreach ($responseTable as $key => $value) {
        if (strcasecmp($key, "seal") != 0) {
            $responseData[$key] = $value;
        }
        //store responseTable in session to access responseData from other pages
//        $_SESSION[$key] = $value;
    }

    true == Configuration::get('SLIDEREVSHERLOCKPAYMENT_TEST_MODE')
        ? $secretKey = Configuration::get('SLIDEREVSHERLOCKPAYMENT_TEST_SECRET_KEY')
        : $secretKey = Configuration::get('SLIDEREVSHERLOCKPAYMENT_SECRET_KEY');


//   return $computedResponseSeal;
    $computedResponseSeal = compute_payment_init_seal("HMAC-SHA-256", $responseData, $secretKey);

    return [
        "responseTable" => $responseTable,
        "computedResponseSeal" => $computedResponseSeal
    ];


//    //REDIRECTION TO SIPS PAYPAGE JSON
//    if (strcmp($computedResponseSeal, $responseTable['seal']) == 0) {
//        if ($responseTable['redirectionStatusCode'] == 00) {
////            var_dump($responseTable);
////            echo $responseTable;
////            var_dump($responseTable);
////            Tools::redirect($urlForRedirectionForm);
//        } else {
//            print_r($responseTable);
//            var_dump($responseTable);
//            var_dump($responseTable);
//            print_r($responseTable);
////            Tools::redirect($urlForRedirectionError);
////            header('Location: Common/requestError.php');
//
//        }
//        exit();
//    } else {
//        echo "Your payment could not be processed";
//        echo "<br>" . "Please contact Worldline Technical Support";
//    }
}

