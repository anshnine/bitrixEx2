# Экзамен №2 — шпаргалка

**Билет:** https://academy.1c-bitrix.ru/~ex21ticket  
**Требования:** https://academy.1c-bitrix.ru/~ex21desc  
**Код:** `/local/php_interface/init.php` (+ lang, при необходимости классы в `/local/php_interface/lib/`)  
**Шаблон:** `/local/templates/ex2_type4/`

> **API:** на экзамене допустимы **и D7, и legacy** (`CIBlock*`, `CEventLog`, `AddEventHandler`).  
> **Обязательно по описанию:** константы ID в одном месте, **lang-файлы** для фраз, **static-класс** между Before/After, **кеш** в 581.

**API-коды ИБ:** `Rev` → `ElementRevTable`, `status` → `ElementStatusTable`

---

## Требования экзамена (чеклист)

| Требование | Где |
|------------|-----|
| ID ИБ, групп — **константы в одном месте**, не магические числа по коду | `Ex2Config` / блок const в init |
| Фразы — **только lang-файлы**, не в PHP/HTML | `lang/ru/init.php`, lang шаблона |
| Данные между Before/After — **static свойство класса**, не `$GLOBALS`, **не** `$arFields` | классы-обработчики |
| 581: доп. данные **кешируются**, `SetResultCacheKeys` | `result_modifier.php` |
| 581: **не** `cache` в ORM при кеше компонента | query без `cache` |
| Кеш компонента и платформы **включён** в конце | `CACHE_TYPE => 'A'` |
| Только **активные** элементы, если в билете не сказано иное | `ACTIVE = Y` |
| Нет запросов к БД **в цикле** по элементам | один `whereIn` |
| Бизнес-логика **не** в template.php | только вывод |

---

## Константы и lang (сделать один раз)

### `/local/php_interface/ex2_config.php`

```php
<?php

class Ex2Config
{
    public const IB_TYPE_EX2     = 'ex2';
    public const IB_CODE_REVIEWS = 'reviews';
    public const IB_CODE_STATUS  = 'status';
    public const GROUP_CONTENT   = 5;   // «Контент-редакторы» — ID с сервера
    public const STATUS_PUBLISH  = 'Публикуется';
    public const AGENT_OPT_LAST  = 'ex2_610_last_run';
}
```

ID инфоблока на экзамене лучше **получить один раз** (не хардкодить 5), если не знаешь заранее:

```php
public static function ibReviewsId(): int
{
    static $id;
    if ($id === null) {
        $row = \Bitrix\Iblock\IblockTable::query()
            ->setSelect(['ID'])
            ->setFilter(['=CODE' => self::IB_CODE_REVIEWS, '=IBLOCK_TYPE_ID' => self::IB_TYPE_EX2])
            ->fetch();
        $id = (int)($row['ID'] ?? 0);
    }
    return $id;
}
```

Подключение в init: `require_once __DIR__ . '/ex2_config.php';`

### `/local/php_interface/lang/ru/init.php`

```php
<?php
$MESS['EX2_PREVIEW_TOO_SHORT'] = 'Текст анонса слишком короткий: #LEN#';
$MESS['EX2_AUTHOR_CHANGED']    = 'В рецензии #ID# изменился автор с #OLD# на #NEW#';
$MESS['EX2_AGENT_LOG']          = 'Запуск агента ex2_610. С #FROM# изменилось #COUNT# рецензий';
$MESS['EX2_MENU_QUICK']         = 'Быстрый доступ';
$MESS['EX2_MENU_LINK1']         = 'Ссылка 1';
$MESS['EX2_MENU_LINK2']         = 'Ссылка 2';
```

### init.php — подключение lang

```php
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
// или IncludeModuleLangFile(__FILE__);
```

### Lang шаблона каталога

`/local/templates/ex2_type4/components/.../catalog.section/furniture/lang/ru/template.php`:

```php
<?php
$MESS['EX2_REVIEWS_TITLE'] = 'Рецензии:';
```

В template: `<?= GetMessage('EX2_REVIEWS_TITLE') ?>`

---

## ex2-31 — Подготовка (~25 мин)

| # | Где | Что |
|---|-----|-----|
| 1 | `/local/templates/ex2_type4/` | Шаблон сайту |
| 2 | `.settings.php` | `'debug' => true` |
| 3 | Типы свойств структуры | `ex2_meta` = `ex2 #count#` на `/` |
| 4 | `header.php` | `ShowProperty('ex2_meta')` в meta |
| 5 | ИБ тип `ex2` | `reviews`, `status`, API `Rev`, `status` |
| 6 | UF `USER` | `UF_AUTHOR_STATUS` → элементы `status`, **Флажки** |

---

## ex2-591 — Обработчики рецензий (~15 мин)

