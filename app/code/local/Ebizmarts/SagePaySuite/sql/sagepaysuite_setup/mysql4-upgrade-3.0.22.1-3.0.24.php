<?php

$installer = $this;
$connection = $installer->getConnection();

$installer->startSetup();

$connection->addColumn($this->getTable('sagepaysuite_transaction'), 'server_success_arrived', 'TINYINT(1) null');
$connection->addColumn($this->getTable('sagepaysuite_transaction'), 'server_session', 'TEXT null');

$installer->endSetup();