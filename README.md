这个项目用来练习Google Adwords Api的，由于Api调用比较耗时，使用了httpsqs消息队列发起请求。

- 测试样例可直接使用，也可另写脚本直接调用Adapter。

- 这个项目还在开发中，部分功能缺失或存在较大BUG。

###How To Use
1. 使用toadwords.sql创建MySQL数据库；

2. 配置src/ToAdwords/bootstrap.inc.php；
3. 编写外部脚本，调用Adapter；

	require_once('src/ToAdwords/bootstrap.inc.php');

	use ToAdwords\CampaignAdapter;