**Класс + static** (урок 4514), **lang** для фраз.

```php
EventManager::getInstance()->addEventHandler(
    'iblock', 'OnBeforeIBlockElementAdd', [Ex2ReviewHandler::class, 'onBefore']
);
EventManager::getInstance()->addEventHandler(
    'iblock', 'OnBeforeIBlockElementUpdate', [Ex2ReviewHandler::class, 'onBefore']
);
EventManager::getInstance()->addEventHandler(
    'iblock', 'OnAfterIBlockElementUpdate', [Ex2ReviewHandler::class, 'onAfter']
);

class Ex2ReviewHandler
{
    protected static array $oldAuthors = [];

    public static function onBefore(&$arFields)
    {
        if ((int)$arFields['IBLOCK_ID'] !== Ex2Config::ibReviewsId()) {
            return;
        }

        if (!empty($arFields['ID'])) {
            $row = ElementRevTable::getByPrimary((int)$arFields['ID'], [
                'select' => ['AUTHOR_VALUE' => 'AUTHOR.VALUE'],
            ])->fetch();
            self::$oldAuthors[(int)$arFields['ID']] = (int)($row['AUTHOR_VALUE'] ?? 0);
        }

        $preview = str_replace('#del#', '', (string)($arFields['PREVIEW_TEXT'] ?? ''));
        $arFields['PREVIEW_TEXT'] = $preview;

        if (mb_strlen($preview) < 5) {
            global $APPLICATION;
            $APPLICATION->ThrowException(Loc::getMessage('EX2_PREVIEW_TOO_SHORT', [
                '#LEN#' => mb_strlen($preview),
            ]));
            return false;
        }
    }

    public static function onAfter(&$arFields): void
    {
        if ((int)$arFields['IBLOCK_ID'] !== Ex2Config::ibReviewsId()) {
            return;
        }

        $id = (int)$arFields['ID'];
        if (!array_key_exists($id, self::$oldAuthors)) {
            return;
        }

        $old = self::$oldAuthors[$id];
        unset(self::$oldAuthors[$id]);

        $new = (int)(ElementRevTable::getByPrimary($id, [
            'select' => ['AUTHOR_VALUE' => 'AUTHOR.VALUE'],
        ])->fetch()['AUTHOR_VALUE'] ?? 0);

        if ($old === $new) {
            return;
        }

        EventLogTable::add([
            'AUDIT_TYPE_ID' => 'ex2_590',
            'MODULE_ID'     => 'iblock',
            'ITEM_ID'       => (string)$id,
            'DESCRIPTION'   => Loc::getMessage('EX2_AUTHOR_CHANGED', [
                '#ID#' => $id, '#OLD#' => $old, '#NEW#' => $new,
            ]),
        ]);
    }
}
```

| Проверка | Ожидание |
|----------|----------|
| анонс `abc` | ошибка с длиной |
| `ab#del#c` | сохранится `abc` |
| смена автора | журнал **`ex2_590`** |

---

## ex2-601 — Письмо при смене UF (~10 мин)

**Админка:** событие `EX2_AUTHOR_INFO`, шаблон «Информирование», `test@academy.1c-bitrix.ru`  
Текст шаблона (в админке): `Статус изменился. Был: #OLD_UF_STATUS#, стал: #NEW_UF_STATUS#`

```php
EventManager::getInstance()->addEventHandler('main', 'OnBeforeUserUpdate', [Ex2UserHandler::class, 'onBefore']);
EventManager::getInstance()->addEventHandler('main', 'OnAfterUserUpdate', [Ex2UserHandler::class, 'onAfter']);

class Ex2UserHandler
{
    protected static array $oldStatus = [];

    public static function onBefore(&$arFields): void
    {
        if (empty($arFields['ID'])) {
            return;
        }
        $user = UserTable::getByPrimary((int)$arFields['ID'], [
            'select' => ['UF_AUTHOR_STATUS'],
        ])->fetch();
        self::$oldStatus[(int)$arFields['ID']] = (array)($user['UF_AUTHOR_STATUS'] ?? []);
    }

    public static function onAfter(&$arFields): void
    {
        if ($arFields['RESULT'] !== true || !array_key_exists('UF_AUTHOR_STATUS', $arFields)) {
            return;
        }

        $id = (int)$arFields['ID'];
        if (!array_key_exists($id, self::$oldStatus)) {
            return;
        }

        $old = array_map('intval', self::$oldStatus[$id]);
        unset(self::$oldStatus[$id]);

        $new = array_map('intval', (array)$arFields['UF_AUTHOR_STATUS']);
        sort($old);
        sort($new);
        if ($old === $new) {
            return;
        }

        MailEvent::send([
            'EVENT_NAME' => 'EX2_AUTHOR_INFO',
            'LID'        => SITE_ID,
            'C_FIELDS'   => [
                'OLD_UF_STATUS' => Ex2UserHandler::statusName($old[0] ?? 0),
                'NEW_UF_STATUS' => Ex2UserHandler::statusName($new[0] ?? 0),
            ],
        ]);
    }

    protected static function statusName(int $id): string
    {
        if ($id <= 0) {
            return '';
        }
        return (string)(ElementStatusTable::getByPrimary($id, ['select' => ['NAME']])->fetch()['NAME'] ?? '');
    }
}
```

