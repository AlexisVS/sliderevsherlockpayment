<?php

/**
 * <ModuleClassName> => sliderevsherlockpayment
 * <FileName> => validation.php
 * Format expected: SliderevsherlockpaymentValidationModuleFrontController
 */

use classes\paymentRequest;
use PrestaShop\Module\sliderevsherlockpayment\Exception\sliderevsherlockpaymentException;

class SliderevsherlockpaymentValidationModuleFrontController extends ModuleFrontController
{
    public function __construct($abaeille)
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
        if (!Validate::isLoadedObject($cart)) {
            throw new sliderevsherlockpaymentException('No cart found', sliderevsherlockpaymentException::PRESTASHOP_CART_NOT_FOUND);
        }

        /** @var sliderevsherlockpayment $module */
        $module = Module::getInstanceByName("sliderevsherlockpayment");
        if (!Validate::isLoadedObject($module)) {
            throw new sliderevsherlockpaymentException('No module found', sliderevsherlockpaymentException::PRESTASHOP_MODULE_NOT_FOUND);
        }

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
        $amount_paid = $cart->getOrderTotal();
        $module->validateOrder(
            $cart->id,
            intval(Configuration::get('SLIDEREVSHERLOCKPAYMENT_ORDER_STATE_PENDING_ID')),
            $amount_paid,
            $module->name,
            'sherlock\'s payement valider',
            [],
            null,
            false,
            $customer->secure_key,
        );

        $this->process_payment();
    }

    /**
     * Process the payment on the Sherlock's services
     *
     * @return void
     * @throws PrestaShopException
     * @throws Exception
     */
    public function process_payment(): void
    {
        $paymentValidationResponse = $this->process_payment_request();
        $computedResponseSeal = $paymentValidationResponse['computedResponseSeal'];
        $responseTable = $paymentValidationResponse['responseTable'];

        if (strcmp($computedResponseSeal, $responseTable['seal']) == 0) {
            if ($responseTable['redirectionStatusCode'] == '00') {
                $this->context->smarty->assign([
                    'redirectionUrl' => $responseTable['redirectionUrl'],
                    'redirectionVersion' => $responseTable['redirectionVersion'],
                    'redirectionData' => $responseTable['redirectionData']
                ]);
                $this->setTemplate('module:sliderevsherlockpayment/views/templates/front/redirection_form.tpl');
            } else {
                $this->process_response_code_payment($responseTable['redirectionStatusCode']);
            }
            return;
        }
        $this->context->smarty->assign([
            'error' => $this->module->l('An error occurred during the payment process')
        ]);
        $this->setTemplate('module:sliderevsherlockpayment/views/templates/front/payment_error.tpl');
    }

    /**
     * Process payment request
     *
     * @return array
     * @throws Exception
     */
    private function process_payment_request(): array
    {
        /** @var $paymentRequest $paymentRequest */
        $paymentRequest = new paymentRequest();

        $requestData = $this->make_request_payment_request();

        $requestTable = $paymentRequest->generate_the_payment_request($requestData);
        true == Configuration::get('SLIDEREVSHERLOCKPAYMENT_TEST_MODE')
            ? $urlForPaymentInitialisation = Configuration::get('SLIDEREVSHERLOCKPAYMENT_POST_REQUEST_DEV_MODE')
            : $urlForPaymentInitialisation = Configuration::get('SLIDEREVSHERLOCKPAYMENT_POST_REQUEST_PROD_MODE');

        return $paymentRequest->send_payment_request($requestTable, $urlForPaymentInitialisation);
    }

    /**
     * Generate the request for sending payment request
     *
     * @return array
     * @throws Exception
     */
    private function make_request_payment_request(): array
    {
        $cart = $this->context->cart;
        if (!Validate::isLoadedObject($cart)) {
            throw new sliderevsherlockpaymentException('No cart found', sliderevsherlockpaymentException::PRESTASHOP_CART_NOT_FOUND);
        }

        /** @var sliderevsherlockpayment $module */
        $module = Module::getInstanceByName('sliderevsherlockpayment');
        if (!Validate::isLoadedObject($module)) {
            throw new sliderevsherlockpaymentException('No module found', sliderevsherlockpaymentException::PRESTASHOP_MODULE_NOT_FOUND);
        }

        $amount = number_format(((float)$cart->getOrderTotal()), 2, '', '.');
        $currencyCode = $this->context->currency->iso_code_num;

        $params = [
            'id_cart' => $cart->id,
        ];
        $normalReturn = $this->context->link->getModuleLink($module->name, 'paymentResponse', $params);

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

    /**
     * Process payment error
     *
     * @param string $redirectionStatusCode
     * @return void
     * @throws sliderevsherlockpaymentException
     */
    private function process_response_code_payment(string $redirectionStatusCode): void
    {
        switch ($redirectionStatusCode) {
            case '03':
                throw new sliderevsherlockpaymentException('The merchant ID or the acquirer contract is not valid.', sliderevsherlockpaymentException::SLIDEREVSHERLOCKPAYMENT_MERCHANTID_OR_ACQUERER_CONSTRACT_NOT_VALID);
            case '12':
                throw new sliderevsherlockpaymentException('The transaction parameters are invalid.', sliderevsherlockpaymentException::SLIDEREVSHERLOCKPAYMENT_TRANSACTION_PARAMETERS_SEND_INVALID);
            case '30':
                throw new sliderevsherlockpaymentException('The request format is invalid.', sliderevsherlockpaymentException::SLIDEREVSHERLOCKPAYMENT_REQUEST_FORMAT_INVALID);
            case '34':
                throw new sliderevsherlockpaymentException('Sherlock payment security issue.', sliderevsherlockpaymentException::SLIDEREVSHERLOCKPAYMENT_SECURITY_ISSUES);
            case '94':
                throw new sliderevsherlockpaymentException('The transaction already exist.', sliderevsherlockpaymentException::SLIDEREVSHERLOCKPAYMENT_TRANSACTION_ALREADY_EXIST);
            case '99':
                throw new sliderevsherlockpaymentException('Service temporarily unavailable.', sliderevsherlockpaymentException::SLIDEREVSHERLOCKPAYMENT_SERVICE_TEMPORARILY_UNAVAILABLE);
        }
    }
}