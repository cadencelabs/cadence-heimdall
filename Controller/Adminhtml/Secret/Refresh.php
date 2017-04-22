<?php
/**
 * @author Alan Barber <alan@cadence-labs.com>
 */
namespace Cadence\Heimdall\Controller\Adminhtml\Secret;
class Refresh extends \Cadence\Heimdall\Controller\Adminhtml\Secret
{
    /**
     * Administrator login action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $this->clearMessages();

        if ($redirect =$this->isInvalidMfa()) {
            return $redirect;
        }

        /** @var \Cadence\Heimdall\Model\Backend\Auth $auth */
        $auth = $this->_auth;

        if ($this->getRequest()->getPost('verification')) {
            $verifyData = $this->getRequest()->getPost('verification');
            $code = isset($verifyData['code']) ? $verifyData['code'] : false;
            if (!$code) {
                $this->messageManager->addError(__("No verification code provided!"));
            } else {
                if (!$auth->getLoginCandidate()->getData('heimdall_secret')) {
                    // Prevent multiple people from completing a verification
                    $this->messageManager->addError("Error! MFA is not configured for this account!");
                    $this->_forward('login', 'auth', 'admin');
                    return;
                }
                if ($redirect = $this->isValidCode($code)) {
                    return $redirect;
                }
            }
        }
        $page = $this->resultPageFactory->create();
        return $page;
    }
}
