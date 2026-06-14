<?php

namespace OriginMain\LaravelMcp\Services\Tools;

use Illuminate\Support\Facades\Route;
use OriginMain\LaravelMcp\Contracts\ToolInterface;

class ListRoutes implements ToolInterface
{
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
        $routes = Route::getRoutes()->getRoutes();
        $mappedRoutes = [];

        foreach ($routes as $route) {
            $action = $route->getActionName();
            
            // Handle and normalize anonymous closures safely
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

        return [
            'content' => [
                [
                    'type' => 'text',
                    'text' => json_encode($mappedRoutes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                ]
            ]
        ];
    }
}
