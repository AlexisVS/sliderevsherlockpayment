<?php

/**
 * <ModuleClassName> => sliderevsherlockpayment
 * <FileName> => validation.php
 * Format expected: SliderevsherlockpaymentValidationModuleFrontController
 */

use classes\paymentRequest;

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

        /** @var sliderevsherlockpayment $module */
        $module = Module::getInstanceByName("sliderevsherlockpayment");


        // redirect if missed information
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module_item) {
            if ($module_item['name'] == 'sliderevsherlockpayment') {
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


        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        //Validate Order
        $amount_paid = number_format($cart->getOrderTotal(), 2, '', '.');
        $module->validateOrder(
            $cart->id,
            Configuration::get('SLIDEREVSHERLOCKPAYMENT_ORDER_STATE_ID'),
            $amount_paid,
            $this->module->name,
            'sherlock\'s payement valider',
            [],
            null,
            false,
            $customer->secure_key,
        );

        $this->process_payment($customer);
    }

    /**
     * Process the payment on the Sherlock's services
     *
     * @param Customer $customer
     * @return void
     * @throws PrestaShopException
     * @throws Exception
     */
    public function process_payment(Customer $customer): void
    {
        $paymentValidationResponse = $this->process_payment_request($customer);

        $computedResponseSeal = $paymentValidationResponse['computedResponseSeal'];
        $responseTable = $paymentValidationResponse['responseTable'];

        if (strcmp($computedResponseSeal, $responseTable['seal']) == 0) {
            if ($responseTable['redirectionStatusCode'] == 00) {
                $this->context->smarty->assign([
                    'redirectionUrl' => $responseTable['redirectionUrl'],
                    'redirectionVersion' => $responseTable['redirectionVersion'],
                    'redirectionData' => $responseTable['redirectionData']
                ]);
                $this->setTemplate('module:sliderevsherlockpayment/views/templates/front/redirection_form.tpl');
            } else {
                $this->setTemplate('module:sliderevsherlockpayment/views/templates/front/payment_error.tpl');
            }
        }
    }

    /**
     * Process payment request
     * @param $customer
     * @return array
     * @throws Exception
     */
    private function process_payment_request($customer): array
    {
        /** @var $paymentRequest $paymentRequest */
        $paymentRequest = new paymentRequest();

        $requestData = $this->make_request_payment_request($customer);

        $requestTable = $paymentRequest->generate_the_payment_request($requestData);
        true == Configuration::get('SLIDEREVSHERLOCKPAYMENT_TEST_MODE')
            ? $urlForPaymentInitialisation = Configuration::get('SLIDEREVSHERLOCKPAYMENT_POST_REQUEST_DEV_MODE')
            : $urlForPaymentInitialisation = Configuration::get('SLIDEREVSHERLOCKPAYMENT_POST_REQUEST_PROD_MODE');

        return $paymentRequest->send_payment_request($requestTable, $urlForPaymentInitialisation);
    }

    /**
     * Generate the request for sending payment request
     *
     * @param $customer
     * @return array
     * @throws Exception
     */
    private function make_request_payment_request($customer): array
    {
        $cart = $this->context->cart;

        /** @var sliderevsherlockpayment $module */
        $module = Module::getInstanceByName('sliderevsherlockpayment');


        $amount = number_format(((float)$cart->getOrderTotal()), 2, '', '.');
        $currencyCode = $this->context->currency->iso_code_num;
//        $normalReturn = 'http://slide-prestashop.test/index.php?controller=order-confirmation&id_cart=' . $cart->id
//            . '&id_module=' . $this->module->id . '&id_order=' . $module->currentOrder . '&key=' . $customer->secure_key;
        $normalReturn = $this->context->link->getModuleLink($module->name, 'paymentResponse');
        true == Configuration::get('SLIDEREVSHERLOCKPAYMENT_TEST_MODE')
            ? $merchantId = Configuration::get('SLIDEREVSHERLOCKPAYMENT_TEST_MERCHANT_ID')
            : $merchantId = Configuration::get('SLIDEREVSHERLOCKPAYMENT_MERCHANT_ID');

        true == Configuration::get('SLIDEREVSHERLOCKPAYMENT_TEST_MODE')
            ? $referenceOrder = 'SLIDEREV' . $module->currentOrderReference
            : $referenceOrder = $module->currentOrderReference;

        // ! Les champs de la request doivent être ranger par ordre alphabétique mise à part les captures
        return [
            "amount" => $amount,
            "currencyCode" => $currencyCode,
            "interfaceVersion" => "IR_WS_2.42",
            "merchantId" => $merchantId,
            "normalReturnUrl" => $normalReturn,
            "orderChannel" => "INTERNET",
            "transactionReference" => $referenceOrder,
            "captureDay" => "0",
            "captureMode" => "AUTHOR_CAPTURE",
        ];
    }
}