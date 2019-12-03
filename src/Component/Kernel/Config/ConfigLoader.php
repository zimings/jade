<?php


namespace Zimings\Jade\Component\Kernel\Config;


use Zimings\Jade\Component\Kernel\Config\Exception\ConfigLoadException;
use Zimings\Jade\Foundation\Path\PathInterface;

class ConfigLoader
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var PathInterface
     */
    private $path;

    /**
     * @var ParserInterface
     */
    private $parser;

    /**
     * @var Config
     */
    private $config;

    public function __construct(string $name = '', PathInterface $path = null, ParserInterface $parser = null)
    {
        $this->name = $name;
        $this->path = $path;
        $this->parser = $parser;
    }

    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    public function setPath(PathInterface $path)
    {
        $this->path = $path;
        return $this;
    }

    public function setParser(ParserInterface $parser)
    {
        $this->parser = $parser;
        return $this;
    }

    /**
     * @return bool
     * @throws ConfigLoadException
     */
    public function prepare(): bool
    {
        if ($this->name === '') {
            throw new ConfigLoadException('是否忘记设置name属性？');
        }
        if (!($this->parser instanceof ParserInterface)) {
            throw new ConfigLoadException('parser属性必须instanceof ParserInterface');
        }
        if (!($this->path instanceof PathInterface)) {
            throw new ConfigLoadException('path属性必须instanceof PathInterface');
        }
        return true;
    }

    /**
     * @return Config
     */
    public function loadFromFile(): Config
    {
        $this->parser->setName($this->name)
            ->setPath($this->path);
        if ($this->parser->fileExists()) {
            $this->config = $this->parser->loadAsConfig();
        }
        return $this->config;
    }
}