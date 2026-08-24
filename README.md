# Bitrix Exam №2 — подготовка

Репозиторий для переноса **кастомизаций** между ПК. Ядро Bitrix (`/bitrix/`, `/upload/`) в git не входит.

## Что в репозитории

| Путь | Назначение |
|------|------------|
| `local/` | `init.php`, шаблон `ex2_type4` |
| `EXAM2_GUIDE.md` | Шпаргалка по билету |
| `CURSOR_PROMPT.md` | **Промпт для Cursor на другом ПК** |
| `.cursor/rules/` | Правила для Cursor |
| `Ex21_TrainingMaterials/` | Материалы билета |

## Первый раз на этом ПК

```bash
git clone git@github.com:anshnine/bitrixEx2.git
cd bitrixEx2
```

Скопируй `local/` на сервер или в полную установку Bitrix.

## Ежедневно

```bash
git add local/ EXAM2_GUIDE.md
git commit -m "описание изменений"
git push
```

На другом ПК: `git pull`

## GitHub

Remote: `git@github.com:anshnine/bitrixEx2.git`

SSH-ключ: https://docs.github.com/en/authentication/connecting-to-github-with-ssh

## Cursor

Новый чат → `@CURSOR_PROMPT.md` `@EXAM2_GUIDE.md`
