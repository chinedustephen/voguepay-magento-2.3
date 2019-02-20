<?php
/**
 * Copyright © 2018 Voguepay All rights reserved.
 */

namespace Voguepay\Payment\Controller\Checkout;

use Magento\Payment\Helper\Data as PaymentHelper;
use Voguepay\Error\Error;

/**
 * Description of Redirect
 *
 * @author Voguepay Technical <technical@voguepay.com>
 */
class Redirect extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Voguepay\Payment\Model\Config
     */
    protected $_config;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var PaymentHelper
     */
    protected $_paymentHelper;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Voguepay\Payment\Model\Config $config
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Voguepay\Payment\Model\Config $config,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Psr\Log\LoggerInterface $logger,
        PaymentHelper $paymentHelper
    )
    {
        $this->_config = $config; // Voguepay config helper
        $this->_checkoutSession = $checkoutSession;
        $this->_logger = $logger;
        $this->_paymentHelper = $paymentHelper;

        parent::__construct($context);
    }

    public function execute()
    {
        try {

            $order = $this->_getCheckoutSession()->getLastRealOrder();
            $method = $order->getPayment()->getMethod();


            $data['total'] = $total = $order->getGrandTotal();
//            $data['items'] = $items = $order->getAllVisibleItems();

            $data['order_id'] = $order_id = $order->getIncrementId();
            $data['quote_id'] = $quoteId = $order->getQuoteId();

            $data['currency'] = $currency = $order->getOrderCurrencyCode();

            $data['notify_url'] = $notify_url = urlencode($this->_url->getUrl('voguepay/checkout/notify/'));
            $data['return url'] = $return_url = urlencode($this->_url->getUrl('voguepay/checkout/finish/'));

            $data['api_key'] = $merchant_id = $this->_config->getApiKey();

            $data['test'] = $demo =  $this->_config->isTestMode();
            $data['description'] = $description =  urlencode("Order - $order_id");

            if(!empty($demo)){
                if($demo == 1) {
                    $merchant_id = 'demo';
                }
            }
            $data['merchant_id'] = $merchant_id;
            $merchant_ref = $order_id.'-'.$total . '-' .$currency;

            $vurl = "https://voguepay.com/?p=linkToken&v_merchant_id=$merchant_id&memo=$description&total=$total&merchant_ref=$merchant_ref&notify_url=$notify_url&success_url=$return_url&fail_url=$return_url&developer_code=5c655d7b30eb7&cur=$currency";

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $vurl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "Cache-Control: no-cache",
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                $this->messageManager->addNotice(__("Error connecting to gateway. Please try again later"));

                $this->_getCheckoutSession()->restoreQuote();
                return $this->_redirect('checkout/cart');
            }

            header("Location: $response");
            exit;

        } catch (\Exception $e) {
//            $this->messageManager->addException($e, __('Something went wrong, please try again later'));
//            $this->_logger->critical($e);
            $this->messageManager->addNotice(__("Error connecting to gateway. Please try again later"));

           $this->_getCheckoutSession()->restoreQuote();
           return $this->_redirect('checkout/cart');
        }
    }

    /**
     * Return checkout session object
     *
     * @return \Magento\Checkout\Model\Session
     */
    protected function _getCheckoutSession()
    {
        return $this->_checkoutSession;
    }
}