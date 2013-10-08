/*
Navicat MySQL Data Transfer

Source Server         : 10.0.2.19
Source Server Version : 50160
Source Host           : 10.0.2.19:3306
Source Database       : toadwords

Target Server Type    : MYSQL
Target Server Version : 50160
File Encoding         : 65001

Date: 2013-10-08 10:22:20
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
  `sync_status` enum('QUEUE','SYNCED','ERROR','RECEIVE') NOT NULL DEFAULT 'RECEIVE' COMMENT '在队列中，已同步，同步出错',
  `adgroup_status` enum('ACTIVE','PAUSE','DELETE') NOT NULL DEFAULT 'ACTIVE',
  PRIMARY KEY (`idclick_groupid`,`idclick_planid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of adgroup
-- ----------------------------
INSERT INTO adgroup VALUES ('12313', '43141', '0', null, 'group_name3', 'keywords3,keywords2', '301.00', 'CREATE', 'RECEIVE', 'ACTIVE');
INSERT INTO adgroup VALUES ('79640', '51549', '0', '1', 'group_name', 'Array', '200.00', 'CREATE', 'QUEUE', 'ACTIVE');
INSERT INTO adgroup VALUES ('79641', '51549', '0', '1', 'group_name', 'keywords1,keywords2', '200.00', 'CREATE', 'QUEUE', 'ACTIVE');
INSERT INTO adgroup VALUES ('79642', '51549', '0', '1', 'group_name2', 'keywords1,keywords2', '400.00', 'UPDATE', 'QUEUE', 'ACTIVE');
INSERT INTO adgroup VALUES ('123456', '51549', '0', '1', 'group_name2', 'keywords1,keywords2', '202.00', 'DELETE', 'QUEUE', 'ACTIVE');

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
  `sync_status` enum('QUEUE','SYNCED','ERROR','RECEIVE') NOT NULL DEFAULT 'RECEIVE' COMMENT '在队列中，已同步，同步出错',
  `ad_status` enum('ACTIVE','PAUSE','DELETE') NOT NULL DEFAULT 'ACTIVE',
  PRIMARY KEY (`idclick_adid`,`idclick_groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of adgroupad
-- ----------------------------
INSERT INTO adgroupad VALUES ('12345', '123456', '0', null, 'headline——l', 'description1....', 'description2....', 'http://www.izptec.com/go1.php', 'http://www.izptec.com/', 'DELETE', 'RECEIVE', 'ACTIVE');
INSERT INTO adgroupad VALUES ('12346', '123456', '0', '1', 'headline', 'description1', 'description2', 'http://www.izptec.com/go.php', 'http://www.izptec.com/', 'CREATE', 'QUEUE', 'ACTIVE');
INSERT INTO adgroupad VALUES ('12347', '123456', '0', '1', 'headline', 'description1', 'description2', 'http://www.izptec.com/go.php', 'http://www.izptec.com/', 'CREATE', 'QUEUE', 'ACTIVE');
INSERT INTO adgroupad VALUES ('12348', '123456', '0', '1', 'headline', 'description1', 'description2', 'http://www.izptec.com/go.php', 'http://www.izptec.com/', 'CREATE', 'QUEUE', 'ACTIVE');
INSERT INTO adgroupad VALUES ('12349', '123456', '0', '1', 'headline', 'description1', 'description2', 'http://www.izptec.com/go.php', 'http://www.izptec.com/', 'CREATE', 'QUEUE', 'ACTIVE');
INSERT INTO adgroupad VALUES ('12350', '123456', '0', '1', 'headline', 'description1', 'description2', 'http://www.izptec.com/go.php', 'http://www.izptec.com/', 'CREATE', 'QUEUE', 'ACTIVE');
INSERT INTO adgroupad VALUES ('12351', '123456', '0', '1', 'headline', 'description1', 'description2', 'http://www.izptec.com/go.php', 'http://www.izptec.com/', 'CREATE', 'QUEUE', 'ACTIVE');
INSERT INTO adgroupad VALUES ('12352', '123456', '0', '1', 'headline', 'description1', 'description2', 'http://www.izptec.com/go.php', 'http://www.izptec.com/', 'CREATE', 'QUEUE', 'ACTIVE');
INSERT INTO adgroupad VALUES ('12353', '123456', '0', '1', 'headline', 'description1', 'description2', 'http://www.izptec.com/go.php', 'http://www.izptec.com/', 'CREATE', 'QUEUE', 'ACTIVE');
INSERT INTO adgroupad VALUES ('12354', '123456', '0', '1', 'headline', 'description1', 'description2', 'http://www.izptec.com/go.php', 'http://www.izptec.com/', 'CREATE', 'QUEUE', 'ACTIVE');

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

-- ----------------------------
-- Table structure for `customer`
-- ----------------------------
DROP TABLE IF EXISTS `customer`;
CREATE TABLE `customer` (
  `idclick_uid` int(10) NOT NULL,
  `adwords_customerid` bigint(10) DEFAULT NULL,
  `last_action` enum('CREATE','UPDATE','DELETE') NOT NULL DEFAULT 'CREATE',
  `sync_status` enum('QUEUE','SYNCED','ERROR','RECEIVE') NOT NULL DEFAULT 'RECEIVE' COMMENT '在队列中，已同步，同步出错',
  PRIMARY KEY (`idclick_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of customer
-- ----------------------------
