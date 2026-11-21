# Bazarik — інтернет-магазин bazarik.com.ua

Інструкції для Claude та інших AI-асистентів, які працюють з цим репозиторієм.

## Продукт
- Магазин недорогих товарів, які вже лежать на складі в Україні: «ціни як на Temu, але завтра у вас».
- Ядро аудиторії — жінки 25–45, ~60% покупок з телефона. **Спершу верстаємо під телефон.**
- Головні обіцянки (мають бути видні на кожному екрані): склад в Україні, відправка в день замовлення до [15:00], оплата після огляду (накладений платіж), повернення 14 днів, гарантія [12] міс.
- Прототип: 60 екранів (32 мобільні, 28 десктоп). Назви екранів — MobileHome, MobileCatalog, MobileProduct, MobileApparel, MobileCart, MobileCheckout, StateSuccess, StateTracking, AuthPhone/AuthCode/AuthName, AccountHome/Orders/Order/Favorites/Addresses, QuickBuyGuest/QuickBuyUser, StateSizeSheet, StateAddedToCart, StatePaymentFailed, StateCartEmpty, StateOutOfStock, State404, StateLoading.
- Тексти — українською, прості й чесні, без тиску й «крикливих» знижок.

## Стек
- Laravel 13, PHP 8.4, MySQL 8.4, Redis — локально через Laravel Sail (Docker).
- Вітрина: Blade + Livewire 4 + Alpine.js + Tailwind CSS 4.
- Адмінка: Filament 5 на `/admin` (guard `web`, модель `User`).
- Покупці: окрема модель `Customer`, guard `customer`, вхід за телефоном + код з SMS, без пароля.

## Домовленості
- Гроші — `decimal(10,2)` у гривнях; виводимо через хелпер `money()` → «1 499 ₴».
- Умови магазину, які ще погоджуються (поріг безкоштовної доставки, час відправки, гарантія) — тільки через `config('shop.*')`, не хардкодити.
- Статуси й способи — PHP enum-и в `app/Enums` (з `HasLabel` для Filament).
- Варіант товару (`ProductVariant`) = колір × розмір із залишком. У товару без варіантів є рівно один варіант.
- У замовленні зберігаємо знімок товару (`order_items.name`, `price`, `variant_label`).
- Кольори бренду: `brand` #FF7A1A, `brand-dark` #D95E0A, `ink` #1F1F24. Шрифт Nunito (лежить локально, через npm).
- Команди запускаємо через Sail: `./vendor/bin/sail artisan …`, `./vendor/bin/sail npm …`.
