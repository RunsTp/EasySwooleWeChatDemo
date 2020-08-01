<?php


namespace App\Process;

use App\Message\KafkaMessage;
use App\Task\SendWechatMessage;
use EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\EasySwoole\Task\TaskManager;
use EasySwoole\Kafka\Config\ConsumerConfig;
use EasySwoole\Kafka\kafka;

class Consumer extends AbstractProcess
{
    private $id;

    protected function run($arg)
    {
        /**
         * 如果使用多Process 方式 可以这样注册自己的ID
         */
        $this->id = $arg['id'] ?? 0;

        go(function () {
            $config = new ConsumerConfig();
            $config->setRefreshIntervalMs(1000);
            $config->setMetadataBrokerList('127.0.0.1:9092');
            $config->setBrokerVersion('0.9.0');
            $config->setGroupId('test');

            $config->setTopics(['test']);
            $config->setOffsetReset('earliest');

            $kafka = new kafka($config);
            // 设置消费回调
            $func = function ($topic, $partition, $message) {
                /**
                 * 这里业务层可以对 $topic $partition $message 进行处理
                 * 这里不做演示 仅对$message 中的message value 重新转化为Message Class
                 */
                $kafkaMessage = KafkaMessage::create($message['message']['value']);

                $task = new SendWechatMessage($kafkaMessage);
                /**
                 * 我们可以选择使用Task进程来执行实际的消费工作
                 * 亦可以将业务代码直接在这里展开，通过增加 Consumer Process 数量提高消费能力
                 * 这里仅做一个演示
                 */
                TaskManager::getInstance()->async($task);

                /**
                 * 如果你使用多Process的方式 可以这样来直接展开
                 * 这里其实并不是很完善，只是为了强行演示，实际上要对 $topic, $partition都有好的处理
                 * 防止 onException 后无法追踪
                 * 上面和下面选择一种方式不可以都使用
                 */
//                try {
//                    $task->run($message['offset'], $this->id);
//                }catch (\Throwable $throwable) {
//                    $task->onException($throwable, $message['offset'], $this->id);
//                }
            };
            $kafka->consumer()->subscribe($func);
        });
    }

    protected function onException(\Throwable $throwable, ...$args)
    {
        print_r($throwable);
    }
}