---

## ex2-611 — Агент (~10 мин)

**Админка:** `Agent_ex_610();`, интервал **86400**

```php
function Agent_ex_610(): string
{
    $opt = Ex2Config::AGENT_OPT_LAST;
    $last = (int)Option::get('main', $opt, 0);

    $q = ElementRevTable::query()->setSelect(['ID']);
    if ($last > 0) {
        $q->where('TIMESTAMP_X', '>', DateTime::createFromTimestamp($last));
    }
    $count = $q->queryCountTotal();

    EventLogTable::add([
        'AUDIT_TYPE_ID' => 'ex2_610',
        'MODULE_ID'     => 'main',
        'DESCRIPTION'   => Loc::getMessage('EX2_AGENT_LOG', [
            '#FROM#'  => $last ? date('d.m.Y H:i:s', $last) : '—',
            '#COUNT#' => $count,
        ]),
    ]);

    Option::set('main', $opt, (string)time());
    return 'Agent_ex_610();';
}
```

---

## ex2-631 — Поиск (~10 мин)

**После кода:** Настройки → Поиск → **Переиндексация**

```php
EventManager::getInstance()->addEventHandler('search', 'BeforeIndex', [Ex2SearchHandler::class, 'onBeforeIndex']);

class Ex2SearchHandler
{
    public static function onBeforeIndex($arFields)
    {
        if ($arFields['MODULE_ID'] !== 'iblock') {
            return $arFields;
        }
        if ((int)$arFields['PARAM2'] !== Ex2Config::ibReviewsId()) {
            return $arFields;
        }

        $authorId = (int)(ElementRevTable::getByPrimary((int)$arFields['ITEM_ID'], [
            'select' => ['AUTHOR_VALUE' => 'AUTHOR.VALUE'],
        ])->fetch()['AUTHOR_VALUE'] ?? 0);

        if ($authorId > 0) {
            $login = (string)(UserTable::getByPrimary($authorId, ['select' => ['LOGIN']])->fetch()['LOGIN'] ?? '');
            if ($login !== '') {
                $arFields['TITLE'] = $login . ' ' . $arFields['TITLE'];
            }
        }

        return $arFields;
    }
}
```

---

## ex2-581 — Каталог `/products/` (~30 мин)

**Файлы:**

| Файл | Действие |
|------|----------|
| `result_modifier.php` | логика + **SetResultCacheKeys** |
| `component_epilog.php` | **SetPageProperty** для `#count#` (работает при кеше!) |
| `template.php` | вывод + **GetMessage** |
| `lang/ru/template.php` | фраза «Рецензии:» |

### result_modifier.php

```php
<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Iblock\Elements\ElementRevTable;
use Bitrix\Iblock\Elements\ElementStatusTable;

Loader::includeModule('iblock');

$publishId = (int)(ElementStatusTable::query()
    ->setSelect(['ID'])
    ->setFilter(['=NAME' => Ex2Config::STATUS_PUBLISH])
    ->fetch()['ID'] ?? 0);

$productIds = array_map('intval', array_column($arResult['ITEMS'], 'ID'));
$authors = [];

if ($publishId > 0) {
    $rs = UserTable::query()
        ->setSelect(['ID'])
        ->setFilter(['=UF_AUTHOR_STATUS' => $publishId, '=ACTIVE' => 'Y'])
        ->exec();
    while ($u = $rs->fetch()) {
        $authors[] = (int)$u['ID'];
    }
}

$byProduct = [];
if ($productIds && $authors) {
    $rs = ElementRevTable::query()
        ->setSelect([
            'ID', 'NAME',
            'AUTHOR_VALUE'  => 'AUTHOR.VALUE',
            'PRODUCT_VALUE' => 'PRODUCT.VALUE',
        ])
        ->where('ACTIVE', '=', 'Y')
        ->whereIn('PRODUCT.VALUE', $productIds)
        ->whereIn('AUTHOR.VALUE', $authors)
        ->exec();
    while ($r = $rs->fetch()) {
        $byProduct[(int)$r['PRODUCT_VALUE']][] = ['ID' => (int)$r['ID'], 'NAME' => $r['NAME']];
    }
}

$withReviews = 0;
foreach ($arResult['ITEMS'] as $k => $item) {
    $item['PRICES']['PRICE']['PRINT_VALUE'] =
        number_format((float)$item['PRICES']['PRICE']['PRINT_VALUE'], 0, '.', ' ')
        . ' ' . $item['PROPERTIES']['PRICECURRENCY']['VALUE_ENUM'];
    $item['EX2_REVIEWS'] = $byProduct[$item['ID']] ?? [];
    if ($item['EX2_REVIEWS']) {
        $withReviews++;
    }
    $arResult['ITEMS'][$k] = $item;
}

$arResult['EX2_PRODUCTS_WITH_REVIEWS'] = $withReviews;

$this->__component->SetResultCacheKeys(['ITEMS', 'EX2_PRODUCTS_WITH_REVIEWS']);
```

