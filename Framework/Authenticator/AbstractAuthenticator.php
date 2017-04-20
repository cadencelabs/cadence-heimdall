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
    abstract public function generateSecret() : string;

    /**
     * @param string $secret
     * @return string
     */
    abstract public function getHumanSecret(string $secret) : string;

    /**
     * @param string $code
     * @param string $secret
     * @return bool
     */
    abstract public function verifyCode(string $code, string $secret) : bool;

    /**
     * @return bool
     */
    abstract public function supportsQrCode() : bool;

    /**
     * @return string
     * @throws \Exception
     */
    public function getQrCode(string $secret) : string
    {
        if (!$this->supportsQrCode()) {
            throw new \Exception("MFA Authenticator " . __CLASS__ . " does not support QR Code Generation!");
        }

        return "";
    }

}