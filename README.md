 CoinFellow - Split Expenses Easily

<div align="center">

![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Vue.js](https://img.shields.io/badge/Vue.js-35495E?style=for-the-badge&logo=vue.js&logoColor=4FC08D)
![Telegram](https://img.shields.io/badge/Telegram-2CA5E0?style=for-the-badge&logo=telegram&logoColor=white)
![JWT](https://img.shields.io/badge/JWT-000000?style=for-the-badge&logo=JSON%20web%20tokens&logoColor=white)

* Приложение для учета общих расходов в Telegram*

</div>

---

 **О проекте**

CoinFellow - это современное Telegram Mini App для легкого учета общих расходов с друзьями, семьей или коллегами. Больше никаких споров "кто кому должен"!

 **Key Features**

-  **Групповые расходы** - Создавайте группы для разных мероприятий
-  **Умное распределение** - Автоматический расчет долгов
-  **Telegram интеграция** - Работайте прямо в мессенджере
-  **Категории расходов** - Упорядочивайте траты по типам
-  **Мультивалютность** - Поддержка разных валют
-  **Уведомления** - Своевременные напоминания о долгах

---

## 🛠 **Tech Stack**

### **Backend**
![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat-square&logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=flat-square&logo=laravel&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-005C84?style=flat-square&logo=mysql&logoColor=white)
![JWT](https://img.shields.io/badge/JWT-000000?style=flat-square&logo=JSON%20web%20tokens&logoColor=white)

### **Frontend**
![Vue.js](https://img.shields.io/badge/Vue.js-35495E?style=flat-square&logo=vue.js&logoColor=4FC08D)
![Telegram Web App](https://img.shields.io/badge/Telegram-2CA5E0?style=flat-square&logo=telegram&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=flat-square&logo=tailwind-css&logoColor=white)

### **DevOps**
![Docker](https://img.shields.io/badge/Docker-2CA5E0?style=flat-square&logo=docker&logoColor=white)
![GitHub Actions](https://img.shields.io/badge/GitHub_Actions-2088FF?style=flat-square&logo=github-actions&logoColor=white)

---

##  **Documentation**

### 🔗 **API Documentation**
-  [Postman Collection](https://lively-astronaut-179920.postman.co/workspace/API~1fbe64a2-bdba-4aa7-b5da-cae9c1d126f2/collection/27047596-adb81a45-106a-47af-8b30-2b8fefcc59ff?action=share&source=copy-link&creator=27047596)

###  **Project Management**
-  [Bitrix24 Tasks](https://b24-trzm16.bitrix24.ru/workgroups/group/2/tasks/)
-  [Telegram Support](https://t.me/aevonso)

---

##  **Quick Start**

### **Prerequisites**
- PHP 8.1+
- Composer 2.0+
- MySQL 8.0+
- Node.js 16+

### **Local Development**

#### 1. **Backend Setup (Laravel)**

```bash
# Клонируем репозиторий
git clone https://github.com/your-username/coinfellow.git
cd coinfellow/backend

# Устанавливаем PHP зависимости
composer install

# Копируем файл окружения и настраиваем
cp .env.example .env

# Генерируем ключ приложения
php artisan key:generate

# Создаем символическую ссылку для storage
php artisan storage:link
```

**Настройка .env файла:**
```env
APP_NAME=CoinFellow
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=coinfellow
DB_USERNAME=root
DB_PASSWORD=

TELEGRAM_BOT_TOKEN=your_telegram_bot_token_here
TELEGRAM_SECRET_TOKEN=your_webhook_secret

JWT_SECRET=your_jwt_secret_here
```

**Запуск миграций и сидов:**
```bash
# Запускаем миграции для создания таблиц в базе данных
php artisan migrate

# (Опционально) Заполняем базу тестовыми данными
php artisan db:seed

# Или запускаем миграции и сиды вместе
php artisan migrate --seed
```

**Запуск сервера:**
```bash
# Запускаем development сервер
php artisan serve
```






##  **Дополнительные команды**

```bash
# Генерация JWT секрета
php artisan jwt:secret

# Очистка кэша
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Запуск очередей
php artisan queue:work
```

Приложение будет доступно по адресу: `http://localhost:8000`
