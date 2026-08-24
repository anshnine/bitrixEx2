<?php
/**
 * Эталон ex2-591 — только для тренировки.
 * НЕ подключается автоматически.
 *
 * Когда будешь готов — скопируй нужное в init.php
 * или в конце init.php: require __DIR__ . '/test_init.php';
 */

use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\EventLog\Internal\EventLogTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\Elements\ElementRevTable;

// --- debug (удалить на экзамене) ---

function ex2Debug($data, string $label = ''): void
{
    $content = implode(PHP_EOL, array_filter([
        str_repeat('-', 80),
        date('Y-m-d H:i:s'),
        $label !== '' ? $label : null,
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

// --- регистрация (раскомментировать при подключении файла) ---

/*
Loader::includeModule('iblock');

EventManager::getInstance()->addEventHandler('iblock', 'OnBeforeIBlockElementAdd', 'Ex2ReviewBefore');
EventManager::getInstance()->addEventHandler('iblock', 'OnBeforeIBlockElementUpdate', 'Ex2ReviewBefore');
EventManager::getInstance()->addEventHandler('iblock', 'OnAfterIBlockElementUpdate', 'Ex2ReviewAfter');
*/

// Вариант A: знаешь ID инфоблока «Рецензии» (смотри в админке, URL IBLOCK_ID=...)
const IB_REVIEWS = 5;

// Вариант B: искать по коду (если не хочешь хардкодить ID)
$ex2RevIblockId = null;
$ex2OldAuthors = [];

function Ex2ReviewBefore(&$arFields)
{
    global $ex2RevIblockId, $ex2OldAuthors;

    // --- фильтр: только инфоблок «Рецензии» ---

    // Вариант A (константа):
    if ((int)$arFields['IBLOCK_ID'] !== IB_REVIEWS) {
        return;
    }

    // Вариант B (по коду reviews) — закомментируй A, раскомментируй B:
    /*
    if ($ex2RevIblockId === null) {
        $ex2RevIblockId = (int)(IblockTable::query()
            ->setSelect(['ID'])
            ->setFilter(['=CODE' => 'reviews', '=IBLOCK_TYPE_ID' => 'ex2'])
            ->fetch()['ID'] ?? 0);
    }
    if ((int)$arFields['IBLOCK_ID'] !== $ex2RevIblockId) {
        return;
    }
    */

    // --- Update: запомнить старого автора (для журнала в After) ---
    if (!empty($arFields['ID'])) {
        $row = ElementRevTable::getByPrimary((int)$arFields['ID'], [
            'select' => ['AUTHOR_VALUE' => 'AUTHOR.VALUE'],
        ])->fetch();
        $ex2OldAuthors[(int)$arFields['ID']] = (int)($row['AUTHOR_VALUE'] ?? 0);
    }

    // --- анонс: убрать #del# → проверить длину ---
    $preview = str_replace('#del#', '', (string)($arFields['PREVIEW_TEXT'] ?? ''));
    $arFields['PREVIEW_TEXT'] = $preview;

    if (mb_strlen($preview) < 5) {
        global $APPLICATION;
        $APPLICATION->ThrowException('Текст анонса слишком короткий: ' . mb_strlen($preview));
        return false;
    }
}

function Ex2ReviewAfter(&$arFields)
{
    global $ex2RevIblockId, $ex2OldAuthors;

    // Вариант A:
    if ((int)$arFields['IBLOCK_ID'] !== IB_REVIEWS) {
        return;
    }

    // Вариант B:
    /*
    if ((int)$arFields['IBLOCK_ID'] !== $ex2RevIblockId) {
        return;
    }
    */

    $id = (int)$arFields['ID'];
    $old = (int)($ex2OldAuthors[$id] ?? 0);
    $new = (int)(ElementRevTable::getByPrimary($id, [
        'select' => ['AUTHOR_VALUE' => 'AUTHOR.VALUE'],
    ])->fetch()['AUTHOR_VALUE'] ?? 0);

    if ($old !== $new) {
        EventLogTable::add([
            'AUDIT_TYPE_ID' => 'ex2_590',
            'MODULE_ID'     => 'iblock',
            'ITEM_ID'       => (string)$id,
            'DESCRIPTION'   => "В рецензии {$id} изменился автор с {$old} на {$new}",
        ]);
    }
}
