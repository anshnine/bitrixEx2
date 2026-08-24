# Bitrix Exam №2 — подготовка

Репозиторий для переноса **кастомизаций** между ПК. Ядро Bitrix (`/bitrix/`, `/upload/`) в git не входит.

## Что в репозитории

| Путь | Назначение |
|------|------------|
| `local/` | `init.php`, шаблон `ex2_type4` |
| `EXAM2_GUIDE.md` | Шпаргалка по билету |
| `.cursor/rules/` | Правила для Cursor |
| `Ex21_TrainingMaterials/` | Материалы билета |

## Первый раз на этом ПК

```bash
git clone <URL-репозитория> BitrizExam2
cd BitrizExam2
```

Скопируй `local/` на сервер или в полную установку Bitrix.

## Ежедневно

```bash
git add local/ EXAM2_GUIDE.md
git commit -m "описание изменений"
git push
```

На другом ПК: `git pull`

## Подключить GitHub (один раз)

1. Создай **приватный** репозиторий на GitHub.
2. В корне проекта:

```bash
git remote add origin git@github.com:USER/BitrizExam2.git
git push -u origin main
```

SSH-ключ: https://docs.github.com/en/authentication/connecting-to-github-with-ssh

## Cursor

Новый чат → `@EXAM2_GUIDE.md` или `@local/php_interface/init.php`
