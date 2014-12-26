-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

-- -----------------------------------------------------
-- Schema simstats_master
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Schema simstats_master
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `simstats_master` DEFAULT CHARACTER SET utf8 ;
USE `simstats_master` ;

-- -----------------------------------------------------
-- Table `simstats_master`.`shards`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `simstats_master`.`shards` ;

CREATE TABLE IF NOT EXISTS `simstats_master`.`shards` (
  `id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `name_UNIQUE` (`name` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `simstats_master`.`users`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `simstats_master`.`users` ;

CREATE TABLE IF NOT EXISTS `simstats_master`.`users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `shardId` TINYINT UNSIGNED NOT NULL,
  `uuid` VARCHAR(36) NOT NULL,
  `name` VARCHAR(63) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_users_shards1_idx` (`shardId` ASC),
  CONSTRAINT `fk_users_shards1`
    FOREIGN KEY (`shardId`)
    REFERENCES `simstats_master`.`shards` (`id`)
    ON DELETE CASCADE
    ON UPDATE RESTRICT)
ENGINE = InnoDB
AUTO_INCREMENT = 141
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `simstats_master`.`servers`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `simstats_master`.`servers` ;

CREATE TABLE IF NOT EXISTS `simstats_master`.`servers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `shardId` TINYINT UNSIGNED NOT NULL,
  `ownerId` INT UNSIGNED NOT NULL,
  `uuid` VARCHAR(36) NOT NULL,
  `objectId` VARCHAR(36) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `address` VARCHAR(255) NOT NULL,
  `enabled` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `name_UNIQUE` (`name` ASC),
  UNIQUE INDEX `address_UNIQUE` (`address` ASC),
  INDEX `fk_shardId_idx` (`shardId` ASC),
  INDEX `fk_ownerId_idx` (`ownerId` ASC),
  UNIQUE INDEX `uuid_UNIQUE` (`uuid` ASC),
  UNIQUE INDEX `objectId_UNIQUE` (`objectId` ASC),
  CONSTRAINT `fk_shardId`
    FOREIGN KEY (`shardId`)
    REFERENCES `simstats_master`.`shards` (`id`)
    ON DELETE CASCADE
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_ownerId`
    FOREIGN KEY (`ownerId`)
    REFERENCES `simstats_master`.`users` (`id`)
    ON DELETE CASCADE
    ON UPDATE RESTRICT)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `simstats_master`.`stats`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `simstats_master`.`stats` ;

CREATE TABLE IF NOT EXISTS `simstats_master`.`stats` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `serverId` INT UNSIGNED NOT NULL,
  `time` INT(10) UNSIGNED NOT NULL,
  `agentCount` TINYINT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_serverId_idx` (`serverId` ASC),
  CONSTRAINT `fk_serverId`
    FOREIGN KEY (`serverId`)
    REFERENCES `simstats_master`.`servers` (`id`)
    ON DELETE CASCADE
    ON UPDATE RESTRICT)
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
