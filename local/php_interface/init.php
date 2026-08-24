<?php

// CONSTS

const IB_REVIEWS = 5;
// EVENTS

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

$eventManager = \Bitrix\Main\EventManager::getInstance();

$eventManager->addEventHandler('iblock', 'OnBeforeIBlockElementAdd', 'revIblockHandler');
$eventManager->addEventHandler('iblock', 'OnBeforeIBlockElementUpdate', 'revIblockHandler');
$eventManager->addEventHandler('iblock', 'OnBeforeIBlockElementUpdate', 'revIblockHandler');

class Ex2ReviewHandler
{
    protected static array $oldAuthors = [];

    function revIblockHandler(&$event)
    {

        if (!isRevIblock($event['IBLOCK_ID'])) {
            return;
        }

        if (!array_key_exists('PREVIEW_TEXT', $event)) {
            return;
        }
        $preview = str_replace('#del#', '', $event['PREVIEW_TEXT'] ?? '');

        if (mb_strlen($preview) < 5) {
            debug(55555);
            global $APPLICATION;
            $APPLICATION->ThrowException('Меньше 5 символов описания не может быть');
            return false;
        }

    }

    function isRevIblock(int $iblockId): bool
    {

        return $iblockId === IB_REVIEWS;
    }
}
