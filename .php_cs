<?php
return PhpCsFixer\Config::create()
    ->setRules([
        '@Symfony' => true,
        'array_syntax' => array('syntax' => 'long'),
    ])
    ->setFinder(PhpCsFixer\Finder::create()->in(__DIR__));
