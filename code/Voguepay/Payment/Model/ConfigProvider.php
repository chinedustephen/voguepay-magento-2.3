<?php
/**
 * Copyright © 2018 Voguepay All rights reserved.
 */

namespace Voguepay\Payment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;


class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var string[]
     */
    protected $methodCodes = [
    	
    	// 'voguepay_payment_bitcoin',
    	'voguepay_payment_creditcard_jp',
    	
    ];

    /**
     * @var \Magento\Payment\Model\Method\AbstractMethod[]
     */
    protected $methods = [];

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @param PaymentHelper $paymentHelper
     * @param Escaper $escaper
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        Escaper $escaper
    ) {
    	$this->escaper = $escaper;
        foreach ($this->methodCodes as $code) {
        	$this->methods[$code] = $paymentHelper->getMethodInstance($code);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
    	$config = [];
        foreach ($this->methodCodes as $code) {
        	if ($this->methods[$code]->isAvailable()) {
                $config['payment']['instructions'][$code] = $this->getInstructions($code);
                $config['payment']['icon'][$code] = $this->getIcon($code);
            }
        }
        return $config;
    }

    /**
     * Get instructions text from config
     *
     * @param string $code
     * @return string
     */
    protected function getInstructions($code)
    {
        return nl2br($this->escaper->escapeHtml($this->methods[$code]->getInstructions()));
    }

    /**
     * Get payment method icon
     *
     * @param string $code
     * @return string
     */
    protected function getIcon($code)
    {
        return "https://voguepay.com/images/icon.png";
    }
}
