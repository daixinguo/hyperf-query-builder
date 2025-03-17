<?php

declare(strict_types=1);

namespace ApiElf\QueryBuilder;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [],
            'commands' => [],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for hyperf-query-builder.',
                    'source' => __DIR__ . '/../config/query-builder.php',
                    'destination' => BASE_PATH . '/config/autoload/query-builder.php',
                ],
            ],
        ];
    }
}
