<?php


namespace App\HttpController;

use EasySwoole\Http\AbstractInterface\AbstractRouter;
use FastRoute\RouteCollector;

class Router extends AbstractRouter
{
    function initialize(RouteCollector $routeCollector)
    {
        $routeCollector->get('/weChat', '/WeChatEvent/onOfficialAccountGet');
        $routeCollector->post('/weChat', '/WeChatEvent/onOfficialAccountPost');
    }
}