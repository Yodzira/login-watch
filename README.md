# Login Watch

**[EN]** Know who enters your admin the second they do: admin login alerts, failed-attempt bursts (one calm alert after 10 tries — not a flood), new-admin creation and role changes. Telegram and email. No firewall, no lockouts — compatible with Wordfence.

**[RU]** Узнавайте о входах в админку в момент входа: алерты о входах администраторов, всплесках подбора паролей (один спокойный алерт после 10 попыток), создании новых админов и смене ролей. Telegram и email. Без фаервола и блокировок — совместим с Wordfence.

🔗 [**Скачать бесплатно / Download free**](https://github.com/Yodzira/login-watch/releases/latest/download/login-watch.zip)

## События

- 🔑 **Вход администратора** — логин, время, маскированный IP
- ⚠️ **Всплеск неудачных попыток** — один алерт на источник за окно 10 минут, «×N попыток»
- 🚨 **Новый администратор** / **смена роли на администратора** — классический ход взломщика
- 🔌 **Активация плагина** — в журнале

## Принципы

- Никогда ничего не блокирует — вас не залочит собственный плагин
- IP маскируется (последний октет → 0), журнал 30 дней
- Telegram (опционально) + email админа
- Совместим с Wordfence / Solid Security — это уведомитель, не фаервол
- Чистый uninstall: таблица, опции, крон стираются

## Установка / Install

1. Скачайте [`login-watch.zip`](https://github.com/Yodzira/login-watch/releases/latest/download/login-watch.zip)
2. WP-админка → **Плагины → Добавить новый → Загрузить плагин** → zip → Активировать
3. Меню **Login Watch** — журнал наполняется сразу; Telegram — по желанию

## Требования / Requirements

- WordPress 6.0+ (протестировано до 7.1), PHP 7.4+

## Качество / Quality

- PHPUnit (ядро): 5 тестов, 15 assertions ✅ (окна всплесков, подписи, маскировка IP, шаблоны)
- Интеграция на живом WP 7.1: 10/10 (входы, брут-волна, new admin, смена роли) ✅
- Официальный Plugin Checker: 0 errors (release build) ✅
- Uninstall: таблица/опции/крон стёрты ✅

## Лицензия / License

GPL-2.0-or-later (совместимо с WordPress).
