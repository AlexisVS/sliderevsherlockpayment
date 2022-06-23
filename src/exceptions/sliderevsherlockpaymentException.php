<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace PrestaShop\Module\sliderevsherlockpayment\Exception;

class sliderevsherlockpaymentException extends \Exception
{
    const UNKNOWN = 0;
    const PRESTASHOP_ORDER_NOT_FOUND = 2;
    const PRESTASHOP_REFUND_ALREADY_SAVED = 3;
    const PRESTASHOP_REFUND_TOTAL_AMOUNT_REACHED = 4;
    const PRESTASHOP_ORDER_STATE_ERROR = 5;
    const PRESTASHOP_CONTEXT_INVALID = 6;
    const PRESTASHOP_PAYMENT_UNAVAILABLE = 7;
    const PSACCOUNT_TOKEN_MISSING = 8;
    const PSACCOUNT_REFRESH_TOKEN_MISSING = 9;
    const SLIDEREVSHERLOCKPAYMENT_MERCHANTID_OR_ACQUERER_CONSTRACT_NOT_VALID = 10;
    const SLIDEREVSHERLOCKPAYMENT_TRANSACTION_PARAMETERS_SEND_INVALID = 11;
    const SLIDEREVSHERLOCKPAYMENT_REQUEST_FORMAT_INVALID = 12;
    const SLIDEREVSHERLOCKPAYMENT_SECURITY_ISSUES = 13;
    const SLIDEREVSHERLOCKPAYMENT_TRANSACTION_ALREADY_EXIST = 14;
    const SLIDEREVSHERLOCKPAYMENT_SERVICE_TEMPORARILY_UNAVAILABLE = 15;
    const PRESTASHOP_ORDER_ID_MISSING = 16;
    const PRESTASHOP_VALIDATE_ORDER = 17;
    const PRESTASHOP_ORDER_PAYMENT = 18;
    const PRESTASHOP_CART_NOT_FOUND = 19;
    const PRESTASHOP_MODULE_NOT_FOUND = 20;
    const PRESTASHOP_CUSTOMER_NOT_FOUND = 21;
    const SLIDEREVSHERLOCKPAYMENT_PAYMENT_RESPONSE_NOT_FOUND = 22;
    const SLIDEREVSHERLOCKPAYMENT_PAYMENT_DECLINED_BY_SHERLOCK = 23;
    const SLIDEREVSHERLOCKPAYMENT_PAYMENT_DENIED_DUE_TO_FRAUD = 24;
    const SLIDEREVSHERLOCKPAYMENT_PAYMENT_SEVERAL_ATTEMPT_FAILED = 25;
    const SLIDEREVSHERLOCKPAYMENT_PAYMENT_TEMPORARY_TECHNICAL_PROBLEM = 26;
    const SLIDEREVSHERLOCKPAYMENT_PAYMENT_CANCELLED = 27;
}
