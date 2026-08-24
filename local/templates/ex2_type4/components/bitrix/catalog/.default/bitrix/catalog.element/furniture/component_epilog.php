<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

global $APPLICATION;

$count = (int)($arResult['EX2_PRODUCTS_WITH_REVIEWS'] ?? 0);

$meta = $APPLICATION->GetProperty('ex2_meta');
if ($meta !== false && str_contains($meta, '#count#')) {
    $APPLICATION->SetPageProperty(
            'ex2_meta',
            str_replace('#count#', (string)$count, $meta)
    );
}