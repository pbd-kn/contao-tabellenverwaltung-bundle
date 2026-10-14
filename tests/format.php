<?php
require dirname(__DIR__).'/src/Content/CellFormatter.php';
use PBDKN\TabellenverwaltungBundle\Content\CellFormatter;
function verify(bool $ok):void { if(!$ok)throw new RuntimeException('Formatting check failed'); }
verify(str_contains(CellFormatter::format('EMAIL','info@example.com',''), 'mailto:info@example.com'));
verify(str_contains(CellFormatter::format('INTERNET1','https://example.com/',''), 'href="https://example.com/"'));
verify(!str_contains(CellFormatter::format('INTERNET','javascript:alert(1)',''), '<a'));
verify(!str_contains(CellFormatter::format('NAME','<script>alert(1)</script>',''), '<script>'));
verify(str_contains(CellFormatter::format('TELEFON','07732/3618',''), 'tel:077323618'));
verify(!str_contains(CellFormatter::format('LOGO','files/../../outside.png',dirname(__DIR__,3)), '<img'));
echo "Formatting checks passed.\n";
