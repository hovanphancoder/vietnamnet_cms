<?php
namespace System\Core;

class Middleware {

    protected $Middleware = [];
    protected $current = 0;

    /**
     * Add middleware to stack
     *
     * @param callable|string $middleware Middleware name or callback
     */
    public function add($middleware) {
        $this->Middleware[] = $middleware;
    }

    /**
     * Execute middleware
     *
     * @param mixed $request Current request
     * @param callable $next Callback when middleware completes
     * @return mixed
     */
    public function handle($request, $next) {
        // If there are still unexecuted middlewares
        if ($this->current < count($this->Middleware)) {
            $middleware = $this->Middleware[$this->current];
            $this->current++;

            // If middleware is callback - Execute current middleware
            if (is_callable($middleware)) {
                return $middleware($request, function ($request) use ($next) {
                    return $this->handle($request, $next); // Call next middleware
                });
            }

            // If middleware is string class, instantiate and call handle
            if (is_string($middleware) && class_exists($middleware)) {
                $middlewareInstance = new $middleware();
                return $middlewareInstance->handle($request, function ($request) use ($next) {
                    return $this->handle($request, $next);
                });
            }
        }

        // No more middlewares, proceed to next processing (controller)
        return $next($request);
    }
}