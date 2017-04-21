<?php
/**
 * @author Alan Barber <alan@cadence-labs.com>
 */
namespace Cadence\Heimdall\Plugin;
use Cadence\Heimdall\Controller\Adminhtml\Secret\Reset;
use Cadence\Heimdall\Model\Backend\Auth;

class UserEditBeforeView
{
    /**
     * @var \Magento\Framework\View\Element\Context
     */
    protected $context;

    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    protected $authorization;

    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Magento\Framework\AuthorizationInterface $authorization
    )
    {
        $this->context = $context;
        $this->authorization = $authorization;
    }

    public function beforeAddButton(
        \Magento\User\Block\User\Edit $subject,
        $buttonName = null)
    {
        if (!$this->isMfaEnabled()) {
            return;
        }

        if (!$this->authorization->isAllowed(Reset::ADMIN_RESOURCE)) {
            return;
        }

        if ($buttonName == 'back') {
            $request = $this->context->getRequest();
            $userId = $request->getParam('user_id');
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            /** @var \Magento\User\Model\User $user */
            $user = $objectManager->create('\Magento\User\Model\User')->load($userId);
            if ($user->getData('heimdall_secret')) {
                $subject->addButton('heimdall_reset_secret',
                    [
                        'label' => __('Reset MFA Secret'),
                        'class' => 'reset',
                        'onclick' => sprintf(
                            'deleteConfirm("%s", "%s", %s)',
                            __('Are you sure you want to reset the MFA secret? This user will be prompted to connect a new mobile device on their next login.'),
                            $this->context->getUrlBuilder()->getUrl('heimdall/secret/reset'),
                            json_encode(['data' => ['user_id' => $userId]])
                        )
                    ]);
            }
        }
    }

    /**
     * @return mixed
     */
    public function isMfaEnabled()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        return $objectManager->get('\Cadence\Heimdall\Helper\Settings')->isMfaEnabled();
    }
}