> **Без** `cache` в ORM. **SetResultCacheKeys** — только нужные ключи.

### component_epilog.php (рядом с result_modifier)

```php
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
```

> `SetPageProperty` в **epilog**, не только в result_modifier — epilog выполняется и при **попадании в кеш**.

### template.php — после `PREVIEW_TEXT`

```php
<?php if (!empty($arElement['EX2_REVIEWS'])): ?>
	<div class="catalog-item-reviews">
		<strong><?= GetMessage('EX2_REVIEWS_TITLE') ?></strong><br />
		<?php foreach ($arElement['EX2_REVIEWS'] as $review): ?>
			<?= $review['NAME'] ?><br />
		<?php endforeach; ?>
	</div>
<?php endif; ?>
```

> Вывод из `$review['NAME']` без `~` — данные уже безопасны для HTML.

---

## ex2-191 — Админ-меню (~15 мин)

**Пользователь:** `userc` / `123456`, группа **Контент-редакторы**

```php
EventManager::getInstance()->addEventHandler('main', 'OnBuildGlobalMenu', [Ex2AdminHandler::class, 'onBuildMenu']);

class Ex2AdminHandler
{
    public static function onBuildMenu(&$aGlobalMenu, &$aModuleMenu): void
    {
        global $USER;
        if (!in_array(Ex2Config::GROUP_CONTENT, $USER->GetUserGroupArray(), true)) {
            return;
        }

        $aGlobalMenu = ['global_menu_content' => $aGlobalMenu['global_menu_content']];
        $aModuleMenu = array_values(array_filter(
            $aModuleMenu,
            static fn($i) => ($i['parent_menu'] ?? '') === 'global_menu_content'
        ));

        $aModuleMenu[] = [
            'parent_menu' => 'global_menu_content',
            'sort'        => 1000,
            'text'        => Loc::getMessage('EX2_MENU_QUICK'),
            'items_id'    => 'menu_ex2_quick',
            'items'       => [
                ['text' => Loc::getMessage('EX2_MENU_LINK1'), 'url' => 'https://test1', 'title' => Loc::getMessage('EX2_MENU_LINK1')],
                ['text' => Loc::getMessage('EX2_MENU_LINK2'), 'url' => 'https://test2', 'title' => Loc::getMessage('EX2_MENU_LINK2')],
            ],
        ];
    }
}
```

---

## init.php — скелет

```php
<?php

use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserTable;
use Bitrix\Main\Mail\Event as MailEvent;
use Bitrix\Main\EventLog\Internal\EventLogTable;
use Bitrix\Iblock\Elements\ElementRevTable;
use Bitrix\Iblock\Elements\ElementStatusTable;

require_once __DIR__ . '/ex2_config.php';
Loc::loadMessages(__FILE__);

Loader::includeModule('iblock');

// классы Ex2ReviewHandler, Ex2UserHandler, Ex2SearchHandler, Ex2AdminHandler
// addEventHandler для каждого
// function Agent_ex_610()
```

---

## Порядок на экзамене

| # | Задание | мин |
|---|---------|-----|
| 1 | ex2-31 | 25 |
| 2 | ex2-591 | 15 |
| 3 | ex2-601 | 10 |
| 4 | ex2-611 | 10 |
| 5 | ex2-631 | 10 |
| 6 | ex2-581 | 30 |
| 7 | ex2-191 | 15 |

---

## Частые ошибки

- iblock-события → **`&$arFields`**, не `Entity\Event`
- отмена → `ThrowException` + **`return false`**
- между Before/After → **`static` класса**, не `global` / не `$arFields`
- фразы → **lang**, не строки в PHP
- 581 → **`SetResultCacheKeys`** + **`component_epilog`** для meta
- meta в header → **`ShowProperty`**
- журнал смены автора → **`ex2_590`**
- агент → **`return 'Agent_ex_610();'`**
- поиск → **переиндексация**
- в конце → **кеш включён**
