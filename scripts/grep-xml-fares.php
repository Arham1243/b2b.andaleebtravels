<?php
$xml = file_get_contents(__DIR__ . '/travelport-lfs-raw.xml');
preg_match_all('/FareBasis="([^"]*AE1[^"]*)"/i', $xml, $m);
echo 'All AE1 fare bases: ' . implode(', ', array_unique($m[1])) . PHP_EOL;
preg_match_all('/Name="([^"]*)"[^>]*Carrier="EK"/i', $xml, $b);
echo 'EK brand names: ' . implode(', ', array_unique($b[1])) . PHP_EOL;
