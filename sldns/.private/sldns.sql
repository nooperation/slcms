-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

-- -----------------------------------------------------
-- Schema secondlife_dns
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Schema secondlife_dns
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `secondlife_dns` DEFAULT CHARACTER SET utf8 ;
USE `secondlife_dns` ;

-- -----------------------------------------------------
-- Table `secondlife_dns`.`dns`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `secondlife_dns`.`dns` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(32) NOT NULL,
  `hash` BINARY(64) NOT NULL,
  `salt` BINARY(32) NOT NULL,
  `address` VARCHAR(255) NULL DEFAULT NULL,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `index_password` (`hash` ASC),
  UNIQUE INDEX `name_UNIQUE` (`name` ASC))
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
