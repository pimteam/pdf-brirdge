<?php

$finder = Isolated\Symfony\Component\Finder\Finder::class;

return [
    'prefix' => 'PdfBridge',

    'finders' => [
        $finder::create()
            ->files()
            ->in(__DIR__ . '/vendor'),

        $finder::create()
            ->append([
                __DIR__ . '/composer.json',
            ]),
    ],
];
