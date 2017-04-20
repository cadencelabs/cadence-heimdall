<?php
/**
 * @author Alan Barber <alan@cadence-labs.com>
 */
namespace Cadence\Heimdall\Controller\Adminhtml\Secret;
class Index extends \Magento\Backend\Controller\Adminhtml\Auth
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * Array of actions which can be processed without secret key validation
     *
     * @var array
     */
    protected $_publicActions = ['index'];

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Administrator login action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $this->messageManager->getMessages(true);
        /** @var \Cadence\Heimdall\Model\Backend\Auth $auth */
        $auth = $this->_auth;

        if (!$auth->getLoginCandidate()) {
            $this->messageManager->addError(__("Login first before attempting MFA."));
            return $this->getRedirect($this->_backendUrl->getStartupPageUrl());
        }

        if ($this->getRequest()->getPost('verification')) {
            $verifyData = $this->getRequest()->getPost('verification');
            $secret = $verifyData['secret'] ?? false;
            $code = $verifyData['code'] ?? false;
            if (!$code) {
                $this->messageManager->addError(__("No verification code provided!"));
            } else if (!$secret) {
                $this->messageManager->addError(__("No secret code provided!"));
            } else {
                if ($auth->getLoginCandidate()->getData('heimdall_secret')) {
                    // Prevent multiple people from completing a verification
                    $this->messageManager->addError("Error! This account already has MFA enabled.");
                    $this->_forward('login', 'auth', 'admin');
                    return;
                }
                if ($auth->verifyMfaCode($code, $secret)) {
                    $this->messageManager->addSuccess("Successfully verified your account!");
                    $auth->completeLogin($secret);

                    return $this->getRedirect($this->_backendUrl->getStartupPageUrl());
                } else {
                    $this->messageManager->addError(__("Invalid verification code provided!"));
                }
            }
        }
        $page = $this->resultPageFactory->create();
        return $page;
    }

    /**
     * Get redirect response
     *
     * @param string $path
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    private function getRedirect($path)
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($path);
        return $resultRedirect;
    }
}
