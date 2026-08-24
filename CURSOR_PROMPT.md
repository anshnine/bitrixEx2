# Промпт для Cursor (экзамен Bitrix №2)

> **Как использовать на другом ПК:**  
> 1. `git clone git@github.com:anshnine/bitrixEx2.git`  
> 2. Открыть проект в Cursor  
> 3. Новый чат → вставить **весь блок «Скопируй в чат»** ниже **или** написать:  
>    `@CURSOR_PROMPT.md @EXAM2_GUIDE.md — работай по этим файлам`

---

## Скопируй в чат

```
Ты помогаешь готовиться к экзамену 1С-Битрикс №2 «Основные инструменты кастомизации».

Проект: кастомизация Bitrix (билет ex21). Репозиторий: https://github.com/anshnine/bitrixEx2

ОБЯЗАТЕЛЬНО прочитай перед ответами:
- EXAM2_GUIDE.md — главная шпаргалка по всем заданиям
- CURSOR_PROMPT.md — этот файл (контекст и правила)
- local/php_interface/init.php — текущий код обработчиков
- local/templates/ex2_type4/ — шаблон ex2_type4

Ссылки:
- Билет: https://academy.1c-bitrix.ru/~ex21ticket
- Требования экзамена: https://academy.1c-bitrix.ru/~ex21desc
- Документация: https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43

---

ЗАДАНИЯ БИЛЕТА (порядок на экзамене):

| Код | Суть | Где код |
|-----|------|---------|
| ex2-31 | Шаблон ex2_type4, meta ex2_meta, ИБ reviews/status, UF UF_AUTHOR_STATUS | админка + header.php |
| ex2-591 | Before: #del#, длина анонса ≥5; After: смена AUTHOR → журнал ex2_590 | init.php |
| ex2-601 | Смена UF_AUTHOR_STATUS → письмо EX2_AUTHOR_INFO | init.php + почтовое событие |
| ex2-611 | Агент Agent_ex_610(), журнал ex2_610 | init.php + агенты |
| ex2-631 | BeforeIndex: логин автора в TITLE рецензии | init.php + переиндексация |
| ex2-581 | Рецензии на /products/, meta #count# | result_modifier + component_epilog + template |
| ex2-191 | Меню админки для группы 5 + userc | init.php |

API-коды ИБ: Rev → ElementRevTable, status → ElementStatusTable

---

ТРЕБОВАНИЯ ЭКЗАМЕНА (из официального описания — соблюдать строго):

1. API: допустимы D7 И legacy (CIBlock*, CEventLog, AddEventHandler) — не навязывай только D7.
2. ID инфоблоков/групп — константы в одном месте (Ex2Config), не магические числа по коду.
3. Текстовые фразы — в lang-файлах (Loc::getMessage), не хардкод в PHP/HTML.
4. Между Before/After — static свойство класса; НЕ $GLOBALS; НЕ класть служебные данные в $arFields.
5. ex2-581: SetResultCacheKeys; component_epilog для SetPageProperty (#count# при кеше); без ORM cache в getList.
6. Бизнес-логика не в template.php — только вывод.
7. Активные элементы по умолчанию; без запросов к БД в цикле.
8. Ядро /bitrix/ не трогать; кастомизация в /local/.

---

КРИТИЧНЫЕ ТЕХНИЧЕСКИЕ ФАКТЫ (частые ошибки):

iblock-события OnBeforeIBlockElementAdd/Update:
- Сигнатура: function handler(&$arFields) — НЕ Main\Entity\Event (будет TypeError).
- Отмена сохранения: global $APPLICATION; ThrowException(...); return false;
- #del# удалять ДО проверки mb_strlen.
- PREVIEW_TEXT при Update может отсутствовать в $arFields — проверять array_key_exists.
- После очистки: $arFields['PREVIEW_TEXT'] = $preview;

meta ex2_meta (#count#):
- header.php: ShowProperty('ex2_meta'), НЕ GetProperty().
- SetPageProperty в result_modifier или component_epilog (epilog нужен при кеше).

ElementRevTable / свойства:
- setSelect(['*']) не выбирает свойства — явно: 'AUTHOR_VALUE' => 'AUTHOR.VALUE'
- IN по свойству: whereIn('PRODUCT.VALUE', $ids), не @ в setFilter

581 reviewsByProduct:
- ключ (int), значение массив рецензий: $byProduct[$id][] = [...]
- статус «Публикуется» — '=NAME' => 'Публикуется' (регистр!)

591 журнал: AUDIT_TYPE_ID = ex2_590 (не 591)

---

ТЕКУЩЕЕ СОСТОЯНИЕ ПРОЕКТА (на момент последнего коммита):

- EXAM2_GUIDE.md — актуальная шпаргалка
- ex2-581: result_modifier.php частично готов; component_epilog может быть в catalog.element — проверить путь furniture/section
- ex2-591: init.php в процессе (revIblockHandler / Ex2ReviewHandler — нужен рефакторинг: static методы, After, lang, $event['PREVIEW_TEXT']=$preview)
- ex2-601, 611, 631, 191 — по гайду, в init может быть не всё
- test_init.php — эталон для тренировки 591
- debug() пишет на сервер (local/php_interface/debug.log), локально файла нет

Сервер разработки: Beget (anshniy9.beget.tech) — код заливать через SFTP/Deployment.

---

КАК ОТВЕЧАТЬ:

- Отвечай по-русски, кратко, по делу.
- Код для экзамена — минимальный, без лишнего.
- При правках init.php не ломай уже работающее без запроса.
- Ссылайся на EXAM2_GUIDE.md; при расхождении — приоритет официальному описанию экзамена.
- Не создавай коммиты/push без явной просьбы пользователя.
```

---

## Структура репозитория

```
/local/php_interface/
  init.php          — обработчики событий
  test_init.php     — эталон ex2-591 для тренировки
  ex2_config.php    — (создать по гайду) константы
  lang/ru/init.php  — (создать) фразы

/local/templates/ex2_type4/
  header.php
  components/.../catalog.section/furniture/
    result_modifier.php
    component_epilog.php  — проверить наличие
    template.php

EXAM2_GUIDE.md      — полные решения всех заданий
.cursor/rules/      — правило bitrix-d7-only (учебное, на экзамене D7+legacy OK)
```

---

## Git

```bash
git clone git@github.com:anshnine/bitrixEx2.git
cd bitrixEx2
git pull   # перед работой
git add local/ EXAM2_GUIDE.md
git commit -m "описание"
git push
```

---

## Что ещё не доделано (ориентир для AI)

- [ ] ex2_config.php + lang/ru/init.php
- [ ] ex2-591: Ex2ReviewHandler — static onBefore/onAfter, EventLogTable, ex2_590
- [ ] ex2-601, 611, 631, 191 в init.php
- [ ] ex2-581: component_epilog в catalog.section (не element), lang для «Рецензии:»
- [ ] Убрать debug() с экзаменационного кода
- [ ] В конце: кеш включён
