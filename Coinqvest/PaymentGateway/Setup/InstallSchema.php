<?php

namespace Coinqvest\PaymentGateway\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;


class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $installer->getConnection()->addColumn(
            $installer->getTable('quote'),
            'coinqvest_checkout_id',
            [
                'type' => 'text',
                'nullable' => true,
                'comment' => 'Coinqvest Checkout Id'
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'coinqvest_checkout_id',
            [
                'type' => 'text',
                'nullable' => true,
                'comment' => 'Coinqvest Checkout Id'
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order_payment'),
            'coinqvest_tx_id',
            [
                'type' => 'text',
                'nullable' => true,
                'comment' => 'Coinqvest Transaction Id'
            ]
        );

        $setup->endSetup();
    }
}