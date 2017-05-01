<?php

use Illuminate\View\FileViewFinder;
use Illuminate\Filesystem\Filesystem as Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container as Container;
use Illuminate\View\Factory;
use Illuminate\View\Engines\EngineResolver;


class Template
{
    protected $templatesDir;
    protected $cacheDir;
    protected $factory;

    public function __construct($templatesDir, $cacheDir = null, $templateExtensions = [])
    {
        if (is_string($templatesDir)) {
            $templatesDir = [$templatesDir];
        } elseif (!is_array($templatesDir)) {
            throw new InvalidArgumentException('$templatesDir argument should be an array or string');
        }
        foreach ($templatesDir as $dir) {
            if (!is_dir($dir)) {
                throw new Exception("Templates directory $dir not found");
            }
        }
        if (is_null($cacheDir)) {
            $cacheDir = DATADIR . '/templates_cache';
        } else {
            if (!is_dir($cacheDir)) {
                throw new Exception("Templates cache directory $cacheDir not found");
            }
        }
        $finder = new FileViewFinder(new Filesystem, $templatesDir);
        $dispatcher = new Dispatcher(new Container);
        $resolver = function() use($cacheDir) {
            $compiler = new BladeCompiler(new Filesystem, $cacheDir);
            $engine = new CompilerEngine($compiler);
            return $engine;
        };
        $engineResolver = new EngineResolver;
        $engineResolver->register('blade', $resolver);
        $this->factory = new Factory($engineResolver, $finder, $dispatcher);
        foreach ($templateExtensions as $extension) {
            $this->factory->addExtension($extension, 'blade');
        }
    }

    public function render($templateName, $signs = [])
    {
        $view = $this->factory->make($templateName, $signs);
        $result = $view->render();
        return $result;
    }

    public function display($templateName, $signs = []) {
        if (!outputClean()) {
            throw new Exception('Failed to display template: headers already sent');
        }
        $html = $this->render($templateName, $signs);
        stop($html);
    }

    public function getTemplatesDir() {
        return $this->templatesDir;
    }

    public function getCacheDir() {
        return $this->cacheDir;
    }
}
