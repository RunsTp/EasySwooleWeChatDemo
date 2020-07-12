<?php


namespace App\Service\Wechat;


use EasySwoole\WeChat\Bean\OfficialAccount\Message\Text;
use EasySwoole\WeChat\Bean\OfficialAccount\RequestConst;
use EasySwoole\WeChat\Bean\OfficialAccount\RequestMsg;
use EasySwoole\WeChat\OpenPlatform\OpenPlatform;

class NetworkReleases
{

    /**
     * @var string 微信开放平台测试公众号AppId
     */
    private const TestOfficialAccountAppId = 'wx570bc396a51b8ff8';

    /**
     * @var string 微信开放平台测试小程序AppId
     */
    private const TestMiniProgramAppId = 'wxd101a85aa106f53e';

    /**
     * 注册全网发布事件处理
     * @buy https://developers.weixin.qq.com/doc/oplatform/Third-party_Platforms/Post_Application_on_the_Entire_Network/releases_instructions.html
     * @param OpenPlatform $openPlatform
     */
    public static function register(OpenPlatform $openPlatform)
    {
        $openPlatform->officialAccount(self::TestOfficialAccountAppId)
            ->server()->onMessage()->set(RequestConst::MSG_TYPE_TEXT, function (RequestMsg $request) use ($openPlatform){
                /**
                 * 模拟粉丝发送文本消息给专用测试公众号，第三方平台方需根据文本消息的内容进行相应的响应：
                 *  1 微信模推送给第三方平台方：文本消息，其中 Content 字段的内容固定为：TESTCOMPONENT_MSG_TYPE_TEXT
                 *  2 第三方平台方立马回应文本消息并最终触达粉丝：Content 必须固定为：TESTCOMPONENT_MSG_TYPE_TEXT_callback
                 */
                if ($request->getContent() === 'TESTCOMPONENT_MSG_TYPE_TEXT') {
                    $text = new Text();
                    $text->setContent('TESTCOMPONENT_MSG_TYPE_TEXT_callback');
                    return $text;
                }

                /**
                 * 测试公众号使用客服消息接口处理用户消息
                 * 1 模拟粉丝发送文本消息给专用测试公众号，第三方平台方需在 5 秒内返回空串表明暂时不回复，然后再立即使用客服消息接口发送消息回复粉丝
                 * 2 微信模推送给第三方平台方：文本消息，其中 Content 字段的内容固定为： QUERY_AUTH_CODE:$query_auth_code$（query_auth_code 会在专用测试公众号自动授权给第三方平台方时，由微信后台推送给开发者）
                 * 3 第三方平台方拿到 $query_auth_code$ 的值后，通过接口文档页中的使用授权码获取授权信息接口，将 $query_auth_code$ 的值赋值给接口所需的参数 authorization_code。然后，调用发送客服消息接口 回复文本消息给粉丝，其中文本消息的 content 字段设为：$query_auth_code$_from_api（其中 $query_auth_code$ 需要替换成推送过来的 $query_auth_code$ 的值）
                 */
                if (strpos($request->getContent(), 'QUERY_AUTH_CODE:', 0) === 0) {
                    $query_auth_code = substr($request->getContent(), mb_strlen('QUERY_AUTH_CODE:'));
                    /**
                     * 这里注册一个defer func 代表稍后回复
                     */
                    defer(function () use ($openPlatform, $request, $query_auth_code) {
                        /**
                         * 构建客服回复消息
                         */
                        $text = new \EasySwoole\WeChat\Bean\OfficialAccount\ServiceMessage\Text();
                        $text->setContent("{$query_auth_code}_from_api");
                        $text->setTouser($request->getFromUserName());

                        /**
                         * 使用 $query_auth_code 获取授权信息
                         */
                        $authorizerInfo = $openPlatform->handleAuthorize($query_auth_code);

                        /**
                         * 主动调用授权公众号API 需要携带 RefreshToken
                         */
                        $openPlatform->officialAccount($authorizerInfo->getAuthorizerAppid(), $authorizerInfo->getAuthorizerRefreshToken())
                            ->service()->sendServiceMsg($text);
                    });
                }
                return null;
            });


        /**
         * TODO: 实现小程序的客服消息
         */
        // $openPlatform->miniProgram(self::TestMiniProgramAppId);
    }
}