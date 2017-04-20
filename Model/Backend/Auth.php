<?php
/**
 * @author Alan Barber <alan@cadence-labs.com>
 */
namespace Cadence\Heimdall\Model\Backend;

use Cadence\Heimdall\Framework\Exception\MfaSecret;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\Plugin\AuthenticationException as PluginAuthenticationException;
use Magento\Framework\Phrase;

Class Auth extends \Magento\Backend\Model\Auth
{
    /**
     * @const Is MFA enabled
     */
    const XML_PATH_MFA_ENABLED = 'heimdall/general/active';

    /**
     * @const 15 minute window where MFA can be applied after logging in
     */
    const CANDIDATE_TIMEOUT = 900;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var \Cadence\Heimdall\Framework\Authenticator\Google
     */
    protected $mfaAuthenticator;

    /**
     * @var \Magento\User\Model\User|\Magento\Backend\Model\Auth\Credential\StorageInterface
     */
    protected $loginCandidate;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Security\Model\AdminSessionsManager
     */
    protected $sessionsManager;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\Magento\Store\Model\Store
     */
    protected $storeManager;

    /**
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Backend\Helper\Data $backendData
     * @param \Magento\Backend\Model\Auth\StorageInterface $authStorage
     * @param \Magento\Backend\Model\Auth\Credential\StorageInterface $credentialStorage
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $coreConfig
     * @param \Magento\Framework\Data\Collection\ModelFactory $modelFactory
     */
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Backend\Helper\Data $backendData,
        \Magento\Backend\Model\Auth\StorageInterface $authStorage,
        \Magento\Backend\Model\Auth\Credential\StorageInterface $credentialStorage,
        \Magento\Framework\App\Config\ScopeConfigInterface $coreConfig,
        \Magento\Framework\Data\Collection\ModelFactory $modelFactory
    ) {

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_request = $objectManager->get('Magento\Framework\App\RequestInterface');
        $this->mfaAuthenticator = $objectManager->get('\Cadence\Heimdall\Framework\Authenticator\Google');
        $this->scopeConfig = $objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');
        $this->sessionsManager = $objectManager->get('\Magento\Security\Model\AdminSessionsManager');
        $this->storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');


        parent::__construct($eventManager, $backendData, $authStorage, $credentialStorage, $coreConfig, $modelFactory);
    }

    /**
     * Perform login process
     *
     * @param string $username
     * @param string $password
     * @return void
     * @throws \Magento\Framework\Exception\AuthenticationException
     */
    public function login($username, $password)
    {
        if (!$this->isMfaEnabled()) {
            parent::login($username, $password);
            return;
        }
        if (empty($username) || empty($password)) {
            self::throwException(__('You did not sign in correctly or your account is temporarily disabled.'));
        }

        try {
            $this->_initCredentialStorage();
            $this->getCredentialStorage()->login($username, $password);
            if ($this->getCredentialStorage()->getId()) {

                $this->handleMfa();
            }

            if (!$this->getAuthStorage()->getUser()) {
                self::throwException(__('You did not sign in correctly or your account is temporarily disabled.'));
            }
        } catch (PluginAuthenticationException $e) {
            $this->_eventManager->dispatch(
                'backend_auth_user_login_failed',
                ['user_name' => $username, 'exception' => $e]
            );
            throw $e;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->_eventManager->dispatch(
                'backend_auth_user_login_failed',
                ['user_name' => $username, 'exception' => $e]
            );
            self::throwException(
                __($e->getMessage()? : 'You did not sign in correctly or your account is temporarily disabled.')
            );
        }
    }

    /**
     * @param \Magento\User\Model\User|\Magento\Backend\Model\Auth\Credential\StorageInterface $user
     * @return $this
     * @throws MfaSecret
     */
    public function setupMfa(\Magento\Backend\Model\Auth\Credential\StorageInterface $user)
    {
        // Set the user to context of who to login
        $this->setLoginCandidate($user);

         $request = $this->_request;
         $request->setForwarded(true)
             ->setRouteName('adminhtml')
             ->setModuleName('heimdall')
             ->setControllerName('secret')
             ->setActionName('index')
             ->setDispatched(false);
        return $this;
    }

    /**
     * @return Auth
     */
    public function handleMfa()
    {
        /** @var \Magento\User\Model\User $user */
        $user = $this->getCredentialStorage();

        if (!$user->getData('heimdall_secret')) {
            return $this->setupMfa($user);
        } else {
            return $this->continueLogin();
        }
    }

    /**
     * @return $this
     */
    public function continueLogin()
    {
        $this->getAuthStorage()->setUser($this->getCredentialStorage());
        $this->getAuthStorage()->processLogin();

        $this->_eventManager->dispatch(
            'backend_auth_user_login_success',
            ['user' => $this->getCredentialStorage()]
        );

        return $this;
    }

    /**
     * @return $this
     */
    public function completeLogin($secret)
    {
        $this->_credentialStorage = $this->getLoginCandidate();

        $this->continueLogin();

        if ($secret) {
            // Save the secret key
            $this->getUser()
                ->setData('heimdall_secret', $secret)
                ->save();
        }

        $this->sessionsManager->processLogin();

        return $this;
    }

    /**
     * @param $code
     * @param null $secret
     * @return bool
     */
    public function verifyMfaCode($code, $secret = null)
    {
        if (is_null($secret)) {
            $secret = $this->getLoginCandidate()->getData('heimdall_secret');
        }
        return $this->mfaAuthenticator->verifyCode($code, $secret);
    }

    /**
     * @return \Cadence\Heimdall\Framework\Authenticator\Google
     */
    public function getMfaAuthenticator()
    {
        return $this->mfaAuthenticator;
    }

    /**
     * @return \Magento\Backend\Model\Auth\Credential\StorageInterface|null|\Magento\User\Model\User
     */
    public function getLoginCandidate()
    {
        return $this->getAuthStorage()->getLoginCandidate();
    }

    /**
     * @param \Magento\Backend\Model\Auth\Credential\StorageInterface $user
     * @return $this
     */
    public function setLoginCandidate($user)
    {
        $this->getAuthStorage()->setLoginCandidate($user);
        $this->getAuthStorage()->setCandidateTime(time());
        $this->getAuthStorage()->setLoginLabel($this->getMfaLabel());
        return $this;
    }

    /**
     * @return bool
     */
    public function checkCandidateExpired()
    {
        if (!$this->getLoginCandidate()) {
            return false;
        }
        if (!$this->getAuthStorage()->getCandidateTime() || $this->isCandidateExpired()) {
            $this->setLoginCandidate(null);
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isCandidateExpired()
    {
        return time() > $this->getAuthStorage()->getCandidateTime() + self::CANDIDATE_TIMEOUT;
    }

    /**
     * @return mixed
     */
    public function isMfaEnabled()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_MFA_ENABLED, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES) == 1;
    }

    /**
     * @return string
     */
    public function getMfaLabel()
    {
        $url = $this->storeManager->getStore()->getCurrentUrl();
        $host = parse_url($url, PHP_URL_HOST) ?? "Magento 2 Admin";
        $candidate = $this->getLoginCandidate() ?? "Unknown";
        return $host . ' - ' . $candidate->getEmail();
    }
}