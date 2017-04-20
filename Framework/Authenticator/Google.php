<?php
namespace Cadence\Heimdall\Framework\Authenticator;
/**
 * @author Alan Barber <alan@cadence-labs.com>
 */
class Google extends AbstractAuthenticator
{
    /**
     * @var \RobThree\Auth\TwoFactorAuth
     */
    protected $tfa;

    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Magento\Backend\Model\Auth\StorageInterface
     */
    protected $authStorage;

    /**
     * Google constructor.
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Backend\Model\Auth|\Cadence\Heimdall\Model\Backend\Auth $auth
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Backend\Model\Auth\StorageInterface $authStorage,
        array $data = []
    ) {
        $this->objectManager = $objectManager;
        $this->authStorage = $authStorage;
        $this->tfa = $this->createTfa();
    }

    /**
     * @return string
     * @throws \RobThree\Auth\TwoFactorAuthException
     */
    public function generateSecret() : string
    {
        return $this->tfa->createSecret(160);
    }

    /**
     * @param string $code
     * @param string $secret
     * @return bool
     */
    public function verifyCode(string $code, string $secret) : bool
    {
        $code = str_replace(" ", "", $code);
        return $this->tfa->verifyCode($secret, $code);
    }

    /**
     * @return bool
     */
    public function supportsQrCode() : bool
    {
        return true;
    }

    public function getQrCode(string $secret) : string
    {
        return $this->tfa->getQRCodeImageAsDataUri($this->getAuthLabel(), $secret);
    }

    /**
     * @param string $secret
     * @return string
     */
    public function getHumanSecret(string $secret) : string
    {
        return chunk_split($secret, 4, ' ');
    }

    /**
     * @return \RobThree\Auth\TwoFactorAuth
     */
    public function createTfa()
    {
        return $this->objectManager->create('\RobThree\Auth\TwoFactorAuth', [
            $this->getAuthLabel()
        ]);
    }

    /**
     * @return mixed|string
     */
    public function getAuthLabel()
    {
        return $this->authStorage->getLoginLabel();
    }
}