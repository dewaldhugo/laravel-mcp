<?php

namespace DewaldHugo\LaravelMcp\Services\Tools;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;
use DewaldHugo\LaravelMcp\Contracts\ToolInterface;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

class ReadModelSchema implements ToolInterface
{
    public function getName(): string
    {
        return 'read_model_schema';
    }

    public function getDescription(): string
    {
        return 'Inspects database columns, data types, defaults, and Eloquent relationships via reflection for a given model class.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'model' => [
                    'type' => 'string',
                    'description' => 'The fully qualified class name of the Eloquent model (e.g., App\\Models\\User).'
                ]
            ],
            'required' => ['model']
        ];
    }

    public function execute(array $arguments): array
    {
        $modelClass = $arguments['model'] ?? '';

        if (!class_exists($modelClass)) {
            return $this->errorContent("Target class '{$modelClass}' does not exist.");
        }

        if (!is_subclass_of($modelClass, Model::class)) {
            return $this->errorContent("Class '{$modelClass}' is not a valid Eloquent Model.");
        }

        try {
            /** @var Model $instance */
            $instance = new $modelClass();
            $table = $instance->getTable();

            // 1. Reflect Columns and Attributes
            $columns = [];
            if (method_exists(Schema::getFacadeRoot(), 'getColumns')) {
                foreach (Schema::getColumns($table) as $column) {
                    $columns[] = [
                        'name' => $column['name'],
                        'type' => $column['type_name'] ?? $column['type'],
                        'nullable' => $column['nullable'],
                        'default' => $column['default'],
                    ];
                }
            } else {
                foreach (Schema::getColumnListing($table) as $name) {
                    $columns[] = [
                        'name' => $name,
                        'type' => Schema::getColumnType($table, $name),
                        'nullable' => 'unknown',
                        'default' => null,
                    ];
                }
            }

            // 2. Reflect Relationships
            $relationships = [];
            $reflection = new ReflectionClass($modelClass);

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                // Skip methods requiring parameters or those declared directly on the base Model class
                if ($method->getNumberOfParameters() > 0 || $method->getDeclaringClass()->getName() === Model::class) {
                    continue;
                }

                try {
                    $return = $method->invoke($instance);
                    if ($return instanceof Relation) {
                        $relationships[] = [
                            'name' => $method->getName(),
                            'type' => (new ReflectionClass($return))->getShortName(),
                            'related_model' => get_class($return->getRelated()),
                        ];
                    }
                } catch (Throwable $e) {
                    // Bypass methods that throw exceptions when invoked statically/without full context
                    continue;
                }
            }

            $schemaMap = [
                'model' => $modelClass,
                'table' => $table,
                'columns' => $columns,
                'relationships' => $relationships,
            ];

            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => json_encode($schemaMap, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                    ]
                ]
            ];

        } catch (Throwable $e) {
            return $this->errorContent("Failed to reflect model schema: " . $e->getMessage());
        }
    }

    private function errorContent(string $message): array
    {
        return [
            'isError' => true,
            'content' => [
                [
                    'type' => 'text',
                    'text' => $message
                ]
            ]
        ];
    }
}
