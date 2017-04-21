<?php
/**
 * @author Alan Barber <alan@cadence-labs.com>
 */
namespace Cadence\Heimdall\Controller\Adminhtml\Secret;
class Reset extends \Magento\Backend\App\AbstractAction
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Cadence_Heimdall::backend_reset';

    /**
     * Administrator login action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $userId = $this->getRequest()->getParam('user_id');
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Magento\User\Model\User $user */
        $user = $objectManager->create('\Magento\User\Model\User')->load($userId);
        if ($user->getId()) {
            $user->setData('heimdall_secret', null)->save();
            $this->messageManager->addSuccess(
                __("Successfully reset MFA secret for user {$user->getEmail()}. They will be prompted to connect a new mobile device on their next login.")
            );
        }
        return $this->_redirect('adminhtml/user/edit', [
            'user_id' => $userId
        ]);
    }
}
