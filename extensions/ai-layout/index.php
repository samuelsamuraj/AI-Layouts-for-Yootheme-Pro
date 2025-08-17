<?php

use YOOtheme\Application;

return [
    'name' => 'ai-layout',
    'title' => 'AI Layout',
    'icon' => 'layout',
    'description' => 'Generate AI-driven layouts with OpenAI',
    'version' => '0.3.3',
    'author' => 'Samuraj ApS',
    'author_url' => 'https://samuraj.dk',
    'main' => __DIR__ . '/dist/index.js',
    'dependencies' => ['@yootheme/theme'],
    'require' => [
        'php' => '>=7.4',
        'wordpress' => '>=6.0',
        'yootheme' => '>=4.0'
    ],
    'events' => [
        'theme.init' => function (Application $app) {
            // Register customizer panel
            $app->extend('customizer.panels', function ($panels) {
                $panels['ai-layout'] = [
                    'title' => 'AI Layout',
                    'description' => 'Generate AI-driven layouts',
                    'icon' => 'layout',
                    'priority' => 100
                ];
                return $panels;
            });

            // Register customizer sections
            $app->extend('customizer.sections', function ($sections) {
                $sections['ai-layout'] = [
                    'title' => 'AI Layout Generator',
                    'panel' => 'ai-layout',
                    'priority' => 10
                ];
                return $sections;
            });

            // Register customizer controls
            $app->extend('customizer.controls', function ($controls) {
                $controls['ai-layout-generator'] = [
                    'component' => 'ai-layout-panel',
                    'props' => [
                        'type' => 'ai-layout-generator'
                    ]
                ];
                return $controls;
            });
        }
    ]
];
