<?php

require __DIR__ . '/../vendor/autoload.php';

$sig = 'travelport:pnr-ssrs {locator : Universal Record locator (e.g. #87) or booking ID prefixed with # (e.g. #87)}';
[$name, $arguments, $options] = Illuminate\Console\Parser::parse($sig);

echo "name={$name}\n";
foreach ($arguments as $arg) {
    echo 'arg name=' . $arg->getName() . ' required=' . ($arg->isRequired() ? 'yes' : 'no') . "\n";
}
