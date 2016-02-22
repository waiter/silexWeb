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

 Date: 02/18/2016 00:23:06 AM
*/

SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `ConfigGame`
-- ----------------------------
DROP TABLE IF EXISTS `ConfigGame`;
CREATE TABLE `ConfigGame` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package` varchar(200) NOT NULL,
  `appName` varchar(100) NOT NULL,
  `desc` varchar(1000) NOT NULL,
  `updated` int(11) NOT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;

SET FOREIGN_KEY_CHECKS = 1;
