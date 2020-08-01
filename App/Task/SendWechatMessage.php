<?php


namespace App\Task;



use App\Message\KafkaMessage;
use App\WeChat\WeChatManager;
use EasySwoole\Component\Csp;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\Db\MysqliClient;
use EasySwoole\ORM\DbManager;
use EasySwoole\Task\AbstractInterface\TaskInterface;
use EasySwoole\WeChat\Bean\OfficialAccount\TemplateMsg;
use EasySwoole\WeChat\WeChat;

class SendWechatMessage implements TaskInterface
{
    protected $kafkaMessage;

    public function __construct(KafkaMessage $kafkaMessage)
    {
        $this->kafkaMessage = $kafkaMessage;
    }

    function run(int $taskId, int $workerIndex)
    {
        $wechat = $this->getWeCat();
        /**
         * 这里就是实际的业务处理流，我们可以自由的在这里使用各类API来最终推送消息
         */
        $templateMsg = $this->buildTemplate($this->kafkaMessage->getTemplateId(), $this->kafkaMessage->getContent());
        $templateMsg = $this->setToUser($templateMsg, $this->kafkaMessage->getUserId());

        $wechat->officialAccount()->templateMsg()->send($templateMsg);
    }

    protected function sendAll(WeChat $wechat, TemplateMsg $templateMsg)
    {
        /**
         * 群发时可以从其他地方读取要发送的openId list
         * 考虑到可能列表很长，可以使用循环获取的方式来读取
         * $i 可以作为周期的标识，来确定当前的读取位置
         */
        for ($i=0;;$i++) {
            /**
             * 使用CSP 来控制群发时的并发量
             * 下面的意思是 并发5 注意这个值是指当前执行的并发量
             * 实际的并发量和你投递的task 速率或 消费者进程数有关
             * 请务必注意不要太大，防止微信把你ban了
             */
            $csp = new Csp(5);
            $openIdList = $this->getPushList($i);
            /**
             * list 为空时跳出循环
             */
            if (empty($openIdList)) {
                break;
            }
            foreach ($openIdList as $openId) {
                $tmpTemplateMsg = clone $templateMsg;
                $tmpTemplateMsg->setTouser($openId);
                $csp->add($openId, function () use ($wechat, $tmpTemplateMsg) {
                    $wechat->officialAccount()->templateMsg()->send($tmpTemplateMsg);
                });
            }
            $csp->exec(30);
        }
    }

    protected function getPushList(int $cycle): array
    {
        /**
         * 在实际业务中，这里可以从DB获取，$cycle 可以作为周期点
         * 最简单的就是当做页号了
         */
        if ($cycle > 0) {
            return [];
        }
        return [$this->kafkaMessage->getUserId()];
    }

    protected function buildTemplate($templateId, $message):TemplateMsg
    {
        /**
         * 在这里将业务层的 $templateId 转化为微信层的 $templateId
         * 可以使用Db 或redis 等等方式
         * 为了方便演示，这里我默认使用微信层的$templateId
         */
        $templateId = $this->getTemplateId($templateId);
        $templateMsg = new TemplateMsg();
        $templateMsg->setTemplateId($templateId);
        $templateMsg->setData([
            "content" => $message
        ]);
        return $templateMsg;
    }

    protected function setToUser(TemplateMsg $templateMsg, $userId) : TemplateMsg
    {
        /**
         * 同理这里也一样可以通过Db 或Redis 来获取wechat的openId
         * 这里我为了方便演示 直接在生产者使用了openid
         */
        $templateMsg->setTouser($userId);
        return $templateMsg;
    }

    protected function getWeCat(): WeChat
    {
        /**
         * 在这里你既可以使用 WeChatManager 预注册的WeChat对象
         * 也可以参考 @var \App\HttpController\WeChatEvent 中的 dynamic function 动态获取
         * 为了简单描述我这里使用 default 进行返回
         * 用户可以使用AppId 来区分你想要的使用的weChat对象
         * AppId 在生产者产生，可以使用业务自定义的 也可以使用微信的
         */
        $appId = $this->kafkaMessage->getAppId();
        return WeChatManager::getInstance()->weChat('default');
    }

    protected function getTemplateId($templateId)
    {
        /**
         * 这里提供一个使用DB的演示
         * 实际业务中可以创建Model 来处理
         * @see https://www.easyswoole.com/Cn/Components/Orm/base.html
         */
//        $templateId = DbManager::getInstance()->invoke(function (MysqliClient $client) use ($templateId){
//            $query = new QueryBuilder();
//            $query->where('templateId', $templateId)->getOne('template_list');
//            $data = $client->query($query);
//            return $data['wechatTemplateId'];
//        });
        return $templateId;
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        /**
         * 这里转交日志服务处理
         */
        echo $throwable;
    }
}