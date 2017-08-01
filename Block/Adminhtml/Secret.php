<?php
/**
 * @author Alan Barber <alan@cadence-labs.com
 */
namespace Cadence\Heimdall\Block\Adminhtml;
class Secret extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magento\Backend\Model\Auth|\Cadence\Heimdall\Model\Backend\Auth
     */
    protected $auth;

    /**
     * @var \Cadence\Heimdall\Framework\Authenticator\Google
     */
    protected $mfaAuthenticator;

    /**
     * @var string
     */
    protected $secret;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Model\Auth|\Cadence\Heimdall\Model\Backend\Auth $auth
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Model\Auth $auth,
        array $data = []
    )
    {
        $this->auth = $auth;
        $this->mfaAuthenticator = $auth->getMfaAuthenticator();
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getSecret()
    {
        if (is_null($this->secret)) {
            $this->secret = $this->mfaAuthenticator->generateSecret();
        }
        return $this->secret;
    }

    /**
     * @return string
     */
    public function getHumanSecret()
    {
        return $this->mfaAuthenticator->getHumanSecret($this->getSecret());
    }

    /**
     * @return string
     */
    public function getQrCode()
    {
        return $this->mfaAuthenticator->getQrCode($this->getSecret());
    }

    /**
     * @return \Cadence\Heimdall\Framework\Authenticator\Google
     */
    public function getAuthenticator()
    {
        return $this->mfaAuthenticator;
    }

    /**
     * @return \Magento\Backend\Model\Auth\Credential\StorageInterface|null|\Magento\User\Model\User
     */
    public function getLoginCandidate()
    {
        return $this->auth->getLoginCandidate();
    }


    /**
     * @param string $action
     * @return string
     */
    public function getSecretUrl($action = 'index')
    {
        return $this->_urlBuilder->getUrl('heimdall/secret/' . $action);
    }

    public function renderHasCompletedMfa()
    {
    }
}
