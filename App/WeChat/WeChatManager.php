<?php


namespace App\WeChat;


use EasySwoole\Component\Singleton;
use EasySwoole\WeChat\WeChat;
use RuntimeException;

class WeChatManager
{
    // 单例化 进程内共享
    use Singleton;

    /** @var array 存储全部WeChat对象 */
    private $weChatList = [];

    public function register(string $name, WeChat $weChat): void
    {
        if (!isset($this->weChatList[$name])) {
            $this->weChatList[$name] = $weChat;
        } else {
            throw new RuntimeException('重复注册weChat.');
        }
    }

    public function weChat(string $name): WeChat
    {
        if (isset($this->weChatList[$name])) {
            return $this->weChatList[$name];
        }

        throw new RuntimeException('not found weChat name');
    }
}