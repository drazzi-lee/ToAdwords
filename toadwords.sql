/*
Navicat MySQL Data Transfer

Source Server         : Local
Source Server Version : 50612
Source Host           : localhost:3306
Source Database       : toadwords

Target Server Type    : MYSQL
Target Server Version : 50612
File Encoding         : 65001

Date: 2013-10-31 15:36:53
*/

SET FOREIGN_KEY_CHECKS=0;
-- ----------------------------
-- Table structure for `adgroup`
-- ----------------------------
DROP TABLE IF EXISTS `adgroup`;
CREATE TABLE `adgroup` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '当前对象objectId，由sync_cache使用。',
  `idclick_groupid` int(10) NOT NULL COMMENT '对象在idclick中的groupid，广告组ID',
  `idclick_planid` int(10) NOT NULL COMMENT '对象在idclick中的父级依赖，广告组ID',
  `adgroup_id` bigint(10) DEFAULT NULL COMMENT '对象在Google Adwords中的AdGroupId.',
  `campaign_id` bigint(10) DEFAULT NULL COMMENT '对象在Google Adwords中的父级依赖，CampaignId',
  `adgroup_name` varchar(128) NOT NULL COMMENT '广告组名称',
  `keywords` varchar(200) NOT NULL COMMENT '广告组关键字',
  `budget_amount` decimal(10,2) NOT NULL COMMENT '广告组预算，以天为单位，adwords需要大于等于1',
  `adgroup_status` enum('ACTIVE','PAUSE','DELETE') NOT NULL DEFAULT 'ACTIVE' COMMENT '广告组状态，默认值为启用',
  `last_action` enum('CREATE','UPDATE','DELETE') NOT NULL DEFAULT 'CREATE' COMMENT '上次操作动作',
  `sync_status` enum('QUEUE','SYNCED','ERROR','RETRY','RECEIVE','SENDING') NOT NULL DEFAULT 'RECEIVE' COMMENT '在队列中，已同步，同步出错，重试中，已收到，发送中',
  PRIMARY KEY (`id`,`idclick_groupid`),
  UNIQUE KEY `idclick_groupid` (`idclick_groupid`),
  UNIQUE KEY `adgroup_id` (`adgroup_id`),
  KEY `idclick_planid` (`idclick_planid`),
  KEY `campaign_id` (`campaign_id`),
  CONSTRAINT `adgroup_ibfk_2` FOREIGN KEY (`campaign_id`) REFERENCES `campaign` (`campaign_id`) ON UPDATE CASCADE,
  CONSTRAINT `adgroup_ibfk_1` FOREIGN KEY (`idclick_planid`) REFERENCES `campaign` (`idclick_planid`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='此表记录广告组当前状态以及与Google Adwords的对象关系及同步情况。';

-- ----------------------------
-- Records of adgroup
-- ----------------------------

-- ----------------------------
-- Table structure for `adgroupad`
-- ----------------------------
DROP TABLE IF EXISTS `adgroupad`;
CREATE TABLE `adgroupad` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '当前对象objectId',
  `idclick_adid` int(10) NOT NULL COMMENT '对象在idclick中的adid，广告ID',
  `idclick_groupid` int(10) NOT NULL COMMENT '对象在idclick中的groupid，广告组ID',
  `ad_id` bigint(10) DEFAULT NULL COMMENT '对象在Google Adwords中的AdGroupAdId',
  `adgroup_id` bigint(10) DEFAULT NULL COMMENT '对象在Google Adwords中的父级依赖，AdGroupId',
  `ad_headline` varchar(128) NOT NULL COMMENT '广告显示标题',
  `ad_description1` varchar(128) NOT NULL COMMENT '广告描述文字1',
  `ad_description2` varchar(128) DEFAULT NULL COMMENT '广告描述文字2',
  `ad_url` varchar(200) NOT NULL COMMENT '广告真实URL',
  `ad_displayurl` varchar(200) NOT NULL COMMENT '广告显示URL',
  `ad_status` enum('ACTIVE','PAUSE','DELETE') NOT NULL DEFAULT 'PAUSE' COMMENT '广告当前状态，默认值为暂停。',
  `last_action` enum('CREATE','UPDATE','DELETE') NOT NULL DEFAULT 'CREATE' COMMENT '上次操作动作',
  `sync_status` enum('QUEUE','SYNCED','ERROR','RETRY','RECEIVE','SENDING') NOT NULL DEFAULT 'RECEIVE' COMMENT '在队列中，已同步，同步出错，重试中，已收到，发送中',
  PRIMARY KEY (`id`,`idclick_adid`),
  KEY `idclick_groupid` (`idclick_groupid`),
  KEY `adgroup_id` (`adgroup_id`),
  CONSTRAINT `adgroupad_ibfk_2` FOREIGN KEY (`adgroup_id`) REFERENCES `adgroup` (`adgroup_id`) ON UPDATE CASCADE,
  CONSTRAINT `adgroupad_ibfk_1` FOREIGN KEY (`idclick_groupid`) REFERENCES `adgroup` (`idclick_groupid`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='此表记录当前广告的信息及与Google Adwords对象的对应关系及同步状态。';

-- ----------------------------
-- Records of adgroupad
-- ----------------------------

-- ----------------------------
-- Table structure for `campaign`
-- ----------------------------
DROP TABLE IF EXISTS `campaign`;
CREATE TABLE `campaign` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idclick_planid` int(10) NOT NULL COMMENT '对象在idclick中的plan_id，广告计划ID。',
  `idclick_uid` int(10) NOT NULL COMMENT '对象在idclick中的uid，用户ID',
  `campaign_id` bigint(10) DEFAULT NULL COMMENT '对象在Google Adwords中的campaignId，广告系列ID',
  `customer_id` bigint(10) DEFAULT NULL COMMENT '对象在Google Adwords中的customerId，帐户ID',
  `campaign_name` varchar(128) NOT NULL COMMENT '对象的名字，广告计划名称',
  `languages` varchar(200) NOT NULL COMMENT '投放网页语言，以ID的形式，以逗号分隔',
  `areas` varchar(200) NOT NULL COMMENT '对象的投放地域信息，ID形式，以逗号分隔',
  `bidding_type` enum('MANUAL_CPC','BUDGET_OPTIMIZER') NOT NULL DEFAULT 'BUDGET_OPTIMIZER' COMMENT '出价策略，默认值为由系统自动出价',
  `budget_amount` decimal(10,2) NOT NULL COMMENT '广告系列预算，以天为单位，adwords需要大于等于1',
  `delivery_method` enum('ACCELERATED','STANDARD') NOT NULL DEFAULT 'ACCELERATED' COMMENT '投放策略，平滑/尽快。CampaignService.Budget#deliveryMethod',
  `max_cpc` decimal(10,2) NOT NULL COMMENT '每次点击费用上限',
  `campaign_status` enum('ACTIVE','PAUSE','DELETE') NOT NULL DEFAULT 'ACTIVE' COMMENT '广告计划状态，默认启用',
  `last_action` enum('CREATE','UPDATE','DELETE') NOT NULL DEFAULT 'CREATE' COMMENT '上次操作动作',
  `sync_status` enum('QUEUE','SYNCED','ERROR','RETRY','RECEIVE','SENDING') NOT NULL DEFAULT 'RECEIVE' COMMENT '在队列中，已同步，同步出错，重试，收到，发送中',
  PRIMARY KEY (`id`,`idclick_planid`),
  UNIQUE KEY `idclick_planid` (`idclick_planid`),
  UNIQUE KEY `campaign_id` (`campaign_id`),
  KEY `campaign_ibfk_1` (`idclick_uid`),
  KEY `campaign_ibfk_2` (`customer_id`),
  CONSTRAINT `campaign_ibfk_1` FOREIGN KEY (`idclick_uid`) REFERENCES `customer` (`idclick_uid`) ON UPDATE CASCADE,
  CONSTRAINT `campaign_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='此表用来记录广告计划模型具体信息及与ADWORDS对应关系、同步状态。';

-- ----------------------------
-- Records of campaign
-- ----------------------------

-- ----------------------------
-- Table structure for `customer`
-- ----------------------------
DROP TABLE IF EXISTS `customer`;
CREATE TABLE `customer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idclick_uid` int(10) NOT NULL COMMENT '对象在idclick中的uid.',
  `customer_id` bigint(10) DEFAULT NULL COMMENT '对象在adwords同的customerId.',
  `last_action` enum('CREATE','UPDATE','DELETE') NOT NULL DEFAULT 'CREATE' COMMENT '上次操作动作',
  `sync_status` enum('QUEUE','SYNCED','ERROR','RETRY','RECEIVE','SENDING') NOT NULL DEFAULT 'RECEIVE' COMMENT '在队列中，已同步，同步出错，重试中，已收到，发送中',
  PRIMARY KEY (`id`,`idclick_uid`),
  UNIQUE KEY `idclick_uid` (`idclick_uid`),
  UNIQUE KEY `adwords_customerid` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='此表用来保存记录当前用户在idclick中的用户状态，以及adwords信息同步情况。';

-- ----------------------------
-- Records of customer
-- ----------------------------

-- ----------------------------
-- Table structure for `sync_cache`
-- ----------------------------
DROP TABLE IF EXISTS `sync_cache`;
CREATE TABLE `sync_cache` (
  `module_name` enum('AdGroupAd','AdGroup','Customer','Campaign') NOT NULL COMMENT '模块名称',
  `object_pid` int(11) NOT NULL COMMENT '模块对应的主键ID，一般为idclickObjectId',
  `last_details` varchar(255) NOT NULL COMMENT '上次进行同步时，当前对象具有的信息，Json格式。',
  PRIMARY KEY (`module_name`,`object_pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='此表用来记录对象上次消息操作器与GOOGLE ADWORDS同步时的信息状态，以便过滤不变的数据，筛选出需要同步的数据。';

-- ----------------------------
-- Records of sync_cache
-- ----------------------------
