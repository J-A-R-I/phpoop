<?php
declare(strict_types=1);

namespace Admin\Core;

class Router
{
    private array $routes = [];
    private $notFoundHandler = null;

    public function setNotFoundHandler(callable $handler): void
    {
        $this->notFoundHandler = $handler;
    }

    public function get(string $path, callable $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    private function addRoute(string $method, string $path, callable $handler): void
    {
        $pattern = $this->compilePattern($path);

        $this->routes[$method][] = [
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $uri, string $method): void
    {
        $uri = $this->normalize($uri);

        if (!isset($this->routes[$method])) {
            $this->notFound($uri);
            return;
        }

        foreach ($this->routes[$method] as $route) {
            if (preg_match($route['pattern'], $uri, $matches)) {
                array_shift($matches);
                call_user_func_array($route['handler'], $matches);
                return;
            }
        }

        $this->notFound($uri);
    }

    private function compilePattern(string $path): string
    {
        $path = $this->normalize($path);

        // AANGEPAST: {parameter} mag nu ALLES zijn behalve een slash
        // Hierdoor worden "post-5" en "nieuws-artikel" geaccepteerd.
        $pattern = preg_replace('/\{(\w+)\}/', '([^/]+)', $path);

        return '#^' . $pattern . '$#';
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '//' ? '/' : $path;
    }

    private function notFound(string $requestedUri): void
    {
        if (is_callable($this->notFoundHandler)) {
            call_user_func($this->notFoundHandler, $requestedUri);
            return;
        }

        http_response_code(404);
        echo '<h1>404 - Pagina niet gevonden</h1>';
    }
}