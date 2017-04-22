<?php
namespace Cadence\Heimdall\Framework\Authenticator;
/**
 * @author Alan Barber <alan@cadence-labs.com>
 */
abstract class AbstractAuthenticator
{
    /**
     * @return string
     */
    abstract public function generateSecret();

    /**
     * @param string $secret
     * @return string
     */
    abstract public function getHumanSecret($secret);

    /**
     * @param string $code
     * @param string $secret
     * @return bool
     */
    abstract public function verifyCode($code, $secret);

    /**
     * @return bool
     */
    abstract public function supportsQrCode();

    /**
     * @return string
     * @throws \Exception
     */
    public function getQrCode($secret)
    {
        if (!$this->supportsQrCode()) {
            throw new \Exception("MFA Authenticator " . __CLASS__ . " does not support QR Code Generation!");
        }

        return "";
    }

}