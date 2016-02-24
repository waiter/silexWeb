/*
 Navicat Premium Data Transfer

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 50709
 Source Host           : localhost
 Source Database       : app

 Target Server Type    : MySQL
 Target Server Version : 50709
 File Encoding         : utf-8

 Date: 02/20/2016 22:17:38 PM
*/

SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `ConfigData`
-- ----------------------------
DROP TABLE IF EXISTS `ConfigData`;
CREATE TABLE `ConfigData` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package` varchar(200) NOT NULL,
  `key` varchar(200) NOT NULL,
  `value` varchar(500) NOT NULL,
  `desc` varchar(200) NOT NULL,
  `updated` int(11) NOT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

SET FOREIGN_KEY_CHECKS = 1;
