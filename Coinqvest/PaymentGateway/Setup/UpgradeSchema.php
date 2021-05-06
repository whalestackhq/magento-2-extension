<?php

namespace Coinqvest\PaymentGateway\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;


class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {

        $connection = $setup->getConnection();

        $connection->addColumn(
            $setup->getTable('quote'),
            'coinqvest_checkout_id',
            [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'Coinqvest Checkout Id'
            ]
        );

        $connection->addColumn(
            $setup->getTable('sales_order'),
            'coinqvest_checkout_id',
            [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'Coinqvest Checkout Id'
            ]
        );

        $connection->addColumn(
            $setup->getTable('sales_order_payment'),
            'coinqvest_tx_id',
            [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'Coinqvest Transaction Id'
            ]
        );

    }
}