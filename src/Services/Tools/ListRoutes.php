<?php

namespace DewaldHugo\LaravelMcp\Services\Tools;

use Illuminate\Support\Facades\Route;
use DewaldHugo\LaravelMcp\Contracts\ToolInterface;

class ListRoutes implements ToolInterface
{
    /**
     * Internal memory cache for compiled routing maps.
     */
    private ?array $cachedRoutes = null;

    /**
     * Stored tracking integer to detect real-time framework route modifications.
     */
    private ?int $cachedRouteCount = null;

    public function getName(): string
    {
        return 'list_routes';
    }

    public function getDescription(): string
    {
        return 'Parses the application router to output all available endpoints, HTTP methods, controller mappings, names, and applied middlewares.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => (object)[]
        ];
    }

    public function execute(array $arguments): array
    {
        $routeCollection = Route::getRoutes();
        $currentCount = $routeCollection->count();

        // Performance Boundary: Serve immediately from memory if router state is unchanged
        if ($this->cachedRoutes !== null && $this->cachedRouteCount === $currentCount) {
            return $this->formatResponse($this->cachedRoutes);
        }

        $routes = $routeCollection->getRoutes();
        $mappedRoutes = [];

        foreach ($routes as $route) {
            $action = $route->getActionName();
            
            if ($action instanceof \Closure || $action === 'Closure') {
                $action = 'Closure (Anonymous)';
            }

            $mappedRoutes[] = [
                'uri' => $route->uri(),
                'methods' => $route->methods(),
                'action' => $action,
                'name' => $route->getName() ?? 'None',
                'middleware' => array_values($route->gatherMiddleware()),
            ];
        }

        // Hydrate the runtime lifecycle cache
        $this->cachedRoutes = $mappedRoutes;
        $this->cachedRouteCount = $currentCount;

        return $this->formatResponse($mappedRoutes);
    }

    /**
     * Standardize the output envelope format.
     */
    private function formatResponse(array $routes): array
    {
        return [
            'content' => [
                [
                    'type' => 'text',
                    'text' => json_encode($routes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                ]
            ]
        ];
    }
}
