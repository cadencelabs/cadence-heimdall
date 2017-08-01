<?php
/**
 * @author Alan Barber <alan@cadence-labs.com>
 */
namespace Cadence\Heimdall\Model\ResourceModel\User;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Cookie extends AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('heimdall_user_cookie', 'id');
    }
}
