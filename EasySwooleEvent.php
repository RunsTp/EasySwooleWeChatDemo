<?php

namespace EasySwoole\EasySwoole;


use App\WeChat\WeChatManager;
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
        $weChatConfig->officialAccount()->setAppId('you appId');
        $weChatConfig->officialAccount()->setAppSecret('you appSecret');
        $weChatConfig->officialAccount()->setToken('you token');
        $weChatConfig->officialAccount()->setAesKey('you AesKey');

        // 也可以使用这个方案
        $configArray = [
            'appId'     => 'you appId',
            'appSecret' => 'you appSecret',
            'token'     => 'you token',
            'AesKey'    => 'you AesKey',
        ];
        $weChatConfig->officialAccount($configArray);

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