<?php
/**
 * @author Alan Barber <alan@cadence-labs.com>
 */
namespace Cadence\Heimdall\User;

use Magento\Framework\Model\AbstractModel;

class Cookie extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Cadence\Heimdall\Model\Resource\User\Cookie');
    }
}