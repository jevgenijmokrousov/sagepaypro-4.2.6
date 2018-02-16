<?php

$installer = $this;
$connection = $installer->getConnection();

$installer->startSetup();

$connection->addColumn($this->getTable('sagepaysuite_transaction'), 'acsurl', 'varchar(255)');
$connection->addColumn($this->getTable('sagepaysuite_transaction'), 'md', 'text');
$connection->addColumn($this->getTable('sagepaysuite_transaction'), 'pareq', 'text');
$connection->addColumn($this->getTable('sagepaysuite_transaction'), 'pares', 'text');

$installer->endSetup();