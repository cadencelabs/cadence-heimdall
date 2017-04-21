<?php
/**
 * @author Alan Barber <alan@cadence-labs.com>
 */
namespace Cadence\Heimdall\Plugin\Backend;

use Cadence\Heimdall\Framework\Exception\MfaSecret;

class Authentication extends \Magento\Backend\App\Action\Plugin\Authentication
{
    /**
     * @param \Magento\Backend\App\AbstractAction $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\App\RequestInterface $request
     *
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundDispatch(
        \Magento\Backend\App\AbstractAction $subject,
        \Closure $proceed,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $requestedActionName = $request->getActionName();
        if ($this->_isOpenAction($request)) {
            $request->setDispatched(true);
        } else {
            if ($this->_auth->getUser()) {
                $this->_auth->getUser()->reload();
            }
            if (!$this->_auth->isLoggedIn()) {
                $this->_processNotLoggedInUser($request);
            } else {
                $this->_auth->getAuthStorage()->prolong();

                $backendApp = null;
                if ($request->getParam('app')) {
                    $backendApp = $this->backendAppList->getCurrentApp();
                }

                if ($backendApp) {
                    $resultRedirect = $this->resultRedirectFactory->create();
                    $baseUrl = \Magento\Framework\App\Request\Http::getUrlNoScript($this->backendUrl->getBaseUrl());
                    $baseUrl = $baseUrl . $backendApp->getStartupPage();
                    return $resultRedirect->setUrl($baseUrl);
                }
            }
        }
        $this->_auth->getAuthStorage()->refreshAcl();
        return $proceed($request);
    }

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     * @return bool
     */
    protected function _isOpenAction( \Magento\Framework\App\RequestInterface $request)
    {
        $requestedActionName = $request->getActionName();
        if (in_array($requestedActionName, $this->_openActions)) {
            return true;
        } elseif ($request->getModuleName() == 'heimdall' && in_array($requestedActionName, ['index', 'refresh'])) {
            return true;
        }
        return false;
    }
}