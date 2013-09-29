/*
Navicat MySQL Data Transfer

Source Server         : Local
Source Server Version : 50612
Source Host           : localhost:3306
Source Database       : toadwords

Target Server Type    : MYSQL
Target Server Version : 50612
File Encoding         : 65001

Date: 2013-09-29 18:30:08
*/

SET FOREIGN_KEY_CHECKS=0;
-- ----------------------------
-- Table structure for `adgroup`
-- ----------------------------
DROP TABLE IF EXISTS `adgroup`;
CREATE TABLE `adgroup` (
  `idclick_groupid` int(10) NOT NULL,
  `idclick_uid` int(10) NOT NULL,
  `idclick_planid` int(10) NOT NULL,
  `adgroup_name` varchar(128) NOT NULL,
  `adgroup_id` bigint(10) DEFAULT '0',
  `campaign_id` bigint(10) DEFAULT NULL,
  `cutomer_id` bigint(10) DEFAULT NULL,
  `keywords` varchar(200) NOT NULL,
  `budget_amount` decimal(10,2) NOT NULL COMMENT '广告组出价，以天为单位，adwords需要大于等于1',
  `last_action` enum('CREATE','UPDATE','DELETE') NOT NULL,
  `sync_status` enum('QUEUE','SYNCED','ERROR','RECEIVE') NOT NULL DEFAULT 'RECEIVE' COMMENT '在队列中，已同步，同步出错',
  `adgroup_status` enum('ACTIVE','PAUSE','DELETE') NOT NULL DEFAULT 'ACTIVE',
  PRIMARY KEY (`idclick_groupid`,`idclick_uid`,`idclick_planid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of adgroup
-- ----------------------------
INSERT INTO adgroup VALUES ('12313', '443', '43141', 'group_name3', '0', null, null, 'keywords3,keywords2', '301.00', 'CREATE', 'RECEIVE', 'ACTIVE');
INSERT INTO adgroup VALUES ('123456', '441', '12345', 'group_name2', '0', '6516584', null, 'keywords3,keywords2,keywords1', '201.00', 'DELETE', 'RECEIVE', 'DELETE');

-- ----------------------------
-- Table structure for `adgroupad`
-- ----------------------------
DROP TABLE IF EXISTS `adgroupad`;
CREATE TABLE `adgroupad` (
  `idclick_adid` int(10) NOT NULL,
  `idclick_uid` int(10) NOT NULL,
  `idclick_groupid` int(10) NOT NULL,
  `ad_id` bigint(10) DEFAULT '0',
  `customer_id` bigint(10) DEFAULT NULL,
  `adgroup_id` bigint(10) DEFAULT NULL,
  `ad_headline` varchar(128) NOT NULL,
  `ad_description1` varchar(128) NOT NULL,
  `ad_description2` varchar(128) NOT NULL,
  `ad_url` varchar(200) NOT NULL,
  `ad_displayurl` varchar(200) NOT NULL,
  `last_action` enum('CREATE','UPDATE','DELETE') NOT NULL,
  `sync_status` enum('QUEUE','SYNCED','ERROR','RECEIVE') NOT NULL DEFAULT 'RECEIVE' COMMENT '在队列中，已同步，同步出错',
  `ad_status` enum('ACTIVE','PAUSE','DELETE') NOT NULL DEFAULT 'ACTIVE',
  PRIMARY KEY (`idclick_adid`,`idclick_uid`,`idclick_groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of adgroupad
-- ----------------------------
INSERT INTO adgroupad VALUES ('12345', '441', '123456', '0', null, null, 'headline——l', 'description1....', 'description2....', 'http://www.izptec.com/go1.php', 'http://www.izptec.com/', 'DELETE', 'RECEIVE', 'ACTIVE');

-- ----------------------------
-- Table structure for `campaign`
-- ----------------------------
DROP TABLE IF EXISTS `campaign`;
CREATE TABLE `campaign` (
  `idclick_planid` int(10) NOT NULL,
  `idclick_uid` int(10) NOT NULL,
  `campaign_id` bigint(10) DEFAULT NULL COMMENT 'Google广告系列ID',
  `customer_id` bigint(10) DEFAULT NULL,
  `campaign_name` varchar(128) NOT NULL COMMENT '用户ID',
  `languages` varchar(200) NOT NULL COMMENT '投放网页语言',
  `areas` varchar(200) NOT NULL COMMENT 'geo target, 投放地域',
  `bidding_type` smallint(4) NOT NULL,
  `budget_amount` decimal(10,2) NOT NULL COMMENT '广告系列出价，以天为单位，adwords需要大于等于1',
  `delivery_method` enum('ACCELERATED','STANDARD') NOT NULL DEFAULT 'ACCELERATED' COMMENT 'CampaignService.Budget#period',
  `max_cpc` decimal(10,2) NOT NULL,
  `campaign_status` enum('ACTIVE','PAUSE','DELETE') NOT NULL DEFAULT 'ACTIVE',
  `last_action` enum('CREATE','UPDATE','DELETE') NOT NULL DEFAULT 'CREATE',
  `sync_status` enum('QUEUE','SYNCED','ERROR','RECEIVE') NOT NULL DEFAULT 'RECEIVE' COMMENT '在队列中，已同步，同步出错',
  PRIMARY KEY (`idclick_planid`,`idclick_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of campaign
-- ----------------------------
INSERT INTO campaign VALUES ('12357', '519', '0', '1', 'campaign_name #5247e4ffd9a45', '10031,10032', '10031,10032', '1', '20000.00', 'ACCELERATED', '10.00', 'ACTIVE', 'CREATE', 'QUEUE');
INSERT INTO campaign VALUES ('15477', '520', '0', '1', 'campaign_name #5247e53a0e075', '10031,10032', '10031,10032', '1', '20000.00', 'ACCELERATED', '10.00', 'ACTIVE', 'CREATE', 'QUEUE');
INSERT INTO campaign VALUES ('51487', '521', '0', '1', 'campaign_name #5247e5a83da2d', '10031,10032', '10031,10032', '1', '20000.00', 'ACCELERATED', '10.00', 'ACTIVE', 'CREATE', 'QUEUE');
INSERT INTO campaign VALUES ('51549', '522', '0', '1', 'campaign_name #5247e5e66b88d', '10031,10032', '10031,10032', '1', '20000.00', 'ACCELERATED', '10.00', 'ACTIVE', 'CREATE', 'QUEUE');
INSERT INTO campaign VALUES ('51550', '522', '0', '1', 'campaign_name #5247e5f109255', '10031,10032', '10031,10032', '1', '20000.00', 'ACCELERATED', '10.00', 'ACTIVE', 'CREATE', 'QUEUE');
INSERT INTO campaign VALUES ('51551', '522', '0', '1', 'campaign_name #5247e5f6eea35', '10031,10032', '10031,10032', '1', '20000.00', 'ACCELERATED', '10.00', 'ACTIVE', 'CREATE', 'QUEUE');
INSERT INTO campaign VALUES ('51552', '522', '0', '1', 'campaign_name #5247e5fb0116d', '10031,10032', '10031,10032', '1', '20000.00', 'ACCELERATED', '10.00', 'ACTIVE', 'CREATE', 'QUEUE');
INSERT INTO campaign VALUES ('51553', '522', '0', '1', 'campaign_name #5247e5fedbd6d', '10031,10032', '10031,10032', '1', '20000.00', 'ACCELERATED', '10.00', 'ACTIVE', 'CREATE', 'QUEUE');
INSERT INTO campaign VALUES ('51554', '523', '0', '1', 'campaign_name #5247e605e9ffd', '10031,10032', '10031,10032', '1', '20000.00', 'ACCELERATED', '10.00', 'ACTIVE', 'CREATE', 'QUEUE');
INSERT INTO campaign VALUES ('51555', '523', '0', '1', 'campaign_name #5247e60a0f015', '10031,10032', '10031,10032', '1', '20000.00', 'ACCELERATED', '10.00', 'ACTIVE', 'CREATE', 'QUEUE');

-- ----------------------------
-- Table structure for `customer`
-- ----------------------------
DROP TABLE IF EXISTS `customer`;
CREATE TABLE `customer` (
  `idclick_uid` int(10) NOT NULL,
  `adwords_customerid` bigint(10) DEFAULT NULL,
  `last_action` enum('CREATE','UPDATE','DELETE') NOT NULL,
  `sync_status` enum('QUEUE','SYNCED','ERROR','RECEIVE') NOT NULL DEFAULT 'RECEIVE' COMMENT '在队列中，已同步，同步出错',
  `incomplete_action_count` smallint(4) NOT NULL DEFAULT '0',
  `inqueue_action_count` smallint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`idclick_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of customer
-- ----------------------------
INSERT INTO customer VALUES ('441', '5572928024', 'CREATE', 'SYNCED', '0', '0');
INSERT INTO customer VALUES ('442', '1235154143', 'CREATE', 'RECEIVE', '0', '0');
INSERT INTO customer VALUES ('443', '1235154143', 'CREATE', 'SYNCED', '0', '0');
INSERT INTO customer VALUES ('445', '123', 'CREATE', 'RECEIVE', '0', '0');
INSERT INTO customer VALUES ('449', '0', 'CREATE', 'QUEUE', '0', '0');
INSERT INTO customer VALUES ('480', null, 'CREATE', 'QUEUE', '0', '0');
INSERT INTO customer VALUES ('481', null, 'CREATE', 'RECEIVE', '0', '0');
INSERT INTO customer VALUES ('482', null, 'CREATE', 'QUEUE', '0', '0');
INSERT INTO customer VALUES ('512', null, 'CREATE', 'QUEUE', '0', '0');
INSERT INTO customer VALUES ('516', null, 'CREATE', 'QUEUE', '0', '0');
INSERT INTO customer VALUES ('517', null, 'CREATE', 'QUEUE', '0', '0');
INSERT INTO customer VALUES ('518', null, 'CREATE', 'QUEUE', '0', '0');
INSERT INTO customer VALUES ('519', null, 'CREATE', 'QUEUE', '0', '0');
INSERT INTO customer VALUES ('520', null, 'CREATE', 'QUEUE', '0', '0');
INSERT INTO customer VALUES ('521', null, 'CREATE', 'QUEUE', '0', '0');
INSERT INTO customer VALUES ('522', null, 'CREATE', 'QUEUE', '0', '0');
INSERT INTO customer VALUES ('523', null, 'CREATE', 'QUEUE', '0', '0');
