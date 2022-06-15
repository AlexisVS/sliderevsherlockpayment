<?php

/**
 * <ModuleClassName> => sliderevsherlockpayment
 * <FileName> => validation.php
 * Format expected: SliderevsherlockpaymentValidationModuleFrontController
 */
include(dirname(__FILE__) . '/../../sips-paypage-json-php/Common/paymentRequest.php');

class SliderevsherlockpaymentValidationModuleFrontController extends ModuleFrontController
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws PrestaShopException
     * @throws Exception
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {

        $cart = $this->context->cart;
        // DUMP cart : https://vardumpformatter.io/en/shared/8c96a934c711b3274082eb97dce5b103
        /** @var sliderevsherlockpayment $module */
        $module = Module::getInstanceByName("sliderevsherlockpayment");


        // redirect if missed information
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'sliderevsherlockpayment') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'validation'));
        }

        $this->context->smarty->assign([
            'params' => $_REQUEST,
        ]);


        // Définir la request
        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $amount = number_format(((float)$cart->getOrderTotal(true, Cart::BOTH)), 2, '', '.');
        $currencyCode = $this->context->currency->iso_code_num;
        $normalReturn = 'http://slide-prestashop.test/module/sliderevsherlockpayment/validation';

        true == Configuration::get('SLIDEREVSHERLOCKPAYMENT_TEST_MODE')
            ? $merchantId = Configuration::get('SLIDEREVSHERLOCKPAYMENT_TEST_MERCHANT_ID')
            : $merchantId = Configuration::get('SLIDEREVSHERLOCKPAYMENT_MERCHANT_ID');

        // ! Les champs de la request doivent être ranger par ordre alphabétique
        $requestData = [
            "amount" => $amount,
            "captureDay" => "0",
            "captureMode" => "AUTHOR_CAPTURE",
            "currencyCode" => $currencyCode,
            "interfaceVersion" => "IR_WS_2.42",
            "merchantId" => $merchantId,
            "normalReturnUrl" => $normalReturn,
            "orderChannel" => "INTERNET",
            "transactionReference" => "61sdfdsfsd485776541",
        ];

        $requestTable = generate_the_payment_request($requestData);
//        $this->context->smarty->assign("sherlock_request_table", $requestTable);
//        $this->setTemplate('module:sliderevsherlockpayment/views/templates/front/payment_return.tpl');
        true == Configuration::get('SLIDEREVSHERLOCKPAYMENT_TEST_MODE')
            ? $urlForPaymentInitialisation = Configuration::get('SLIDEREVSHERLOCKPAYMENT_POST_REQUEST_DEV_MODE')
            : $urlForPaymentInitialisation = Configuration::get('SLIDEREVSHERLOCKPAYMENT_POST_REQUEST_PROD_MODE');

        $paymentValidationResponse = send_payment_request($requestTable, $urlForPaymentInitialisation);

        $computedResponseSeal = $paymentValidationResponse['computedResponseSeal'];
        $responseTable = $paymentValidationResponse['responseTable'];
        var_dump($paymentValidationResponse);
        if (strcmp($computedResponseSeal, $responseTable['seal']) == 0) {
            if ($responseTable['redirectionStatusCode'] == 00) {
//                $this->setTemplate('module:sliderevsherlockpayment/views/templates/front/payment_return.tpl');
                $module->validateOrder(
                    $cart->id,
//                    TODO: comment avoir directement le correct status id et comment le créer a l'installation'
                    Configuration::get('SLIDEREVSHERLOCKPAYMENT_ORDER_STATUS_ID'),
                    $cart->getCartTotalPrice(),
                    $module->name,
                    'sherlockkkk payement valider',
                    [],
                    null,
                    false,
                    $customer->secure_key
                );
                var_dump([
                    "responseTable" => $responseTable,
                    "computedResponseSeal" => $computedResponseSeal
                ]);

            } else {
                $this->setTemplate('module:sliderevsherlockpayment/views/templates/front/payment_error.tpl');
            }
        }

        // J'ecrit n'importe sssssssquoi et ce PUTAIN DE MssdsdERDE DE VCS NE COMPREND PAS QU4IL Y A UN PT1 DE CHANGEMENT DANS MON FICHIER


        // $mailVars = array(
        //     '{bankwire_owner}' => Configuration::get('BANK_WIRE_OWNER'),
        //     '{bankwire_details}' => nl2br(Configuration::get('BANK_WIRE_DETAILS')),
        //     '{bankwire_address}' => nl2br(Configuration::get('BANK_WIRE_ADDRESS'))
        // );

//         $this->module->validateOrder($cart->id, Configuration::get('PS_OS_BANKWIRE'), $total, $this->module->displayName, NULL, $mailVars, (int)$currency->id, false, $customer->secure_key);
        // Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
    }

}