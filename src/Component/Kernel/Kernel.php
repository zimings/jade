<?php


namespace Zimings\Jade\Component\Kernel;


use Zimings\Jade\Component\Http\Request;
use Zimings\Jade\Component\Http\Response;
use Zimings\Jade\Component\Kernel\Config\Exception\ConfigLoadException;
use Zimings\Jade\Component\Kernel\Config\JsonParser;
use Zimings\Jade\Component\Kernel\Config\ConfigLoader;
use Zimings\Jade\Component\Kernel\Controller\ControllerResolver;
use Zimings\Jade\Component\Logger\Logger;
use Zimings\Jade\Component\Router\Exception\NoMatcherException;
use Zimings\Jade\Component\Router\Matcher\MatchByRegexPath;
use Zimings\Jade\Component\Router\RouteContainer;
use Zimings\Jade\Component\Router\Router;
use Zimings\Jade\Foundation\Path\Exception\PathException;
use Zimings\Jade\Foundation\Path\Path;
use Zimings\Jade\Foundation\Path\PathInterface;

abstract class Kernel
{
    /**
     * @var ConfigLoader
     */
    private $configLoader;

    /**
     * 获取缓存目录
     * @return PathInterface
     * @throws PathException
     */
    public function getCacheDir(): PathInterface
    {
        return $this->createPath($this->getRootDir() . "/var/cache");
    }

    /**
     * 获取日志目录
     * @return PathInterface
     * @throws PathException
     */
    public function getLogDir(): PathInterface
    {
        return $this->createPath($this->getRootDir() . "/var/log");
    }

    /**
     * 获取项目根目录
     * @return PathInterface
     * @throws PathException
     */
    public function getRootDir(): PathInterface
    {
        return $this->createPath(dirname(__DIR__));
    }

    /**
     * @param string $path
     * @return PathInterface
     * @throws PathException
     */
    public function createPath(string $path = ''): PathInterface
    {
        try {
            return new Path($path);
        } catch (PathException $e) {
            throw $e;
        }
    }

    /**
     * @param Request $request
     * @return Response
     * @throws PathException
     * @throws ConfigLoadException
     * @throws NoMatcherException
     */
    public function handle(Request $request): Response
    {
        $request->headers->set('X-Php-Ob-Level', ob_get_level());
        $logger = new Logger();
        try {
            $logger->setName('ControllerResolver')->setOutput($this->getLogDir());
            $controllerResolver = new ControllerResolver($logger);
        } catch (PathException $e) {
            throw $e;
        }
        $logger->setName('Router')->setOutput($this->getLogDir());

        $router = new Router();
        $matcher = new MatchByRegexPath();
        $router->setRequest($request)
            ->setLogger($logger)
            ->setRouteContainer($this->getRouteContainer())
            ->setMatcher($matcher);

        $config = $this->getConfigLoader()->setName('response')->loadFromFile();
        //如果加载成功则向Router中传递
        if ($config !== null) {
            $config->add(['root_dir' => $this->getRootDir()]);
            $router->setConfig($config);
        }

        if ($router->matchAll()) {
            $request = $router->getRequest();
            $controller = $controllerResolver->getController($request);
            //调用
            $response = call_user_func_array($controller, $request->request->all());
            if ($response instanceof Response) {
                return $response;
            }
        }
        //响应错误信息
        $reason = $router->getReason();
        $response = new Response($reason->getContent(), $reason->getHttpStatus());
        return $response;
    }

    /**
     * @return RouteContainer
     * @throws PathException
     */
    private function getRouteContainer(): RouteContainer
    {
        $routes = $this->getConfigLoader()
            ->setName('routes')
            ->setParser(new JsonParser())
            ->loadFromFile()
            ->all();
        return RouteContainer::createByArray($routes);
    }

    /**
     * @return ConfigLoader
     * @throws PathException
     */
    public function getConfigLoader(): ConfigLoader
    {
        if ($this->configLoader === null) {
            $this->configLoader = new ConfigLoader();
            $path = $this->createPath($this->getRootDir()->after($this->createPath('/app/config')));
            $this->configLoader->setPath($path)->setParser(new JsonParser());
        }
        return $this->configLoader;
    }
}