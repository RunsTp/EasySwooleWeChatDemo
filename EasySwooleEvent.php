<?php

namespace EasySwoole\EasySwoole;


use App\Process\Consumer;
use App\Process\Producer;
use App\Wechat\NetworkReleases;
use App\WeChat\WeChatManager;
use EasySwoole\Component\Process\Manager;
use EasySwoole\Component\Timer;
use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\EasySwoole\AbstractInterface\Event;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EasySwoole\WeChat\Bean\OfficialAccount\Message\RequestedReplyMsg;
use EasySwoole\WeChat\Bean\OfficialAccount\Message\Text;
use EasySwoole\WeChat\Bean\OfficialAccount\RequestConst;
use EasySwoole\WeChat\Bean\OfficialAccount\RequestMsg;
use EasySwoole\WeChat\WeChat;

class EasySwooleEvent implements Event
{

    public static function initialize()
    {
        // TODO: Implement initialize() method.
        date_default_timezone_set('Asia/Shanghai');
    }

    public static function mainServerCreate(EventRegister $register)
    {
        $weChatConfig = new \EasySwoole\WeChat\Config();
        $weChatConfig->setTempDir(Config::getInstance()->getConf('TEMP_DIR'));

        // 可以使用这种方案
        $weChatConfig->officialAccount()->setAppId('you AppId');
        $weChatConfig->officialAccount()->setAppSecret('you AppSecret');
        $weChatConfig->officialAccount()->setToken('you token');
        $weChatConfig->officialAccount()->setAesKey('you AesKey');

        // 也可以使用这个方案
        $configArray = [
            'appId'     => 'you AppId',
            'appSecret' => 'you AppSecret',
            'token'     => 'you token',
            'AesKey'    => 'you AesKey',
        ];
//        $weChatConfig->officialAccount($configArray);

        // 开放平台注册
        $configArray = [
            'componentAppId'     => 'you componentAppId',
            'componentAppSecret' => 'you componentAppSecret',
            'token'     => 'you token',
            'aesKey'    => 'you aesKey',
        ];
        $weChatConfig->openPlatform($configArray);

        // 注册WeChat对象到全局List
        WeChatManager::getInstance()->register('default', new WeChat($weChatConfig));

        // 获取名为 default WeChat officialAccount Server 对象 用来注册消息事件
        $server = WeChatManager::getInstance()->weChat('default')->officialAccount()->server();

        // 注册收到文本消息事件
        $server->onMessage()->set(RequestConst::MSG_TYPE_TEXT, function (RequestMsg $requestMsg) {
            /** @var string 获取用户发来的文本消息内容 $content */
            $content = $requestMsg->getContent();

            /**
             * @var RequestedReplyMsg
             * 这里代表回复一条文本消息，事实上你可以回复 RequestedReplyMsg 子类
             */
            $reply = new Text();
            $reply->setContent("你发送的消息为： $content .");

            // 这里如果返回的是 null 则不会给用户响应任何内容 即微信的默认 "success"
            return $reply;
        });

        /**
         * 获取名为 default WeChat openPlatform 对象 用来注册全网发布事件
         */
        $openPlatform = WeChatManager::getInstance()->weChat('default')->openPlatform();
        /**
         * 注册全网发布事件
         */
        NetworkReleases::register($openPlatform);

        $register->add($register::onWorkerStart, function ($server, $workId) {
            /**
             * 单独使用一个work 进程来刷新wechat AccessToken
             */
            if ($workId === 0) {
                $accessToken = WeChatManager::getInstance()->weChat('default')->officialAccount()->accessToken();
                /**
                 * 如果 Token失效则立刻刷新
                 */
                if (empty($accessToken->getToken())) {
                    $accessToken->refresh();
                }
                /**
                 * 每 7180秒刷新一次
                 */
                Timer::getInstance()->loop(7180* 1000, function () use ($accessToken) {
                    $accessToken->refresh();
                });
            }
        });

        /**
         * 注册一个生产者 模拟生产数据
         */
        Manager::getInstance()->addProcess(new Producer());

        /**
         * 注册 消费者 模式处理数据
         * 这里可以通过增加注册的消费者进程数量来提高消费能力
         * 也可以通过在消费者进程内投递Task任务来提高
         * 从可靠性的角度来讲，建议使用Process
         * 但如果你业务量并不大，Task也是一个不错的方案
         */
        for ($i=0; $i<1; $i++) {
            Manager::getInstance()->addProcess(new Consumer(['id' => $i]));
        }

    }

    public static function onRequest(Request $request, Response $response): bool
    {
        // TODO: Implement onRequest() method.
        return true;
    }

    public static function afterRequest(Request $request, Response $response): void
    {
        // TODO: Implement afterAction() method.
    }
}