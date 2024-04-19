<?php

namespace Sunnysideup\PaymentAliPay;

use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBMoney;
use SilverStripe\View\Requirements;
use Sunnysideup\Ecommerce\Forms\OrderForm;
use Sunnysideup\Ecommerce\Model\Money\EcommercePayment;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Money\Payment\PaymentResults\EcommercePaymentFailure;
use Sunnysideup\Ecommerce\Money\Payment\PaymentResults\EcommercePaymentProcessing;
use Sunnysideup\PaymentAliPay\Control\AliPayPaymentHandler;
use Sunnysideup\PaymentDirectcredit\DirectCreditPayment;

class AliPayPayment extends EcommercePayment
{
    private static $table_name = 'AliPayPayment';

    private static $db = [
    ];

    // AliPay Information

    private static $qrcode_image = 'app/client/images/alipay.png';

    private static $logo = 'sunnysideup/payment-alipay: client/dist/images/alipay.png';

    /**
     * Default Status for Payment.
     *
     * @var string
     */
    private static $default_status = EcommercePayment::PENDING_STATUS;

    private static $after_payment_message_zh = '我们将通过支付宝/微信检查您的付款状态，并在出现任何问题时通知您。';
    private static $after_payment_message_en = 'We will check your payment status with Alipay / WeChat and let you know if there are any issues.';


    private static $email_debug = false;
    public function getPaymentFormFields($amount = 0, ?Order $order = null): FieldList
    {
        $logo = $this->getLogoResource();
        $info = $logo ;


        return new FieldList(
            new LiteralField('AliPayInfo', $logo),
        );
    }

    public function getLogoResource()
    {
        $logo = $this->config()->get('logo');
        $src = ModuleResourceLoader::singleton()->resolveURL($logo);

        return DBField::create_field(
            'HTMLText',
            '<img src="' . $src . '" alt="Pay with AliPay / WeChat"/>'
        );
    }

    public function getQrCodeImageResource()
    {
        $logo = $this->config()->get('qrcode_image');
        $src = ModuleResourceLoader::singleton()->resolveURL($logo);

        return DBField::create_field(
            'HTMLText',
            '<img src="' . $src . '" alt="Credit card payments powered by Alipay / WeChat"/>'
        );
    }

    public function getPaymentFormRequirements(): array
    {
        return [];
    }


    /**
     * Process the DirectCredit payment method.
     *
     * @param mixed $data
     */
    public function processPayment($data, Form $form)
    {
        $this->Status = Config::inst()->get(DirectCreditPayment::class, 'default_status');

        $this->Message = Config::inst()->get(DirectCreditPayment::class, 'after_payment_message_zh');
        $this->Message .= '<br />';
        $this->Message .=  Config::inst()->get(DirectCreditPayment::class, 'after_payment_message_en');

        $order = $this->getOrderCached();
        //if currency has been pre-set use this
        $currency = $this->Amount->Currency;
        //if amout has been pre-set, use this
        $amount = $this->Amount->Amount;
        if ($order && $order->exists()) {
            //amount may need to be adjusted to total outstanding
            //or amount may not have been set yet
            $amount = $order->TotalOutstanding();
            //get currency from Order
            //this is better than the pre-set currency one
            //which may have been set to the default
            $currencyObject = $order->CurrencyUsed();
            if ($currencyObject) {
                $currency = $currencyObject->Code;
            }
        }
        if (! $amount && ! empty($data['Amount'])) {
            $amount = (float) $data['Amount'];
        }
        if (! $currency && ! empty($data['Currency'])) {
            $currency = (string) $data['Currency'];
        }
        //final backup for currency
        if (! $currency) {
            $currency = EcommercePayment::site_currency();
        }
        $this->Amount->Currency = $currency;
        $this->Amount->Amount = $amount;
        $this->write();

        return $this->showRedirect($amount, $currency, $order);
    }

    public function showRedirect(float $amount, string $currency, Order $order)
    {
        $page = new SiteTree();
        $page->Title = 'Alipay Payment information ...';
        $page->Logo = $this->getQrCodeImageResource();
        $page->Content = $this->renderWith('Sunnysideup\PaymentAliPay\AliPayForm', [
            'Amount' => $amount,
            'Currency' => $currency,
            'Order' => $order,
        ]);
        $controller = new ContentController($page);
        Requirements::clear();

        return EcommercePaymentProcessing::create($controller->RenderWith('Sunnysideup\Ecommerce\PaymentProcessingPage'));
    }


}
