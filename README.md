# Bazarik

Інтернет-магазин [bazarik.com.ua](https://bazarik.com.ua). Laravel 13 · Livewire 4 · Filament 5 · Tailwind 4 · MySQL · Redis.

## Перший запуск (macOS, потрібен Docker Desktop)

```bash
cd ~/bazarik

# 1. Встановити PHP-залежності (одноразово, без локального PHP)
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html \
  laravelsail/php84-composer:latest composer install --ignore-platform-reqs

# 2. Налаштування
cp .env.example .env

# 3. Підняти контейнери (перший раз збирається кілька хвилин)
./vendor/bin/sail up -d

# 4. Ключ, база з прикладами товарів, посилання на storage
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail artisan storage:link

# 5. Фронтенд
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

Порада: додайте аліас у `~/.zshrc` — `alias sail='./vendor/bin/sail'`.

## Адреси

| Що | Адреса |
|---|---|
| Сайт | http://localhost |
| Адмінка | http://localhost/admin — `admin@bazarik.com.ua` / `password` |
| Пошта (Mailpit) | http://localhost:8025 |
| MySQL з Mac (TablePlus тощо) | `127.0.0.1:3306`, `sail` / `password`, база `bazarik` |

## Щоденна робота

```bash
sail up -d          # запустити
sail npm run dev    # Vite з hot reload
sail artisan …      # artisan
sail down           # зупинити
```

## Що далі (план)

1. Ресурси Filament: товари з варіантами й фото, категорії, підбірки, замовлення.
2. Вітрина за прототипом: каталог з фільтрами, картка товару й одягу, шторка розміру, кошик.
3. Оформлення без реєстрації, «Купити в 1 клік», сторінка «Замовлення прийнято».
4. Нова Пошта: пошук міст і відділень, ТТН, статуси.
5. Вхід за телефоном (SMS-код), кабінет, обране, адреси.
6. Онлайн-оплата (Apple Pay / Google Pay), стан «Оплата не пройшла».
