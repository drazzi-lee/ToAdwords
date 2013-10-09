/*
Navicat MySQL Data Transfer

Source Server         : Local
Source Server Version : 50612
Source Host           : localhost:3306
Source Database       : toadwords

Target Server Type    : MYSQL
Target Server Version : 50612
File Encoding         : 65001

Date: 2013-10-09 18:35:23
*/

SET FOREIGN_KEY_CHECKS=0;
-- ----------------------------
-- Table structure for `adgroup`
-- ----------------------------
DROP TABLE IF EXISTS `adgroup`;
CREATE TABLE `adgroup` (
  `idclick_groupid` int(10) NOT NULL,
  `idclick_planid` int(10) NOT NULL,
  `adgroup_id` bigint(10) DEFAULT NULL,
  `campaign_id` bigint(10) DEFAULT NULL,
  `adgroup_name` varchar(128) NOT NULL,
  `keywords` varchar(200) NOT NULL,
  `budget_amount` decimal(10,2) NOT NULL COMMENT '广告组出价，以天为单位，adwords需要大于等于1',
  `last_action` enum('CREATE','UPDATE','DELETE') NOT NULL DEFAULT 'CREATE',
  `adgroup_status` enum('ACTIVE','PAUSE','DELETE') NOT NULL DEFAULT 'ACTIVE',
  `sync_status` enum('QUEUE','SYNCED','ERROR','RETRY','RECEIVE') NOT NULL DEFAULT 'RECEIVE' COMMENT '在队列中，已同步，同步出错',
  PRIMARY KEY (`idclick_groupid`,`idclick_planid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of adgroup
-- ----------------------------

-- ----------------------------
-- Table structure for `adgroupad`
-- ----------------------------
DROP TABLE IF EXISTS `adgroupad`;
CREATE TABLE `adgroupad` (
  `idclick_adid` int(10) NOT NULL,
  `idclick_groupid` int(10) NOT NULL,
  `ad_id` bigint(10) DEFAULT NULL,
  `adgroup_id` bigint(10) DEFAULT NULL,
  `ad_headline` varchar(128) NOT NULL,
  `ad_description1` varchar(128) NOT NULL,
  `ad_description2` varchar(128) DEFAULT NULL,
  `ad_url` varchar(200) NOT NULL,
  `ad_displayurl` varchar(200) NOT NULL,
  `last_action` enum('CREATE','UPDATE','DELETE') NOT NULL DEFAULT 'CREATE',
  `sync_status` enum('QUEUE','SYNCED','ERROR','RETRY','RECEIVE') NOT NULL DEFAULT 'RECEIVE' COMMENT '在队列中，已同步，同步出错',
  `ad_status` enum('ACTIVE','PAUSE','DELETE') NOT NULL DEFAULT 'PAUSE',
  PRIMARY KEY (`idclick_adid`,`idclick_groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of adgroupad
-- ----------------------------

-- ----------------------------
-- Table structure for `campaign`
-- ----------------------------
DROP TABLE IF EXISTS `campaign`;
CREATE TABLE `campaign` (
  `idclick_planid` int(10) NOT NULL,
  `idclick_uid` int(10) NOT NULL,
  `campaign_id` bigint(10) DEFAULT NULL COMMENT 'Google广告系列ID',
  `customer_id` bigint(10) DEFAULT NULL COMMENT 'ADWORDS帐户ID',
  `campaign_name` varchar(128) NOT NULL COMMENT '用户ID',
  `languages` varchar(200) NOT NULL COMMENT '投放网页语言',
  `areas` varchar(200) NOT NULL COMMENT 'geo target, 投放地域',
  `bidding_type` enum('BUDGET_OPTIMIZER','MANUAL_CPC') NOT NULL DEFAULT 'BUDGET_OPTIMIZER',
  `budget_amount` decimal(10,2) NOT NULL COMMENT '广告系列出价，以天为单位，adwords需要大于等于1',
  `delivery_method` enum('ACCELERATED','STANDARD') NOT NULL DEFAULT 'ACCELERATED' COMMENT 'CampaignService.Budget#period',
  `max_cpc` decimal(10,2) NOT NULL,
  `campaign_status` enum('ACTIVE','PAUSE','DELETE') NOT NULL DEFAULT 'ACTIVE',
  `last_action` enum('CREATE','UPDATE','DELETE') NOT NULL DEFAULT 'CREATE',
  `sync_status` enum('QUEUE','SYNCED','ERROR','RETRY','RECEIVE') NOT NULL DEFAULT 'RECEIVE' COMMENT '在队列中，已同步，同步出错',
  PRIMARY KEY (`idclick_planid`,`idclick_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of campaign
-- ----------------------------
INSERT INTO campaign VALUES ('16', '979', null, '5436784026', 'campaign_name #5255065dd46a4', '1001,1002', '2156', '', '500.00', 'ACCELERATED', '10.00', 'ACTIVE', 'UPDATE', 'QUEUE');

-- ----------------------------
-- Table structure for `customer`
-- ----------------------------
DROP TABLE IF EXISTS `customer`;
CREATE TABLE `customer` (
  `idclick_uid` int(10) NOT NULL,
  `adwords_customerid` bigint(10) DEFAULT NULL,
  `last_action` enum('CREATE','UPDATE','DELETE') NOT NULL DEFAULT 'CREATE',
  `sync_status` enum('QUEUE','SYNCED','ERROR','RETRY','RECEIVE') NOT NULL DEFAULT 'RECEIVE' COMMENT '在队列中，已同步，同步出错',
  PRIMARY KEY (`idclick_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of customer
-- ----------------------------
INSERT INTO customer VALUES ('978', '5436784026', 'CREATE', 'SYNCED');
