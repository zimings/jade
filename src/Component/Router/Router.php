<?php


namespace Jade\Component\Router;


use Jade\Component\Http\Request;
use Jade\Component\Router\Exception\MatcherNoneRequestException;
use Jade\Component\Router\Matcher\Matcher;
use Jade\Component\Router\Reason\HostNotAllow;
use Jade\Component\Router\Reason\MethodNotAllow;
use Jade\Component\Router\Reason\ReasonInterface;
use Jade\Component\Router\Reason\NoMatch;
use Psr\Log\LoggerInterface;

class Router
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var RouteContainer
     */
    private $routeContainer;

    /**
     * @var ReasonInterface
     */
    private $reason;

    public function __construct(Request $request = null, RouteContainer $routeContainer = null)
    {
        $this->request = $request;
        $this->routeContainer = $routeContainer;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @param RouteContainer $routeContainer
     * @return Router
     */
    public function setRouteContainer(RouteContainer $routeContainer)
    {
        $this->routeContainer = $routeContainer;
        return $this;
    }

    /**
     * @param Request $request
     * @return Router
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
        return $this;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getReason(): ReasonInterface
    {
        return $this->reason;
    }

    /**
     * @return bool
     * @throws MatcherNoneRequestException
     */
    public function matchAll(): bool
    {
        $matcher = new Matcher();
        $matcher->setRequest($this->request);
        $routes = $this->routeContainer->getRoutes();
        foreach ($routes as $name => $route) {
            if ($this->beforeMatch($route)) {
                $request = $matcher->match($route);
                if ($request) {
                    $this->setRequest($matcher->getRequest());
                    return true;
                }
            } else {
                //非法请求
                return false;
            }
        }
        //未成功匹配
        $this->reason = new NoMatch();
        return false;
    }

    public function beforeMatch(Route $route): bool
    {
        //方法是否允许
        if ($route->getMethods() !== [] && !in_array($this->request->getMethod(), $route->getMethods())) {
            $this->reason = new MethodNotAllow();
            return false;
        }
        //host是否允许 未规定则视为全都允许
        if ($route->getHost() !== '' && $this->request->headers->get('host') !== $route->getHost()) {
            $this->reason = new HostNotAllow();
            return false;
        }
        return true;
    }
}