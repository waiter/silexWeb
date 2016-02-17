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

 Date: 02/18/2016 00:23:21 AM
*/

SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `RankList`
-- ----------------------------
DROP TABLE IF EXISTS `RankList`;
CREATE TABLE `RankList` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gameId` int(11) NOT NULL COMMENT '所属游戏',
  `name` varchar(100) NOT NULL,
  `desc` varchar(1000) NOT NULL,
  `key` varchar(100) NOT NULL,
  `type` tinyint(4) NOT NULL COMMENT '0: all, 1: day, 2: week, 3: mouth',
  `order` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1: 正序, 0: 逆序',
  `length` int(11) NOT NULL,
  `min` int(11) NOT NULL DEFAULT '0',
  `max` int(11) NOT NULL DEFAULT '-1',
  `unique` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1：唯一，0：不唯一',
  `pre` varchar(10) NOT NULL COMMENT '前缀',
  `num` int(11) NOT NULL,
  `check` int(11) NOT NULL COMMENT 'a*sin(x) + x/a',
  `updated` int(11) NOT NULL DEFAULT '0',
  `created` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

SET FOREIGN_KEY_CHECKS = 1;
