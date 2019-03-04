<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSEn
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Fal\Stick\Web;

use Fal\Stick\Container\Container;
use Fal\Stick\Container\ContainerInterface;
use Fal\Stick\Container\Definition;
use Fal\Stick\Logging\LogLevel;
use Fal\Stick\Util;
use Fal\Stick\Web\Event\FilterControllerArgumentsEvent;
use Fal\Stick\Web\Event\FilterControllerEvent;
use Fal\Stick\Web\Event\FilterResponseEvent;
use Fal\Stick\Web\Event\FinishRequestEvent;
use Fal\Stick\Web\Event\GetRequestEvent;
use Fal\Stick\Web\Event\GetResponseEvent;
use Fal\Stick\Web\Event\GetResponseForControllerResultEvent;
use Fal\Stick\Web\Event\GetResponseForExceptionEvent;
use Fal\Stick\Web\Exception\BadControllerException;
use Fal\Stick\Web\Exception\BadControllerResultException;
use Fal\Stick\Web\Exception\ForbiddenException;
use Fal\Stick\Web\Exception\HttpException;
use Fal\Stick\Web\Exception\NotFoundException;

/**
 * Stick main application class.
 *
 * It holds environment and container.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Kernel implements KernelInterface
{
    const PACKAGE = 'Stick Framework';
    const VERSION = 'v0.1.0-alpha';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var string
     */
    protected $environment;

    /**
     * @var bool
     */
    protected $debug;

    /**
     * Create kernel instance.
     *
     * @param string     $environment
     * @param bool       $debug
     * @param array|null $parameters
     *
     * @return Kernel
     */
    public static function create(string $environment = 'prod', bool $debug = false, array $parameters = null): Kernel
    {
        return new static($environment, $debug, $parameters);
    }

    /**
     * Class constructor.
     *
     * @param string     $environment
     * @param bool       $debug
     * @param array|null $parameters
     */
    public function __construct(string $environment = 'prod', bool $debug = false, array $parameters = null)
    {
        $this->environment = $environment;
        $this->debug = $debug;
        $this->container = $this->initializeContainer($parameters ?? array());
    }

    /**
     * {@inheritdoc}
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * {@inheritdoc}
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * {@inheritdoc}
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, int $requestType = self::MASTER_REQUEST): Response
    {
        try {
            try {
                return $this->handleRaw($request, $requestType);
            } catch (\Throwable $exception) {
                return $this->handleException($exception, $request, $requestType);
            }
        } catch (\Throwable $exception) {
            return $this->handleException($exception, $request, $requestType);
        }
    }

    /**
     * Auto-handle.
     *
     * @return Response
     */
    public function run(): Response
    {
        $response = $this->handle($request = $this->createRequest());

        if ($this->container->getParameter('auto_prepare')) {
            $response->prepare($request);
        }

        if (!$this->container->getParameter('quiet')) {
            $response->send();
        }

        return $response;
    }

    /**
     * Load configuration to container.
     *
     * @param string $file
     * @param bool   $parse
     *
     * @return Kernel
     */
    public function config(string $file, bool $parse = false): Kernel
    {
        $configPattern = '/(?<=^|\n)(?:'.
            '\[(?<section>.+?)\]|'.
            '(?<lval>[^\h\r\n;].*?)\h*=\h*'.
            '(?<rval>(?:\\\\\h*\r?\n|.+?)*)'.
        ')(?=\r?\n|$)/';

        if (!is_file($file) || !preg_match_all($configPattern, file_get_contents($file), $matches, PREG_SET_ORDER)) {
            return $this;
        }

        $sections = 'config|route|controller|rest|event|service';
        $sectionPattern = "/^(?!(?:global|$sections)s\b)((?:\.?\w)+)/i";
        $commandPattern = "/^($sections)s\b(?:\.(.+))?/i";
        $section = 'globals';
        $command = array();
        $previous = null;
        $sectionContent = array();

        for ($i = 0, $last = count($matches) - 1; $i <= $last; ++$i) {
            $match = $matches[$i];

            if ($match['section']) {
                $section = $match['section'];

                if (preg_match($sectionPattern, $section, $subSections) && !$this->container->hasParameter($subSections[0])) {
                    $this->container->setParameter($subSections[0], null);
                }

                if (!preg_match($commandPattern, $section, $command)) {
                    continue;
                }

                $command['call'] = $call = strtolower($command[1]);

                if ('controller' === $call || ('service' === $call && isset($command[2]))) {
                    if (empty($command[2])) {
                        throw new \LogicException(sprintf('%s need first parameter.', $command[1]));
                    }

                    $pair = array();

                    for ($j = $i + 1; $j <= $last; ++$j) {
                        if ($matches[$j]['section']) {
                            break;
                        }

                        $row = $this->configArguments($matches[$j]['lval'], $matches[$j]['rval'], $parse);
                        $ref = &$this->container->reference(array_shift($row), true, $found, $pair);
                        $ref = 1 === count($row) ? reset($row) : $row;
                        $i = $j;
                    }

                    $this->configExecute($call, array($command[2], $pair));
                }

                continue;
            }

            if ($command) {
                $this->configExecute($command['call'], $this->configArguments($match['lval'], $match['rval'], $parse));

                continue;
            }

            if ($parse) {
                $lval = $this->configParse($match['lval']);
                $rval = $this->configParse($match['rval']);
            } else {
                $lval = $match['lval'];
                $rval = $match['rval'];
            }

            // Mark quoted strings with 0x00 whitespace
            $key = $lval;
            $rval = preg_replace('/\\\\\h*(\r?\n)/', '\1', $rval);
            $value = array_map(function ($val) {
                $val = Util::cast($val);

                if (is_string($val)) {
                    $val = $val ? preg_replace('/\\\\"/', '"', $val) : null;
                }

                return $val;
            }, str_getcsv(preg_replace('/(?<!\\\\)(")(.*?)\1/', "\\1\x00\\2\\1", trim($rval))));

            if ('globals' !== strtolower($section)) {
                $key = $section.'.'.$key;
            }

            $this->container->setParameter($key, count($value) > 1 ? $value : reset($value));
        }

        return $this;
    }

    /**
     * Initialize container.
     *
     * @param array $parameters
     */
    protected function initializeContainer(array $parameters): ContainerInterface
    {
        return new Container(array(
            'kernel' => new Definition('Fal\\Stick\\Web\\KernelInterface', $this),
            'session' => new Definition('Fal\\Stick\\Web\\Session\\SessionInterface', 'Fal\\Stick\\Web\\Session\\Session'),
            'router' => new Definition('Fal\\Stick\\Web\\Router\\RouterInterface', 'Fal\\Stick\\Web\\Router\\Router'),
            'requestStack' => new Definition('Fal\\Stick\\Web\\RequestStackInterface', 'Fal\\Stick\\Web\\RequestStack'),
            'urlGenerator' => new Definition('Fal\\Stick\\Web\\UrlGeneratorInterface', 'Fal\\Stick\\Web\\UrlGenerator'),
            'auth' => new Definition('Fal\\Stick\\Web\\Security\\Auth', array(
                'arguments' => array(
                    'encoder' => '%auth_encoder%',
                    'options' => '%auth_options%',
                ),
            )),
            'logger' => new Definition('Fal\\Stick\\Logging\\LoggerInterface', array(
                'use' => 'Fal\\Stick\\Logging\\Logger',
                'arguments' => array(
                    'directory' => '%log_directory%',
                    'logLevelThreshold' => '%log_threshold%',
                ),
            )),
            'eventDispatcher' => new Definition('Fal\\Stick\\EventDispatcher\\EventDispatcherInterface', 'Fal\\Stick\\EventDispatcher\\EventDispatcher'),
            'translator' => new Definition('Fal\\Stick\\Translation\\TranslatorInterface', array(
                'use' => 'Fal\\Stick\\Translation\\Translator',
                'arguments' => array(
                    'locales' => '%translator_locales%',
                    'language' => '%translator_language%',
                    'fallback' => '%translator_fallback%',
                ),
            )),
            'template' => new Definition('Fal\\Stick\\Template\\TemplateInterface', array(
                'use' => 'Fal\\Stick\\Template\\Template',
                'arguments' => array(
                    'directories' => '%template_directories%',
                    'extension' => '%template_extension%',
                ),
            )),
            'validator' => new Definition('Fal\\Stick\\Validation\\Validator', array(
                'arguments' => array(
                    'rules' => '%validator_rules%',
                ),
            )),
        ), $parameters + array(
            'quiet' => false,
            'auto_prepare' => true,
            'auth_options' => null,
            'auth_encoder' => '%Fal\\Stick\\Web\\Security\\BcryptPasswordEncoder%',
            'log_directory' => null,
            'log_threshold' => null,
            'translator_locales' => null,
            'translator_language' => null,
            'translator_fallback' => 'en',
            'template_directories' => null,
            'template_extension' => '.php',
            'validator_rules' => null,
        ));
    }

    /**
     * Handle request.
     *
     * @param Request $request
     * @param int     $requestType
     *
     * @return Response
     */
    protected function handleRaw(Request $request, int $requestType): Response
    {
        $this->container
            ->set('currentRequest', new Definition('Fal\\Stick\\Web\\Request', $request))
            ->get('requestStack')
                ->push($request);

        $dispatcher = $this->container->get('eventDispatcher');

        $event = new GetResponseEvent($this, $request, $requestType);
        $dispatcher->dispatch(self::ON_REQUEST, $event);

        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$match = $this->container->get('router')->handle($request)->getRouteMatch()) {
            throw new NotFoundException();
        }

        if (!$controller = $match->getController()) {
            throw new ForbiddenException();
        }

        if (is_string($controller)) {
            $controller = $this->container->grab($controller);
        }

        if (!is_callable($controller)) {
            throw new BadControllerException(sprintf('Unable to call route controller: %s.', Util::stringify($controller)));
        }

        $event = new FilterControllerEvent($this, $request, $requestType, $controller);
        $dispatcher->dispatch(self::ON_CONTROLLER, $event);

        $controller = $event->getController();
        $arguments = $match->getArguments();

        $event = new FilterControllerArgumentsEvent($this, $request, $requestType, $controller, $arguments);
        $dispatcher->dispatch(self::ON_CONTROLLER_ARGUMENTS, $event);

        $arguments = $event->getArguments();

        $response = $this->container->call($controller, $arguments);

        if (!$response instanceof Response) {
            $event = new GetResponseForControllerResultEvent($this, $request, $requestType, $response);
            $dispatcher->dispatch(self::ON_VIEW, $event);

            if (!$event->hasResponse()) {
                throw new BadControllerResultException(sprintf('Controller should returns Fal\\Stick\\Web\\Response object, given %s of %s.', gettype($response), Util::stringify($response)));
            }

            $response = $event->getResponse();
        }

        return $this->filterResponse($response, $request, $requestType);
    }

    /**
     * Handle exception.
     *
     * @param Throwable $exception
     * @param Request   $request
     * @param int       $requestType
     *
     * @return Response
     */
    protected function handleException(\Throwable $exception, Request $request, int $requestType): Response
    {
        if (!$message = $exception->getMessage()) {
            $message = $request->getMethod().' '.$request->getPath();
        }

        $this->container->get('logger')->log(LogLevel::ERROR, $message);

        $event = new GetResponseForExceptionEvent($this, $request, $requestType, $exception);
        $this->container->get('eventDispatcher')->dispatch(self::ON_EXCEPTION, $event, true);

        $response = $event->getResponse() ?? $this->createExceptionResponse($exception, $request);

        return $this->filterResponse($response, $request, $requestType);
    }

    /**
     * Filter response.
     *
     * @param Response $response
     * @param Request  $request
     * @param int      $requestType
     *
     * @return Response
     */
    protected function filterResponse(Response $response, Request $request, int $requestType): Response
    {
        $event = new FilterResponseEvent($this, $request, $requestType, $response);
        $this->container->get('eventDispatcher')->dispatch(self::ON_RESPONSE, $event);

        $this->finishRequest($request, $requestType);

        return $event->getResponse();
    }

    /**
     * Finish request.
     *
     * @param Request $request
     * @param int     $requestType
     */
    protected function finishRequest(Request $request, int $requestType): void
    {
        $event = new FinishRequestEvent($this, $request, $requestType);
        $this->container->get('eventDispatcher')->dispatch(self::ON_FINISH_REQUEST, $event);
    }

    /**
     * Create request.
     *
     * @return Request
     */
    protected function createRequest(): Request
    {
        $event = new GetRequestEvent($this);
        $this->container->get('eventDispatcher')->dispatch(self::ON_PREPARE, $event);

        return $event->getRequest() ?? Request::createFromGlobals();
    }

    /**
     * Create exception response.
     *
     * @param Throwable $exception
     * @param Request   $request
     *
     * @return Response
     */
    protected function createExceptionResponse(\Throwable $exception, Request $request): Response
    {
        $code = $exception instanceof HttpException ? $exception->getStatusCode() : 500;
        $response = new Response(null, $code);
        $status = $response->getStatusText();
        $message = $exception->getMessage() ?: sprintf('%s %s (%s %s)', $request->getMethod(), $request->getPath(), $code, $status);
        $trace = $this->debug ? Util::trace($exception->getTrace()) : null;

        if ($request->isAjax()) {
            $data = compact('code', 'status', 'message', 'trace');

            return new JsonResponse($data, $code);
        }

        return $response->setContent(sprintf('<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <title>%1$s %2$s</title>
  </head>
  <body>
    <h1>%1$s - %2$s</h1>
    <p>%3$s</p>
    <pre>%4$s</pre>
  </body>
</html>', $code, $status, $message, $trace));
    }

    /**
     * Parse text, replace token if exists in hive.
     *
     * @param string $text
     *
     * @return string
     */
    protected function configParse(string $text): string
    {
        return preg_replace_callback('/\$\{\h*([^\h]+)\h*\}/', function ($match) {
            return $this->container->getParameter($match[1]) ?? $match[0];
        }, $text);
    }

    /**
     * Returns fixed config arguments.
     *
     * @param string $lval
     * @param string $rval
     * @param bool   $parse
     *
     * @return array
     */
    protected function configArguments(string $lval, string $rval, bool $parse): array
    {
        if ($parse) {
            $lval = $this->configParse($lval);
            $rval = $this->configParse($rval);
        }

        return array_merge(array($lval), array_map(array('Fal\\Stick\\Util', 'cast'), str_getcsv($rval)));
    }

    /**
     * Execute config command.
     *
     * @param string $command
     * @param array  $arguments
     */
    protected function configExecute(string $command, array $arguments): void
    {
        if ('config' === $command) {
            $this->config(...$arguments);
        } elseif ('service' === $command) {
            list($class, $definition) = $arguments;

            $this->container->set($class, new Definition($class, $definition));
        } elseif ('event' === $command) {
            $this->container->get('eventDispatcher')->on(...$arguments);
        } elseif (in_array($command, array('route', 'controller', 'rest'))) {
            $this->container->get('router')->$command(...$arguments);
        }
    }
}
