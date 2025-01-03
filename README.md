# Сокращатель ссылок

## Обзор
Плагин **Сокращатель ссылок** позволяет администраторам WordPress создавать и управлять короткими ссылками, отслеживать клики и просматривать подробную аналитику, такую как метки времени кликов, IP-адреса пользователей и источники переходов.

## Особенности
- Генерация коротких ссылок с настраиваемыми слагами.
- Автоматическая генерация уникальных слагов по умолчанию.
- Отслеживание кликов с деталями, такими как время, IP-адрес и источник.
- Просмотр журналов кликов для каждой короткой ссылки через интерфейс.
- Удаление или редактирование коротких ссылок.

---

## Установка
1. Скачайте zip файл и загрузите через установщик плагинов WordPress
2. Активируйте плагин через страницу Плагины в административной панели WordPress.

---

## Как использовать

### 1. Доступ к плагину
- Перейдите в Инструменты > Сокращатель ссылок в административном меню.

### 2. Создание короткой ссылки:
1. Заполните поля **Название**, **Полный URL длинной ссылки** и **Адрес короткой ссылки** (необязательно).
2. Нажмите кнопку **Сократить ссылку**.
3. Новая короткая ссылка появится в списке под формой.

### 3. Управление короткими ссылками:
- **Редактировать**: Нажмите на действие "Редактировать", чтобы изменить короткую ссылку.
- **Удалить**: Нажмите на действие "Удалить", чтобы удалить короткую ссылку. Подтвердите удаление в диалоговом окне.

### 4. Просмотр журналов переходов
- В таблице коротких ссылок кликните на имя ссылки, чтобы перейти к подробному журналу кликов.
- Журнал переходов отобрадает:
  - Дату перехода
  - IP
  - Referrer

---

## Структура кода

### Файлы
- **`custom-short-link-generator.php`**: Главный файл плагина, который инициализирует плагин.
- **`includes/admin-page.php`**: Обрабатывает административный интерфейс, создание коротких ссылок и отображение таблицы.
- **`includes/short-links-table.php`**: Реализует таблицу для управления короткими ссылками.
- **`includes/click-logging.php`**: Обрабатывает логирование переходов и логику перенаправлений.
- **`includes/short-links-cpt.php`**: Регистрирует кастомный тип записи `short_link`.
---



