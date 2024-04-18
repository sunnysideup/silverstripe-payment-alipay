<?php

namespace Sunnysideup\PaymentAliPay;

use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Email\Email;
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

class AliPayPayment extends EcommercePayment
{
    private static $table_name = 'AliPayPayment';

    private static $db = [
    ];

    // AliPay Information

    private static $qrcode_image = 'app/client/images/alipay.png';

    private static $logo = 'sunnysideup/payment-alipay: client/images/alipay.png';

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
            '<img src="' . $src . '" alt="Credit card payments powered by AliPay"/>'
        );
    }

    public function getQrCodeImageResource()
    {
        $logo = $this->config()->get('qrcode_image');
        $src = ModuleResourceLoader::singleton()->resolveURL($logo);

        return DBField::create_field(
            'HTMLText',
            '<img src="' . $src . '" alt="Credit card payments powered by AliPay"/>'
        );
    }

    public function getPaymentFormRequirements(): array
    {
        return [];
    }

    /**
     * @param array $data The form request data - see OrderForm
     * @param Form  $form The form object submitted on
     *
     * @return \Sunnysideup\Ecommerce\Money\Payment\EcommercePaymentResult
     */
    public function processPayment($data, Form $form)
    {
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

        return $this->showRedirect($amount, $currency, $order);
    }

    public function executeURL(float $amount, string $currency, Order $order)
    {


        /**
         * build redirection page.
         */
        $page = new SiteTree();
        $page->Title = 'Alipay Payment information ...';
        $page->Logo = $this->getQrCodeImageResource();
        $page->Form = $this->AliPayForm($amount, $currency, $order);
        $controller = new ContentController($page);
        Requirements::clear();

        return EcommercePaymentProcessing::create($controller->RenderWith('Sunnysideup\Ecommerce\PaymentProcessingPage'));
    }

    public function AliPayForm(float $amount, string $currency, Order $order)
    {
        return $this->renderWith('Sunnysideup\PaymentAliPay\AliPayForm', [
            'Amount' => $amount,
            'Currency' => $currency,
            'Order' => $order,
        ]);
    }


}
