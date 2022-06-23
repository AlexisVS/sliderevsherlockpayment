<?php

namespace classes;

use Configuration;

class paymentRequest
{
    /** @var sealCalculation $sealCalculation */
    public sealCalculation $sealCalculation;

    public function __construct()
    {
        $this->sealCalculation = new sealCalculation();
    }

    /**
     * This function generates a payment request.
     *
     * @param $requestData
     * @return mixed
     */
    function generate_the_payment_request($requestData)
    {
        true == Configuration::get('SLIDEREVSHERLOCKPAYMENT_TEST_MODE')
            ? $secretKey = Configuration::get('SLIDEREVSHERLOCKPAYMENT_TEST_SECRET_KEY')
            : $secretKey = Configuration::get('SLIDEREVSHERLOCKPAYMENT_SECRET_KEY');
        $requestTable = $requestData;
        $requestTable['seal'] = $this->sealCalculation->compute_payment_init_seal("HMAC-SHA-256", $requestData, $secretKey);
        $requestTable['keyVersion'] = "1";
        $requestTable['sealAlgorithm'] = "HMAC-SHA-256";

        return $requestTable;
    }

    /**
     * This function initializes the payment and redirects the client to Sips server
     *
     * @param $requestTable
     * @param $urlForPaymentInitialisation
     * @return array
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
        }

        true == Configuration::get('SLIDEREVSHERLOCKPAYMENT_TEST_MODE')
            ? $secretKey = Configuration::get('SLIDEREVSHERLOCKPAYMENT_TEST_SECRET_KEY')
            : $secretKey = Configuration::get('SLIDEREVSHERLOCKPAYMENT_SECRET_KEY');

        $computedResponseSeal = $this->sealCalculation->compute_payment_init_seal("HMAC-SHA-256", $responseData, $secretKey);

        return [
            "responseTable" => $responseTable,
            "computedResponseSeal" => $computedResponseSeal
        ];
    }
}