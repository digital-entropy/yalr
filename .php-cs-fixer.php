<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('vendor')
    ->exclude('.build')
    ->exclude('.github')
    ->notPath('config/routes.php');

$config = new PhpCsFixer\Config();

return $config
    ->setRiskyAllowed(true)
    ->setRules([
        '@PhpCsFixer:risky' => true,
    ])
    ->setFinder($finder);
