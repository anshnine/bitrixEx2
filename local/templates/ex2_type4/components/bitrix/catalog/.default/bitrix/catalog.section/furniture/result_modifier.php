<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Iblock\Elements\ElementStatusTable;
use Bitrix\Iblock\Elements\ElementRevTable;

if (!Loader::includeModule('iblock')) {
    return;
}

$publishStatusId = (int)(ElementStatusTable::query()
    ->setSelect(['ID'])
    ->setFilter(['=NAME' => 'Публикуется'])
    ->fetch()['ID'] ?? 0);

$reviewsByProduct = [];
$productsWithReviews = 0;

if ($publishStatusId > 0) {
    $authorPublishIdList = [];
    $authorQuery = UserTable::query()
        ->setSelect(['ID'])
        ->setFilter(['=UF_AUTHOR_STATUS' => $publishStatusId, '=ACTIVE' => 'Y'])
        ->exec();

    while ($author = $authorQuery->fetch()) {
        $authorPublishIdList[] = (int)$author['ID'];
    }

    $productIds = array_map('intval', array_column($arResult['ITEMS'], 'ID'));

    if ($authorPublishIdList && $productIds) {
        $revQueryResult = ElementRevTable::query()
            ->setSelect([
                'ID',
                'NAME',
                'AUTHOR_VALUE' => 'AUTHOR.VALUE',
                'PRODUCT_VALUE' => 'PRODUCT.VALUE',
            ])
            ->where('ACTIVE', '=', 'Y')
            ->whereIn('PRODUCT.VALUE', $productIds)
            ->whereIn('AUTHOR.VALUE', $authorPublishIdList)
            ->exec();

        while ($review = $revQueryResult->fetch()) {
            $productId = (int)$review['PRODUCT_VALUE'];
            $reviewsByProduct[$productId][] = [
                'ID' => (int)$review['ID'],
                'NAME' => $review['NAME'],
            ];
        }
    }
}

foreach ($arResult['ITEMS'] as $key => $arItem) {
    $arItem['PRICES']['PRICE']['PRINT_VALUE'] =
        number_format((float)$arItem['PRICES']['PRICE']['PRINT_VALUE'], 0, '.', ' ')
        . ' ' . $arItem['PROPERTIES']['PRICECURRENCY']['VALUE_ENUM'];

    $arItem['EX2_REVIEWS'] = $reviewsByProduct[(int)$arItem['ID']] ?? [];

    if ($arItem['EX2_REVIEWS']) {
        $productsWithReviews++;
    }

    $arResult['ITEMS'][$key] = $arItem;
}

$arResult['EX2_PRODUCTS_WITH_REVIEWS'] = $productsWithReviews;

$this->__component->SetResultCacheKeys(['ITEMS', 'EX2_PRODUCTS_WITH_REVIEWS']);
