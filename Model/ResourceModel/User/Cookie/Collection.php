<?php
/**
 * @author Alan Barber <alan@cadence-labs.com>
 */
namespace Cadence\Heimdall\Model\ResourceModel\User\Cookie;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'Cadence\Heimdall\Model\User\Cookie',
            'Cadence\Heimdall\Model\ResourceModel\User\Cookie'
        );
    }
}
