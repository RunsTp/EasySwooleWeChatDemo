<?php


namespace App\Message;


use EasySwoole\Spl\SplBean;

class KafkaMessage extends SplBean
{
    protected $appId;
    protected $userId;
    protected $templateId;
    protected $content;

    public static function create(string $json) : self
    {
        return new static(json_decode($json, true));
    }

    /**
     * @return mixed
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * @param mixed $appId
     */
    public function setAppId($appId): void
    {
        $this->appId = $appId;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param mixed $userId
     */
    public function setUserId($userId): void
    {
        $this->userId = $userId;
    }

    /**
     * @return mixed
     */
    public function getTemplateId()
    {
        return $this->templateId;
    }

    /**
     * @param mixed $templateId
     */
    public function setTemplateId($templateId): void
    {
        $this->templateId = $templateId;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param mixed $content
     */
    public function setContent($content): void
    {
        $this->content = $content;
    }

    public function build() :string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }
}