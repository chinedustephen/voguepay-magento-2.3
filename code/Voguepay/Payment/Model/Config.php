<?php
/**
 * Copyright © 2018 Voguepay All rights reserved.
 */

namespace Voguepay\Payment\Model;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Description of Config
 *
 * @author Voguepay Technical <technical@voguepay.com>
 */
class Config
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfigInterface;

    public function __construct(
    \Magento\Framework\App\Config\ScopeConfigInterface $configInterface
    )
    {
        $this->_scopeConfigInterface = $configInterface;
    }

    public function getApiKey()
    {
        $api_key = $this->_scopeConfigInterface->getValue('payment/voguepay/api_key', 'store');
        return $api_key;
    }


    public function isTestMode()
    {
       return $this->_scopeConfigInterface->getValue('payment/voguepay/test_mode', 'store') == 1;
    }
}