<?php

use Bitrix\Main\Loader;
use Bitrix\Main\EventLog\Internal\EventLogTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\Elements\ElementRevTable;

const IB_REVIEWS = 5;
function debug($data): void
{
    $content = implode(PHP_EOL, array_filter([
            str_repeat('-', 80),
            print_r($data, true),
            '',
        ])) . PHP_EOL;

    $logFiles = [
        __DIR__ . '/debug.log',
        $_SERVER['DOCUMENT_ROOT'] . '/upload/debug.log',
    ];

    foreach ($logFiles as $logFile) {
        @unlink($logFile);
    }

    foreach ($logFiles as $logFile) {
        if (@file_put_contents($logFile, $content, LOCK_EX) !== false) {
            return;
        }
    }
}

Loader::includeModule('iblock');

$eventManager = \Bitrix\Main\EventManager::getInstance();

$eventManager->addEventHandler('iblock', 'OnBeforeIBlockElementAdd', 'Ex2ReviewBefore');
$eventManager->addEventHandler('iblock', 'OnBeforeIBlockElementUpdate', 'Ex2ReviewBefore');
$eventManager->addEventHandler('iblock', 'OnAfterIBlockElementUpdate', 'Ex2ReviewAfter');

function isRevIblock(int $iblockId): bool
{
    return $iblockId !== IB_REVIEWS;
}

function Ex2ReviewBefore(&$arFields)
{
    // debug($arFields);
    if (isRevIblock($arFields['IBLOCK_ID'])) {
        return;
    }

    $previewText = str_replace('#del#', '', $arFields['PREVIEW_TEXT']) ?? '';

    if (mb_strlen($previewText) < 5) {
        global $APPLICATION;
        $APPLICATION->ThrowException('Текст анонса слишком короткий');
        return false;
    }

}

function Ex2ReviewAfter(&$arFields)
{
    if (isRevIblock($arFields['IBLOCK_ID'])) {
        return;
    }

    $res = \CIBlockElement::GetProperty(
        $arFields['IBLOCK_ID'],
        $arFields['ID'],
        'sort',
        'asc',
        ['CODE' => 'AUTHOR'] // Ищем по коду, а не по ID
    );
    if ($prop = $res->Fetch()) {
        $oldAuthorId = (int)$prop['VALUE'];
    }

    $arFields['prev']=$oldAuthorId;
    debug($arFields);
}