<?php


namespace App\HttpController;


use App\WeChat\WeChatManager;
use EasySwoole\EasySwoole\Config;
use EasySwoole\Http\AbstractInterface\Controller;
use EasySwoole\Http\Message\Status;
use EasySwoole\WeChat\Bean\OfficialAccount\AccessCheck;
use EasySwoole\WeChat\WeChat;
use Throwable;

class WeChatEvent extends Controller
{
    public function index()
    {
        $this->actionNotFound(null);
    }

    /**
     * 此 API 主要服务 微信公众平台的初始验证事件
     */
    public function onOfficialAccountGet()
    {
        // 将微信发来的 params 参数创建为 AccessCheck Bean对象
        $accessCheckBean = new AccessCheck($this->request()->getQueryParams());

        // 使用名为 'default' WeChat 对象进行验证
        $weChat = WeChatManager::getInstance()->weChat('default');
        /**
         * 动态创建可以使用这种方案，但是需要你在微信的回调地址哪里区分好每个appId
         * 比如 设置为 /WeChatEvent/onOfficialAccountGet/{appId}
         */
        // $weChat = $this->dynamic($this->request()->getRequestParam('appId'));
        $verify = $weChat->officialAccount()->server()->accessCheck($accessCheckBean);

        // 如果验证为真(来自于微信服务器的请求)
        if ($verify) {
            // 按照微信约定 响应 Code 200 并在 Body 输出计算出的值
            $this->response()->withStatus(Status::CODE_OK);
            $this->response()->write($accessCheckBean->getEchostr());
        }

        // 结束此响应
        $this->response()->end();
    }

    /**
     * 此 API 主要服务 微信公众平台的 消息事件
     */
    public function onOfficialAccountPost()
    {
        // 将微信发来的 params 参数创建为 AccessCheck Bean对象
        $accessCheckBean = new AccessCheck($this->request()->getQueryParams());

        // 使用名为 'default' WeChat 对象进行验证
        $weChat = WeChatManager::getInstance()->weChat('default');
        /**
         * 动态创建可以使用这种方案，但是需要你在微信的回调地址哪里区分好每个appId
         * 比如 设置为 /WeChatEvent/onOfficialAccountPost/{appId}
         */
        // $weChat = $this->dynamic($this->request()->getRequestParam('appId'));
        $verify = $weChat->officialAccount()->server()->accessCheck($accessCheckBean);

        // 验证请求来自于微信服务器
        if (!$verify) {
            // 如果非微信服务器请求 则直接拒绝此次响应
            $this->response()->end();
            return;
        }

        /** @var string 微信发送的Body体 $rawContent */
        $rawContent = $this->request()->getBody();

        // 将请求转发给名为 'default' WeChat接管 会返回 XML string 或者 null
        try {
            $XML = $weChat->officialAccount()->server()->parserRequest($rawContent);
        } catch (Throwable $throwable) {
            // 这里我建议开发者 catch 住异常 无论如何给用户响应友好的提示 防止出现公众号异常的问题
            // TODO: 这里实现一个异常记录 和发送服务器异常通知给开发者的代码
        }

        $this->response()->withStatus(Status::CODE_OK);
        $this->response()->write($XML ?? 'success');
    }

    /**
     * 提供一个动态创建的例子
     * 事实上动态创建的核心就是通过AppID 动态的从db OR file 等存储点动态读取config
     * 在这里只需要动态的创建一个WeChat 对象并return 即可
     * @param string $appId
     * @return WeChat
     */
    private function dynamic(string $appId):WeChat
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
        $weChat = new WeChat($weChatConfig);
        return $weChat;
    }
}