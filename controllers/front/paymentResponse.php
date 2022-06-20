<?php

/**
 * <ModuleClassName> => sliderevsherlockpayment
 * <FileName> => paymentResponse.php
 * Format expected: SliderevsherlockpaymentPaymentResponseModuleFrontController
 */

use classes\sealCalculation;

class SliderevsherlockpaymentPaymentResponseModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        if (isset($_POST['Data'])) {
            $data = $_POST['Data'];
        }
        if (isset($_POST['Encode'])) {
            $encode = $_POST['Encode'];
        }
        if (isset($_POST['Seal'])) {
            $seal = $_POST['Seal'];
        }

        true == Configuration::get('SLIDEREVSHERLOCKPAYMENT_TEST_MODE')
            ? $secretKey = Configuration::get('SLIDEREVSHERLOCKPAYMENT_TEST_SECRET_KEY')
            : $secretKey = Configuration::get('SLIDEREVSHERLOCKPAYMENT_SECRET_KEY');

        $sealCalculation = new sealCalculation();
        $computedResponseSeal = $sealCalculation->compute_payment_response_seal('HMAC-SHA-256', $data, $secretKey);

        if (strcmp($computedResponseSeal, $seal) == 0) {
            if (strcmp($encode, "base64") == 0) {
                $dataDecode = base64_decode($data);
                $responseData = $this->extract_data_from_the_payment_response($dataDecode);
            } else {
                $responseData = $this->extract_data_from_the_payment_response($data);
            }
            var_dump($responseData);
        } else {
            // TODO: Mettre la page du theme order-confirmation
        }
    }

    /**
     * Extract data from the payment response
     *
     * @param $data
     * @return array
     */
    private function extract_data_from_the_payment_response($data): array
    {
        $singleDimArray = explode("|", $data);

        foreach ($singleDimArray as $value) {
            $fieldTable = explode("=", $value);
            $key = $fieldTable[0];
            $value = $fieldTable[1];
            $responseData[$key] = $value;
            unset($fieldTable);
        }
        return $responseData;
    }
}