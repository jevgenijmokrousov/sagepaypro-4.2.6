<?php

$installer  = $this;
$connection = $installer->getConnection();

$installer->startSetup();

$connection->addColumn($this->getTable('sagepaysuite_tokencard'), 'nickname', 'varchar(30) null');
$connection->addColumn($this->getTable('sagepaysuite_transaction'), 'nickname', 'varchar(30) null');

$installer->endSetup();