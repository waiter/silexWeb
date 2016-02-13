/*
 Navicat Premium Data Transfer

 Source Server         : aliyun
 Source Server Type    : MySQL
 Source Server Version : 50173
 Source Host           : qdm213439568.my3w.com
 Source Database       : qdm213439568_db

 Target Server Type    : MySQL
 Target Server Version : 50173
 File Encoding         : utf-8

 Date: 02/06/2016 23:16:28 PM
*/

SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `CloudAtlas`
-- ----------------------------
DROP TABLE IF EXISTS `CloudAtlas`;
CREATE TABLE `CloudAtlas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package` varchar(200) NOT NULL,
  `desc` varchar(1000) NOT NULL,
  `imageHorizontal` varchar(500) NOT NULL,
  `imageVertical` varchar(500) NOT NULL,
  `appName` varchar(100) NOT NULL,
  `icon` varchar(500) NOT NULL,
  `type` varchar(30) NOT NULL,
  `priority` int(11) NOT NULL DEFAULT '0',
  `download` varchar(500) NOT NULL,
  `channel` varchar(30) NOT NULL,
  `updated` int(11) NOT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Records of `CloudAtlas`
-- ----------------------------
BEGIN;
INSERT INTO `CloudAtlas` VALUES ('1', 'com.monkeystudio.spacewar', '空间战争啊，就是空间战争', 'http://game.xiaomi.com/hy/image/2048.jpg?v=1.1', 'http://game.xiaomi.com/hy/image/2048.jpg?v=1.1', '空间战争', 'http://file.market.xiaomi.com/thumbnail/jpeg/w136/AppStore/0c1aff53fb6bd46811f377a02c648a0c2e5c7f24d', '策略', '1', 'https://play.google.com/store/apps/details?id=com.monkeystudio.spacewar', 'googlepaly', '1454737074', '1454737074'), ('2', 'dadsa', '', '', '', 'dahdua ', 'http://localhost:8084/upload/icon_1454752991.', 'dad', '0', '', 'xiaomi', '1454752991', '1454752991');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
