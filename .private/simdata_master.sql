-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

-- -----------------------------------------------------
-- Schema simdata_master
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Table `shard`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `shard` ;

CREATE TABLE IF NOT EXISTS `shard` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `name_UNIQUE` (`name` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `agent`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `agent` ;

CREATE TABLE IF NOT EXISTS `agent` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(63) NOT NULL,
  `uuid` CHAR(36) NOT NULL,
  `shardId` INT(11) UNSIGNED NOT NULL,
  `authToken` BINARY(32) NULL DEFAULT NULL,
  `authTokenDate` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_users_shards1_idx` (`shardId` ASC),
  UNIQUE INDEX `uri_UNIQUE` (`authToken` ASC),
  CONSTRAINT `fk_users_shards1`
    FOREIGN KEY (`shardId`)
    REFERENCES `shard` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE)
ENGINE = InnoDB
AUTO_INCREMENT = 141
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `user`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `user` ;

CREATE TABLE IF NOT EXISTS `user` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(64) NOT NULL,
  `hash` VARCHAR(255) NOT NULL,
  `updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` TIMESTAMP NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `server_type`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `server_type` ;

CREATE TABLE IF NOT EXISTS `server_type` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `name_UNIQUE` (`name` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `region`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `region` ;

CREATE TABLE IF NOT EXISTS `region` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `shardId` INT(11) UNSIGNED NOT NULL,
  `name` VARCHAR(63) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_Region_shard1_idx` (`shardId` ASC),
  CONSTRAINT `fk_Region_shard1`
    FOREIGN KEY (`shardId`)
    REFERENCES `shard` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `server`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `server` ;

CREATE TABLE IF NOT EXISTS `server` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `serverTypeId` INT(11) UNSIGNED NULL DEFAULT NULL,
  `shardId` INT(11) UNSIGNED NOT NULL,
  `regionId` INT(11) UNSIGNED NOT NULL,
  `ownerId` INT(11) UNSIGNED NOT NULL,
  `userId` INT(11) UNSIGNED NULL DEFAULT NULL,
  `address` VARCHAR(255) NOT NULL,
  `authToken` BINARY(32) NOT NULL,
  `publicToken` BINARY(32) NOT NULL,
  `objectKey` CHAR(36) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` TIMESTAMP NULL,
  `positionX` FLOAT NULL,
  `positionY` FLOAT NULL,
  `positionZ` FLOAT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_shardId_idx` (`shardId` ASC),
  INDEX `fk_ownerId_idx` (`ownerId` ASC),
  UNIQUE INDEX `uuid_UNIQUE` (`authToken` ASC),
  UNIQUE INDEX `objectId_UNIQUE` (`objectKey` ASC),
  INDEX `fk_stats_server_user1_idx` (`userId` ASC),
  UNIQUE INDEX `publicToken_UNIQUE` (`publicToken` ASC),
  INDEX `fk_server_server_type1_idx` (`serverTypeId` ASC),
  INDEX `fk_server_region1_idx` (`regionId` ASC),
  CONSTRAINT `fk_shardId`
    FOREIGN KEY (`shardId`)
    REFERENCES `shard` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT `fk_ownerId`
    FOREIGN KEY (`ownerId`)
    REFERENCES `agent` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT `fk_stats_server_user1`
    FOREIGN KEY (`userId`)
    REFERENCES `user` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT `fk_server_server_type1`
    FOREIGN KEY (`serverTypeId`)
    REFERENCES `server_type` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT `fk_server_region1`
    FOREIGN KEY (`regionId`)
    REFERENCES `region` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `population`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `population` ;

CREATE TABLE IF NOT EXISTS `population` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `serverId` INT(11) UNSIGNED NOT NULL,
  `time` INT(10) UNSIGNED NOT NULL,
  `agentCount` TINYINT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_serverId_idx` (`serverId` ASC),
  CONSTRAINT `fk_serverId`
    FOREIGN KEY (`serverId`)
    REFERENCES `server` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `item`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `item` ;

CREATE TABLE IF NOT EXISTS `item` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `vendorId` INT(11) UNSIGNED NOT NULL,
  `objectKey` CHAR(36) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `price` INT(11) NOT NULL,
  `salePrice` INT(11) NULL DEFAULT NULL,
  `enabled` TINYINT(1) NOT NULL DEFAULT '1',
  `copy` TINYINT(1) NOT NULL DEFAULT 1,
  `modify` TINYINT(1) NOT NULL DEFAULT 1,
  `transfer` TINYINT(1) NOT NULL DEFAULT 0,
  `updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `key_UNIQUE` (`objectKey` ASC),
  INDEX `fk_item_vendor1_idx` (`vendorId` ASC),
  CONSTRAINT `fk_item_vendor1`
    FOREIGN KEY (`vendorId`)
    REFERENCES `server` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `transaction`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `transaction` ;

CREATE TABLE IF NOT EXISTS `transaction` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `vendorId` INT(11) UNSIGNED NOT NULL,
  `itemId` INT(11) UNSIGNED NOT NULL,
  `agentId` INT(11) UNSIGNED NOT NULL,
  `price` INT(11) NOT NULL,
  `time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `fk_transaction_item1_idx` (`itemId` ASC),
  INDEX `fk_transaction_agent1_idx` (`agentId` ASC),
  INDEX `fk_transaction_vendor_idx` (`vendorId` ASC),
  CONSTRAINT `fk_transaction_item1`
    FOREIGN KEY (`itemId`)
    REFERENCES `item` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT `fk_transaction_vendor`
    FOREIGN KEY (`vendorId`)
    REFERENCES `server` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT `fk_transaction_agent1`
    FOREIGN KEY (`agentId`)
    REFERENCES `agent` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `unverified_token`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `unverified_token` ;

CREATE TABLE IF NOT EXISTS `unverified_token` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `authToken` BINARY(32) NOT NULL,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `authToken_UNIQUE` (`authToken` ASC))
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

-- -----------------------------------------------------
-- Data for table `server_type`
-- -----------------------------------------------------
START TRANSACTION;
INSERT INTO `server_type` (`id`, `name`) VALUES (NULL, 'Base Server');
INSERT INTO `server_type` (`id`, `name`) VALUES (NULL, 'Population Server');
INSERT INTO `server_type` (`id`, `name`) VALUES (NULL, 'Vendor Server');

COMMIT;

