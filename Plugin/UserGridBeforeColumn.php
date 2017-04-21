<?php
/**
 * @author Alan Barber <alan@cadence-labs.com>
 */
namespace Cadence\Heimdall\Plugin;
use Cadence\Heimdall\Controller\Adminhtml\Secret\Reset;
use Cadence\Heimdall\Model\Backend\Auth;

class UserGridBeforeColumn
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

    /**
     * @param \Magento\Backend\Block\Widget\Grid\Column $subject
     * @param \Magento\Framework\DataObject $row
     */
    public function beforeGetRowField(
        \Magento\Backend\Block\Widget\Grid\Column $subject,
        \Magento\Framework\DataObject $row)
    {
        if (!$this->isMfaEnabled()) {
            return;
        }

        if ($subject->getNameInLayout() == 'heimdall.secret.complete') {
            $subject->setFrameCallback([
                $this, 'getSecretComplete'
            ]);
        }
    }

    /**
     * @param \Magento\Backend\Block\Widget\Grid\Column $subject
     * @param \Magento\Framework\DataObject $row
     */
    public function beforeGetRowFieldExport(
        \Magento\Backend\Block\Widget\Grid\Column $subject,
        \Magento\Framework\DataObject $row)
    {
        if (!$this->isMfaEnabled()) {
            return;
        }

        if ($subject->getNameInLayout() == 'heimdall.secret.complete') {
            $subject->setFrameCallback([
                $this, 'getSecretComplete'
            ]);
        }
    }

    /**
     * @param $renderedValue
     * @param $row
     * @return string
     */
    public function getSecretComplete($renderedValue, $row)
    {
        return strlen(trim($renderedValue)) > 0
            ? "Yes" : "No";
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