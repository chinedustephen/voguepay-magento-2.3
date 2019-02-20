<?php
/**
 * Copyright © 2018 Voguepay All rights reserved.
 */

namespace Voguepay\Payment\Controller\Checkout;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Checkout\Model\Session;

class Finish extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    /**
     *
     * @var \Voguepay\Payment\Model\Config
     */
    protected $_config;

    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;
    
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Index constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Voguepay\Payment\Model\Config $config
     * @param Session $checkoutSession
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Voguepay\Payment\Model\Config $config,
        Session $checkoutSession,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->_config = $config;
        $this->_checkoutSession = $checkoutSession;
        $this->_logger = $logger;

        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        $params = $this->getRequest()->getParams();

        if(isset($params['transaction_id'])) {
            $transaction_id = $params['transaction_id'];

            if(!empty($transaction_id)) {

                $response = $this->validateNotify($params);

                $orderModel = $this->_objectManager->get('Magento\Sales\Model\Order');
                if (isset($response['merchant_ref'])) {

                    $merchant_ref = $response['merchant_ref'];
                    $explode_ref = explode('-', $merchant_ref);
                    if(count($explode_ref) == 3) {
                        $orderIncrementId = $explode_ref[0];
                        $order = $orderModel->loadByIncrementId($orderIncrementId);

                        if (empty($order)) {
                            $this->messageManager->addNotice(__("Transaction id $transaction_id failed, No order found"));
                            $resultRedirect->setPath('checkout/cart');
                        } else {
                            $total = $order->getGrandTotal();
                            $currency = $order->getOrderCurrencyCode();

                            if ($total != $explode_ref[1]) {
                                $this->messageManager->addNotice(__("Transaction id $transaction_id failed, Invalid transaction total"));
                                $resultRedirect->setPath('checkout/cart');
                            }elseif($currency != $explode_ref[2]){
                                $this->messageManager->addNotice(__("Transaction id $transaction_id failed, Invalid transaction currency"));
                                $resultRedirect->setPath('checkout/cart');
                            } else {

                                if (isset($response['response_message'])) {
                                    $message = "Transaction was not successful";
                                } else {
                                    $message = $response['response_message'];
                                }

                                if ('Approved' == $response['status']) {
                                    $this->_getCheckoutSession()->start();
                                    $resultRedirect->setPath('checkout/onepage/success');
                                } else {
                                    $this->_getCheckoutSession()->restoreQuote();
                                    $this->messageManager->addNotice(__("Transaction id $transaction_id failed, $message"));
                                    $resultRedirect->setPath('checkout/cart');
                                }
                            }
                        }
                    }else{
                        $this->messageManager->addNotice(__("Transaction failed, Invaid transaction"));
                        $resultRedirect->setPath('checkout/cart');
                    }
                } else {
                    $this->messageManager->addNotice(__("Transaction id $transaction_id failed, No transaction found"));
                    $resultRedirect->setPath('checkout/cart');
                }
            }else{
                $this->messageManager->addNotice(__("Transaction failed, No transaction id found"));
                $resultRedirect->setPath('checkout/cart');
            }
        }else{
            $this->_getCheckoutSession()->restoreQuote();
            $this->messageManager->addNotice(__("Transaction failed, Transaction failed security checks"));
            $resultRedirect->setPath('checkout/cart');
        }
        
        return $resultRedirect;
    }

    protected function validateNotify($params){
        $demo =  $this->_config->isTestMode();

        $transaction_id = $params['transaction_id'];
        $query_url = "https://voguepay.com/?v_transaction_id=$transaction_id&type=json";
        if(!empty($demo)){
            if($demo == 1) {
                $query_url = $query_url.'&demo=true';
            }
        }

        if(!empty($transaction_id)){
            $data = file_get_contents($query_url);
            $response = json_decode($data, true);
            return $response;
        }
    }

    /**
     * Return checkout session object
     *
     * @return Session
     */
    protected function _getCheckoutSession()
    {
        return $this->_checkoutSession;
    }
}