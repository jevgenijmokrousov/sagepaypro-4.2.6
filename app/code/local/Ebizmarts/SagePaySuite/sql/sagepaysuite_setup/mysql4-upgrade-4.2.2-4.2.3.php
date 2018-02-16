<?php

$installer = $this;

$installer->startSetup();
$installer->run("ALTER TABLE `{$this->getTable('sagepaysuite_transaction')}` ADD INDEX `vps_tx_id` (`vps_tx_id`);");
$installer->endSetup();