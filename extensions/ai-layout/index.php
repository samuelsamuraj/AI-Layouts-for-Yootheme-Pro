<?php

use YOOtheme\Application;

return [
    'name' => 'ai-builder',
    'title' => 'AI Builder',
    'icon' => 'layout',
    'description' => 'Generate AI-driven layouts with OpenAI',
    'version' => '0.3.4',
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
            // Debug: Log that extension is loading
            error_log('AI Builder Extension: Loading...');
            
            // Register customizer panel
            $app->extend('customizer.panels', function ($panels) {
                $panels['ai-builder'] = [
                    'title' => 'AI Builder',
                    'description' => 'Generate AI-driven layouts',
                    'icon' => 'layout',
                    'priority' => 100
                ];
                error_log('AI Builder Extension: Panel registered');
                return $panels;
            });

            // Register customizer sections
            $app->extend('customizer.sections', function ($sections) {
                $sections['ai-builder'] = [
                    'title' => 'AI Layout Generator',
                    'panel' => 'ai-builder',
                    'priority' => 10
                ];
                error_log('AI Builder Extension: Section registered');
                return $sections;
            });

            // Register customizer controls
            $app->extend('customizer.controls', function ($controls) {
                $controls['ai-builder-generator'] = [
                    'component' => 'ai-layout-panel',
                    'props' => [
                        'type' => 'ai-builder-generator'
                    ]
                ];
                error_log('AI Builder Extension: Control registered');
                return $controls;
            });
            
            error_log('AI Builder Extension: All components registered');
        }
    ]
];
