<?php
/**
 * @author Alan Barber <alan@cadence-labs.com>
 */
namespace Cadence\Heimdall\Helper;
class Settings
{
    /**
     * @const Is MFA enabled
     */
    const XML_PATH_MFA_ENABLED = 'heimdall/general/active';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    public function isMfaEnabled()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_MFA_ENABLED, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES) == 1;
    }
}