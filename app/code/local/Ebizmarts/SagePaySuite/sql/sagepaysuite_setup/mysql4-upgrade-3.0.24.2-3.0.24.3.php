<?php

$installer = $this;

$installer->startSetup();

$installer->run(
    "
	ALTER TABLE `{$this->getTable('sagepaysuite_transaction')}` ADD INDEX `IDX_SAGEPAYSUITE_TRANSACTION_CREATED_AT` (`created_at`);
"
);
$installer->endSetup();