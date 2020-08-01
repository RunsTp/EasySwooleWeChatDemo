<?php


namespace App\Process;

use App\Message\KafkaMessage;
use EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\Kafka\Config\ProducerConfig;
use EasySwoole\Kafka\kafka;
use Swoole\Coroutine;

class Producer extends AbstractProcess
{
    protected function run($arg)
    {
        go(function () {
            $config = new ProducerConfig();
            $config->setMetadataBrokerList('127.0.0.1:9092');
            $config->setBrokerVersion('0.9.0');
            $config->setRequiredAck(1);

            $kafka = new kafka($config);

            while (true) {
                /**
                 * 这个类仅是假定一个推送数据的封装
                 * 实际上你可以自定义这个类，只要最终可以实现__toString()就可以了
                 */
                $message = new KafkaMessage();
                /**
                 * 下面这个部分中AppId 可以使用户层的 也可以直接使用微信层的
                 * 在消费者层进行业务转化处理即可 具体的数据类型可以自己组织这里仅作为一个参考
                 */
                $message->setAppId(1);
                $message->setTemplateId(1);
                $message->setUserId(1);
                $message->setContent('hello word.');

                $result = $kafka->producer()->send([
                    [
                        'topic' => 'test',
                        /**
                         * value 必须是一个 string 类型
                         * message对象只要能实现 __toString 就可以正确被kafka存储
                         */
                        'value' => $message->build(),
                        'key'   => 'key--',
                    ],
                ]);
                echo "push message \n";
                /**
                 * 每5秒生产一条数据
                 */
                Coroutine::sleep(5);
            }
        });
    }

    protected function onException(\Throwable $throwable, ...$args)
    {
        print_r($throwable);
    }
}