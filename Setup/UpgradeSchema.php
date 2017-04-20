<?php
/**
 * @author Alan Barber <alan@cadence-labs.com>
 */
namespace Cadence\Heimdall\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if(!$context->getVersion()) {
            $this->_addSecretTouser($setup)
                ->_createUserCookieTable($setup);
        }

        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    protected function _addSecretTouser(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $connection->addColumn(
            $setup->getTable('admin_user'),
            'heimdall_secret',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'MFA Secret Code'
            ]
        );
        return $this;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    protected function _createUserCookieTable(SchemaSetupInterface $setup)
    {
        $tableName = $setup->getTable('heimdall_user_cookie');
        $table = $setup->getConnection()
            ->newTable($tableName)
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary' => true
                ],
                'ID'
            )
            ->addColumn(
                'user_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'unsigned' => true],
                'Admin User'
            )
            ->addColumn(
                'code',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Cookie Code'
            )
            ->setComment('News Table')
            ->setOption('type', 'InnoDB')
            ->setOption('charset', 'utf8');
        $setup->getConnection()->createTable($table);

        // Add a foreign key to the admin table
        $adminUserTable = $setup->getTable('admin_user');
        $setup->getConnection()->query(
            "ALTER TABLE {$tableName} ADD CONSTRAINT `fk_heimdall_uc_au` FOREIGN KEY (user_id) REFERENCES {$adminUserTable} (user_id) ON DELETE CASCADE"
        );
        // Add an index on the cookie code for quick lookups
        $setup->getConnection()->query(
            "ALTER TABLE {$tableName} ADD INDEX `idx_heimdall_uc_code` (code)"
        );

        return $this;
    }
}
