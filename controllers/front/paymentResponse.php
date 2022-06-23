<?php

/**
 * <ModuleClassName> => sliderevsherlockpayment
 * <FileName> => paymentResponse.php
 * Format expected: SliderevsherlockpaymentPaymentResponseModuleFrontController
 */

use classes\paymentResponse;
use classes\sealCalculation;
use PrestaShop\Module\sliderevsherlockpayment\Exception\sliderevsherlockpaymentException;

class SliderevsherlockpaymentPaymentResponseModuleFrontController extends ModuleFrontController
{


    public function __construct()
    {
        parent::__construct();
    }


    /**
     * @throws Exception
     * @throws sliderevsherlockpaymentException
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

        if (false === isset($data, $encode, $seal)) {
            throw new sliderevsherlockpaymentException('No data found', sliderevsherlockpaymentException::SLIDEREVSHERLOCKPAYMENT_PAYMENT_RESPONSE_NOT_FOUND);
        }

        true == Configuration::get('SLIDEREVSHERLOCKPAYMENT_TEST_MODE')
            ? $secretKey = Configuration::get('SLIDEREVSHERLOCKPAYMENT_TEST_SECRET_KEY')
            : $secretKey = Configuration::get('SLIDEREVSHERLOCKPAYMENT_SECRET_KEY');

        $sealCalculation = new sealCalculation();
        $computedResponseSeal = $sealCalculation->compute_payment_response_seal('HMAC-SHA-256', $data, $secretKey);

        if (strcmp($computedResponseSeal, $seal) == 0) {
            if (strcmp($encode, "base64") == 0) {
                $dataDecode = base64_decode($data);
                $responseData = paymentResponse::extract_data_from_the_payment_response($dataDecode);
            } else {
                $responseData = paymentResponse::extract_data_from_the_payment_response($data);
            }
            $this->process_response_code_payment($responseData['responseCode']);
        } else {
            $this->set_order_status_to_error();
            $this->context->smarty->assign('error', $this->module->l('Payment error. The order has been canceled.'));
            $this->setTemplate('module:sliderevsherlockpayment/views/templates/front/payment_error.tpl');
        }
    }

    /**
     * Process payment error
     *
     * @param string $responseCode
     * @return void
     * @throws sliderevsherlockpaymentException
     */
    private function process_response_code_payment(string $responseCode): void
    {
        if ($responseCode !== '00') {
            $this->set_order_status_to_error();
        }

        switch ($responseCode) {
            case '00':
                $this->process_content();
                break;
            case '05':
                throw new sliderevsherlockpaymentException("Payment was declined by Sherlock's payment fraud engine.", sliderevsherlockpaymentException::SLIDEREVSHERLOCKPAYMENT_PAYMENT_DECLINED_BY_SHERLOCK);
            case '34':
                throw new sliderevsherlockpaymentException('Authorization denied due to fraud.', sliderevsherlockpaymentException::SLIDEREVSHERLOCKPAYMENT_PAYMENT_DENIED_DUE_TO_FRAUD);
            case '75':
                throw new sliderevsherlockpaymentException('The buyer made several attempts, all of which failed because the information entered was not correct.', sliderevsherlockpaymentException::SLIDEREVSHERLOCKPAYMENT_PAYMENT_SEVERAL_ATTEMPT_FAILED);
            case '90':
            case '99':
                throw new sliderevsherlockpaymentException('Temporary technical problem while processing the transaction.', sliderevsherlockpaymentException::SLIDEREVSHERLOCKPAYMENT_PAYMENT_TEMPORARY_TECHNICAL_PROBLEM);
            case '97':
                throw new sliderevsherlockpaymentException('Cancellation of payment', sliderevsherlockpaymentException::SLIDEREVSHERLOCKPAYMENT_PAYMENT_CANCELLED);
        }
    }

    /**
     * Set order status to error
     * @throws sliderevsherlockpaymentException
     */
    private function set_order_status_to_error(): void
    {
        $cart = new Cart(Tools::getValue('id_cart'));
        if (!Validate::isLoadedObject($cart)) {
            throw new sliderevsherlockpaymentException('No cart found', sliderevsherlockpaymentException::PRESTASHOP_CART_NOT_FOUND);
        }

        /** @var Order $order */
        $order = Order::getByCartId($cart->id);
        if (!Validate::isLoadedObject($order)) {
            throw new sliderevsherlockpaymentException('No order found', sliderevsherlockpaymentException::PRESTASHOP_ORDER_NOT_FOUND);
        }
        $order->setCurrentState(Configuration::get('PS_OS_ERROR'));
    }

    /**
     * Process content
     *
     * @throws sliderevsherlockpaymentException
     * @throws Exception
     */
    private function process_content(): void
    {
        $cart = new Cart(Tools::getValue('id_cart'));
        if (!Validate::isLoadedObject($cart)) {
            throw new sliderevsherlockpaymentException('No cart found', sliderevsherlockpaymentException::PRESTASHOP_CART_NOT_FOUND);
        }

        /** @var Sliderevsherlockpayment $module */
        $module = Module::getInstanceByName('SLIDEREVSHERLOCKPAYMENT');
        if (!Validate::isLoadedObject($module)) {
            throw new sliderevsherlockpaymentException('No module found', sliderevsherlockpaymentException::PRESTASHOP_MODULE_NOT_FOUND);
        }

        /** @var Order $order */
        $order = Order::getByCartId($cart->id);
        if (!Validate::isLoadedObject($order)) {
            throw new sliderevsherlockpaymentException('No order found', sliderevsherlockpaymentException::PRESTASHOP_ORDER_NOT_FOUND);
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            throw new sliderevsherlockpaymentException('No customer found', sliderevsherlockpaymentException::PRESTASHOP_CUSTOMER_NOT_FOUND);
        }

        // Re log the user after 3DS secure payment
        $this->context->updateCustomer($customer);

        // Confirm the order
        $order->setCurrentState(Configuration::get('PS_OS_PAYMENT'));

        // redirect
        Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $cart->id
            . '&id_module=' . $module->id . '&id_order=' . $order->id
            . '&key=' . $customer->secure_key);
    }
}