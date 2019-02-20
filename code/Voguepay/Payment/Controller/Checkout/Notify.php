<?php
/**
 * Copyright © 2018 Voguepay All rights reserved.
 */

namespace Voguepay\Payment\Controller\Checkout;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Checkout\Model\Session;

class Notify extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    /**
     *
     * @var \Payssion\Payment\Model\Config
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
    
    
    const STATE_PAID = 2;
    const TRANSACTION_TYPE_ORDER = 'order';
    
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
    	$params = $this->getRequest()->getParams();
    	if(isset($params['transaction_id'])) {
            $response = $this->validateNotify($params);

            if (isset($response['merchant_ref'])) {
                $orderModel = $this->_objectManager->get('Magento\Sales\Model\Order');

                $merchant_ref = $response['merchant_ref'];
                $explode_ref = explode('-', $merchant_ref);

                if(count($explode_ref) == 3) {
                    $orderIncrementId = $explode_ref[0];

                    $order = $orderModel->loadByIncrementId($orderIncrementId);
                    if (empty($order)) {
                        echo 'order not found';
                    } else {

                        $total = $order->getGrandTotal();
                        $currency = $order->getOrderCurrencyCode();

                        if ($total != $explode_ref[1]) {
                            echo "invalid transaction";
                        }elseif($currency != $explode_ref[2]){
                            echo "invalid transaction";
                        } else {

                            $orderStatus = null;
                            switch ($response['status']) {
                                case 'Approved':
                                    $orderStatus = $orderModel::STATE_PROCESSING;
                                    $this->createOrderInvoice($orderModel, $response);
                                    break;
                                case 'cancelled_by_user':
                                case 'cancelled':
                                case 'failed':
                                case 'error':
                                case 'Declined':
                                    $orderStatus = $orderModel::STATE_CANCELED;
                                    break;
                                default:
                                    break;
                            }

                            if ($orderStatus) {
                                $orderModel->setStatus($orderStatus);
                                $orderModel->save();
                                echo 'success';
                            } else {
                                echo 'failed to update';
                            }
                        }
                    }
                }else{
                    echo 'Invalid transaction';
                }

            } else {
                echo 'Invalid transaction id ';
            }
        }else{
    	    echo 'Invalid transaction';
        }
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

//    protected function validateNotify($params)
//    {
//    	$check_parameters = array(
//    			$this->_config->getApiKey(),
//    			$params['pm_id'],
//    			$params['amount'],
//    			$params['currency'],
//    			$params['order_id'],
//    			$params['state'],
//    			$this->_config->getSecretKey()
//    	);
//    	$check_msg = implode('|', $check_parameters);
//    	$check_sig = md5($check_msg);
//    	$notify_sig = $params['notify_sig'];
//    	return ($notify_sig == $check_sig);
//    }
    
    public function createOrderInvoice($order, $response)
    {
    	if ($order->canInvoice()) {
    		$invoice = $this->_objectManager->create('Magento\Sales\Model\Service\InvoiceService')->prepareInvoice($order);
    		$invoice->register();
    		$invoice->setState(self::STATE_PAID);
    		$invoice->save();
    
    		$transactionSave = $this->_objectManager->create('Magento\Framework\DB\Transaction')
    		->addObject($invoice)
    		->addObject($invoice->getOrder());
    		$transactionSave->save();
    
    		$order->addStatusHistoryComment(__('Created invoice #%1.', $invoice->getId()))->setIsCustomerNotified(true)->save();
    
    		$this->createTransaction($order, $response);
    	}
    }
    
    public function createTransaction($order, $response)
    {
    	$payment = $this->_objectManager->create('Magento\Sales\Model\Order\Payment');
    	$payment->setTransactionId($response['merchant_ref']);
    	$payment->setOrder($order);
    	$payment->setIsTransactionClosed(1);
    	$transaction = $payment->addTransaction(self::TRANSACTION_TYPE_ORDER);
    	$transaction->beforeSave();
    	$transaction->save();
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