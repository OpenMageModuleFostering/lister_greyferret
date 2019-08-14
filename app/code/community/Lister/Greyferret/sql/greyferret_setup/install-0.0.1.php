<?php
$installer=$this;
$installer->startSetup();
$installer->run("
-- DROP TABLE IF EXISTS {$this->getTable('greyferret_api')};
CREATE TABLE {$this->getTable('greyferret_api')} (
  `greyId` INT NOT NULL AUTO_INCREMENT,
  `entityLastId` VARCHAR(45) NULL ,
  `entityType` VARCHAR(10) NULL ,
  `entityId` VARCHAR(10872) NULL ,
  `entityStatus` VARCHAR(45) NULL ,
  PRIMARY KEY (`greyId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 ");
$installer->endSetup();
?>
