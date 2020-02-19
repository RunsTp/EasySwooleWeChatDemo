<?php


namespace App\HttpController;


use App\WeChat\WeChatManager;
use EasySwoole\Http\AbstractInterface\Controller;
use EasySwoole\Http\Message\Status;
use EasySwoole\WeChat\Bean\OfficialAccount\AccessCheck;
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
        $accessCheckBean = new AccessCheck($this->request()->getCookieParams());

        // 使用名为 'default' WeChat 对象进行验证
        $verify = WeChatManager::getInstance()->weChat('default')->officialAccount()->server()->accessCheck($accessCheckBean);

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
        $accessCheckBean = new AccessCheck($this->request()->getCookieParams());

        // 使用名为 'default' WeChat 对象进行验证
        $verify = WeChatManager::getInstance()->weChat('default')->officialAccount()->server()->accessCheck($accessCheckBean);

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
            $XML = WeChatManager::getInstance()->weChat('default')->officialAccount()->server()->parserRequest($rawContent);
        } catch (Throwable $throwable) {
            // 这里我建议开发者 catch 住异常 无论如何给用户响应友好的提示 防止出现公众号异常的问题
            // TODO: 这里实现一个异常记录 和发送服务器异常通知给开发者的代码
        }

        $this->response()->withStatus(Status::CODE_OK);
        $this->response()->write($XML ?? 'success');
    }
}