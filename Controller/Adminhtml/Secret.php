<?php
/**
 * @author Alan Barber <alan@cadence-labs.com>
 */
namespace Cadence\Heimdall\Controller\Adminhtml;
abstract class Secret extends \Magento\Backend\Controller\Adminhtml\Auth
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
    protected $_publicActions = ['index', 'refresh'];

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
     * @param $code
     * @param null $secret
     * @return bool|\Magento\Backend\Model\View\Result\Redirect
     */
    public function isValidCode($code, $secret = null)
    {
        /** @var \Cadence\Heimdall\Model\Backend\Auth $auth */
        $auth = $this->_auth;

        if ($auth->verifyMfaCode($code, $secret)) {
            $this->messageManager->addSuccess("Successfully verified your account!");
            $auth->completeLogin($secret);
            return $this->getRedirect($this->_backendUrl->getStartupPageUrl());
        } else {
            $this->messageManager->addError(__("Invalid verification code provided!"));
        }
        return false;
    }

    /**
     * @return $this
     */
    public function clearMessages()
    {
        $this->messageManager->getMessages(true);
        return $this;
    }

    public function isInvalidMfa()
    {
        /** @var \Cadence\Heimdall\Model\Backend\Auth $auth */
        $auth = $this->_auth;

        if (!$auth->getLoginCandidate() || $auth->checkCandidateExpired()) {
            $this->messageManager->addError(__("Login first before attempting MFA."));
            return $this->getRedirect($this->_backendUrl->getStartupPageUrl());
        }
        return false;
    }

    /**
     * Get redirect response
     *
     * @param string $path
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    protected function getRedirect($path)
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($path);
        return $resultRedirect;
    }
}
