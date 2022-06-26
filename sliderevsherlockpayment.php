<?php
/**
 * 2007-2022 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2022 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Sliderevsherlockpayment extends PaymentModule
{
    protected bool $config_form = false;
    /**
     * @var string[]
     */
    private array $limited_currencies;

    public function __construct()
    {
        $this->name = 'sliderevsherlockpayment';
        $this->tab = 'payments_gateways';
        $this->version = '0.0.6';
        $this->author = 'AlexisVS';
        $this->need_instance = 0;
        $this->controllers = ['paymentResponse', 'validation'];

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('SLIDE r.e.v sherlock\'s payment', [], 'Modules.Sliderevsherlockpayment.Sliderevsherlockpayment.php');
        $this->description = $this->trans('Sherlock\'s is a solution for professionals, which secures payments received by credit card on the Internet.
This module has been developed by AlexisVS employed in the SLIDE r.e.v society', [], 'Modules.Sliderevsherlockpayment.Sliderevsherlockpayment.php');

        $this->limited_countries = $this->get_iso_code_countries_europe();

        $this->limited_currencies = array('EUR');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    /**
     * Get all iso code countries from the Europe.
     *
     * @return string[]
     */
    final private function get_iso_code_countries_europe(): array
    {
        $limited_countries = [];
        $europeanCountries = Country::getCountriesByZoneId(Country::getIdZone($this->context->country->id), $this->context->language->id);
        foreach ($europeanCountries as $country) {
            $limited_countries[] = $country['iso_code'];
        }
        return $limited_countries;
    }

    /**
     * Activate new translation system
     *
     * @see PaymentModule::isUsingNewTranslationSystem()
     */
    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install(): bool
    {
        if (extension_loaded('curl') == false) {
            $this->_errors[] = $this->trans('You have to enable the cURL extension on your server to install this module', [], 'Modules.Sliderevsherlockpayment.Sliderevsherlockpayment.php');
            return false;
        }

        $iso_code = Country::getIsoById(Configuration::get('PS_COUNTRY_DEFAULT'));

        if (in_array($iso_code, $this->limited_countries) == false) {
            $this->_errors[] = $this->trans('This module is not available in your country', [], 'Modules.Sliderevsherlockpayment.Sliderevsherlockpayment.php');
            return false;
        }

        Configuration::updateValue('SLIDEREVSHERLOCKPAYMENT_ORDER_STATE_PENDING_ID', 10);
        Configuration::updateValue('SLIDEREVSHERLOCKPAYMENT_TEST_MODE', false);
        Configuration::updateValue('SLIDEREVSHERLOCKPAYMENT_TEST_MERCHANT_ID', '002016000000001');
        Configuration::updateValue('SLIDEREVSHERLOCKPAYMENT_TEST_SECRET_KEY', '002016000000001_KEY1');
        Configuration::updateValue('SLIDEREVSHERLOCKPAYMENT_TEST_KEY_VERSION', '1');
//        Configuration::updateValue('SLIDEREVSHERLOCKPAYMENT_MERCHANT_ID', '');
//        Configuration::updateValue('SLIDEREVSHERLOCKPAYMENT_SECRET_KEY', '');
//        Configuration::updateValue('SLIDEREVSHERLOCKPAYMENT_KEY_VERSION', '');

        return parent::install()
            && $this->registerHook('header')
            && $this->registerHook('backOfficeHeader')
            && $this->registerHook('paymentOptions')
            && $this->registerHook('actionPaymentConfirmation')
            && $this->registerHook('displayPayment');
    }

    public function uninstall(): bool
    {
        Configuration::deleteByName('SLIDEREVSHERLOCKPAYMENT_TEST_MODE');
        Configuration::deleteByName('SLIDEREVSHERLOCKPAYMENT_TEST_MERCHANT_ID');
        Configuration::deleteByName('SLIDEREVSHERLOCKPAYMENT_TEST_SECRET_KEY');
        Configuration::deleteByName('SLIDEREVSHERLOCKPAYMENT_TEST_KEY_VERSION');
        Configuration::deleteByName('SLIDEREVSHERLOCKPAYMENT_MERCHANT_ID');
        Configuration::deleteByName('SLIDEREVSHERLOCKPAYMENT_SECRET_KEY');
        Configuration::deleteByName('SLIDEREVSHERLOCKPAYMENT_KEY_VERSION');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     * @throws SmartyException
     */
    public function getContent(): string
    {
        /**
         * If values have been submitted in the form, process.
         */
        if ((Tools::isSubmit('submitSliderevsherlockpaymentModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output . $this->renderForm();
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues(): array
    {
        return array(
            'SLIDEREVSHERLOCKPAYMENT_ORDER_STATE_PENDING_ID' => Configuration::get('SLIDEREVSHERLOCKPAYMENT_ORDER_STATE_PENDING_ID'),
            'SLIDEREVSHERLOCKPAYMENT_TEST_MODE' => Configuration::get('SLIDEREVSHERLOCKPAYMENT_TEST_MODE'),
            'SLIDEREVSHERLOCKPAYMENT_TEST_MERCHANT_ID' => Configuration::get('SLIDEREVSHERLOCKPAYMENT_TEST_MERCHANT_ID'),
            'SLIDEREVSHERLOCKPAYMENT_TEST_SECRET_KEY' => Configuration::get('SLIDEREVSHERLOCKPAYMENT_TEST_SECRET_KEY'),
            'SLIDEREVSHERLOCKPAYMENT_TEST_KEY_VERSION' => Configuration::get('SLIDEREVSHERLOCKPAYMENT_TEST_KEY_VERSION'),
            'SLIDEREVSHERLOCKPAYMENT_MERCHANT_ID' => Configuration::get('SLIDEREVSHERLOCKPAYMENT_MERCHANT_ID'),
            'SLIDEREVSHERLOCKPAYMENT_SECRET_KEY' => Configuration::get('SLIDEREVSHERLOCKPAYMENT_SECRET_KEY'),
            'SLIDEREVSHERLOCKPAYMENT_KEY_VERSION' => Configuration::get('SLIDEREVSHERLOCKPAYMENT_KEY_VERSION'),
            'SLIDEREVSHERLOCKPAYMENT_POST_REQUEST_DEV_MODE' => Configuration::get('SLIDEREVSHERLOCKPAYMENT_POST_REQUEST_DEV_MODE'),
            'SLIDEREVSHERLOCKPAYMENT_POST_REQUEST_PROD_MODE' => Configuration::get('SLIDEREVSHERLOCKPAYMENT_POST_REQUEST_PROD_MODE'),
        );
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm(): string
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitSliderevsherlockpaymentModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm([$this->getConfigForm()]);
    }

    /**
     * Create the structure of your form.
     * @throws PrestaShopDatabaseException
     */
    protected function getConfigForm(): array
    {
        $options = $this->get_options_orderState_config_form();
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->trans('Settings', [], 'Modules.Sliderevsherlockpayment.Sliderevsherlockpayment.php'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'select',
                        'label' => $this->trans('Choose order states for pending order', [], 'Modules.Sliderevsherlockpayment.Sliderevsherlockpayment.php'),
                        'name' => 'SLIDEREVSHERLOCKPAYMENT_ORDER_STATE_PENDING_ID',
                        'required' => true,
                        'options' => array(
                            'query' => $options,
                            'id' => 'id_order_state',
                            'name' => 'name'
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->trans('Test mode', [], 'Modules.Sliderevsherlockpayment.Sliderevsherlockpayment.php'),
                        'name' => 'SLIDEREVSHERLOCKPAYMENT_TEST_MODE',
                        'is_bool' => true,
                        'desc' => $this->trans('Use this module in test mode', [], 'Modules.Sliderevsherlockpayment.Sliderevsherlockpayment.php'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->trans('Enabled', [], 'Modules.Sliderevsherlockpayment.Sliderevsherlockpayment.php')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->trans('Disabled', [], 'Modules.Sliderevsherlockpayment.Sliderevsherlockpayment.php')
                            )
                        ),
                    ),
                    array(
//                        'col' => 3,
                        'type' => 'text',
//                        'prefix' => '<i class="icon icon-envelope"></i>',
//                        'desc' => $this->trans('Enter a valid test merchant ID', [], 'Modules.Sliderevsherlockpayment.Sliderevsherlockpayment.php'),
                        'name' => 'SLIDEREVSHERLOCKPAYMENT_TEST_MERCHANT_ID',
                        'label' => $this->trans('Test merchant ID', [], 'Modules.Sliderevsherlockpayment.Sliderevsherlockpayment.php'),
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'SLIDEREVSHERLOCKPAYMENT_TEST_SECRET_KEY',
                        'label' => $this->trans('Test secret key', [], 'Modules.Sliderevsherlockpayment.Sliderevsherlockpayment.php'),
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'SLIDEREVSHERLOCKPAYMENT_TEST_KEY_VERSION',
                        'label' => $this->trans('Test key version', [], 'Modules.Sliderevsherlockpayment.Sliderevsherlockpayment.php'),
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'SLIDEREVSHERLOCKPAYMENT_POST_REQUEST_DEV_MODE',
                        'label' => $this->trans('Post request in development mode', [], 'Modules.Sliderevsherlockpayment.Sliderevsherlockpayment.php')
                    ),
                    array(
//                        'col' => 3,
                        'type' => 'text',
//                        'desc' => $this->trans('Enter a valid  merchant ID', [], 'Modules.Sliderevsherlockpayment.Sliderevsherlockpayment.php'),
                        'name' => 'SLIDEREVSHERLOCKPAYMENT_MERCHANT_ID',
                        'label' => $this->trans('Merchant ID', [], 'Modules.Sliderevsherlockpayment.Sliderevsherlockpayment.php'),
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'SLIDEREVSHERLOCKPAYMENT_SECRET_KEY',
                        'label' => $this->trans('Secret key', [], 'Modules.Sliderevsherlockpayment.Sliderevsherlockpayment.php'),
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'SLIDEREVSHERLOCKPAYMENT_KEY_VERSION',
                        'label' => $this->trans('Key version', [], 'Modules.Sliderevsherlockpayment.Sliderevsherlockpayment.php'),
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'SLIDEREVSHERLOCKPAYMENT_POST_REQUEST_PROD_MODE',
                        'label' => $this->trans('Post request in production mode', [], 'Modules.Sliderevsherlockpayment.Sliderevsherlockpayment.php')
                    ),
                ),
                'submit' => array(
                    'title' => $this->trans('Save', [], 'Modules.Sliderevsherlockpayment.Sliderevsherlockpayment.php'),
                ),
            )
        );
    }

    /**
     * Get options for OrderState input in configForm
     * @throws PrestaShopDatabaseException
     */
    private function get_options_orderState_config_form()
    {
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);

        return $db->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'order_state_lang WHERE id_lang = 1');

    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path . '/views/js/front.js');
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
    }

    /**
     * Return payment options available for PS 1.7+
     *
     * @param array Hook parameters
     *
     * @return array|null
     */
    public function hookPaymentOptions($params): ?array
    {
        if (!$this->active) {
            return null;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return null;
        }
        $instantPayment = new PaymentOption();
        $instantPayment
            ->setCallToActionText($this->trans("sherlock's payment", [], 'Modules.Sliderevsherlockpayment.Sliderevsherlockpayment.php'))
            ->setLogo($this->getPathUri() . 'logo.png')


            // Action in /controllers/front/validation
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', ['instalmentPayment' => 'false'], true));
        /* TODO:: If does add additional input in payment button form
              ->setInputs([
                    'token' => [
                        'name' =>'token',
                        'type' =>'hidden',
                        'value' =>'12345689',
                    ],
                ])
            and for add template at the bottom of the button
            ->setAdditionalInformation($this->context->smarty->fetch('module:sliderevsherlockpayment/views/templates/front/payment_infos.tpl'))
        */

        $instalmentPayment = new PaymentOption();
        $instalmentPayment
            ->setCallToActionText($this->trans('sherlock payment in installment', [], 'Modules.Sliderevsherlockpayment.Sliderevsherlockpayment.php'))
            ->setLogo($this->getPathUri() . 'logo.png')
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', ['instalmentPayment' => 'true'], true));

        return [
            $instantPayment,
            $instalmentPayment
        ];
    }

    public function checkCurrency($cart): bool
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function hookActionPaymentConfirmation()
    {
        /* Place your code here. */
    }

    public function hookDisplayPayment()
    {
        /* Place your code here. */
    }

}
