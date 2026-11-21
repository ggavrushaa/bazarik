<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Пошук по назві товару й категорії з урахуванням варіантів написання:
 * павербанк / повербанк / УМБ / power bank → ті самі товари.
 *
 * Запит розбиваємо на групи слів: кожна група — це «будь-яке з цих написань»,
 * а всі групи мають знайтися в товарі (AND). Порівняння — LIKE без урахування регістру.
 */
final class Search
{
    /**
     * Синоніми й типові помилки. Перше значення — як показуємо покупцю
     * («Показуємо результати для «павербанк»»), решта — «шукали також».
     */
    public const SYNONYMS = [
        ['павербанк', 'повербанк', 'УМБ', 'power bank', 'powerbank', 'павер банк', 'повер банк', 'зовнішній акумулятор', 'портативний акумулятор'],
        ['худі', 'худи', 'hoodie', 'толстовка', 'кенгурушка'],
        ['світшот', 'свитшот', 'свічшот', 'sweatshirt'],
        ['футболка', 'футболки', 'футболку', 't-shirt', 'tshirt'],
        ['джогери', 'джоггери', 'джоггеры', 'джогеры', 'joggers'],
        ['термос', 'thermos'],
        ['термочашка', 'термокружка', 'термогорнятко', 'термостакан'],
        ['плед', 'pled', 'покривало'],
        ['лампа', 'лампу', 'lamp', 'світильник'],
        ['ліхтар', 'ліхтарик', 'фонарик', 'фонарь'],
    ];

    /** Латиниця ↔ кирилиця на одних клавішах (ЙЦУКЕН ↔ QWERTY) */
    private const LAYOUT = [
        'q' => 'й', 'w' => 'ц', 'e' => 'у', 'r' => 'к', 't' => 'е', 'y' => 'н', 'u' => 'г', 'i' => 'ш', 'o' => 'щ', 'p' => 'з', '[' => 'х', ']' => 'ї',
        'a' => 'ф', 's' => 'і', 'd' => 'в', 'f' => 'а', 'g' => 'п', 'h' => 'р', 'j' => 'о', 'k' => 'л', 'l' => 'д', ';' => 'ж', "'" => 'є',
        'z' => 'я', 'x' => 'ч', 'c' => 'с', 'v' => 'м', 'b' => 'и', 'n' => 'т', 'm' => 'ь', ',' => 'б', '.' => 'ю',
    ];

    /**
     * display — що показати в «Результати для «…»», also — інші написання, groups — умови пошуку.
     *
     * @return array{display: string, also: list<string>, groups: list<list<string>>}
     */
    public static function parse(string $query): array
    {
        $text = self::normalize($query);
        $groups = [];
        $display = $text;
        $also = [];

        foreach (self::SYNONYMS as $variants) {
            // Довші варіанти першими, щоб «power bank» не розпався на два слова
            $sorted = $variants;
            usort($sorted, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

            foreach ($sorted as $variant) {
                $pattern = '/(?<![\p{L}\p{N}])'.preg_quote(mb_strtolower($variant), '/').'\p{L}{0,3}(?![\p{L}\p{N}])/u';
                if (preg_match($pattern, $text)) {
                    $text = trim((string) preg_replace($pattern, ' ', $text, 1));
                    $display = (string) preg_replace($pattern, mb_strtolower($variants[0]), $display, 1);
                    $groups[] = array_values(array_unique(array_map(mb_strtolower(...), $variants)));
                    $also = array_merge($also, array_slice(array_values(array_filter(array_slice($variants, 1), fn ($v) => mb_strtolower($v) !== mb_strtolower($variant))), 0, 3));
                    break;
                }
            }
        }

        foreach (preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) as $word) {
            if (mb_strlen($word) < 2 && ! ctype_digit($word)) {
                continue;
            }
            $groups[] = self::wordForms($word);
        }

        return ['display' => trim((string) preg_replace('/\s+/u', ' ', $display)), 'also' => $also, 'groups' => $groups];
    }

    /** Обмежує запит товарами, що відповідають усім групам */
    public static function apply(Builder $query, array $groups): Builder
    {
        if ($groups === []) {
            return $query->whereRaw('1 = 0');
        }

        foreach ($groups as $terms) {
            $query->where(function (Builder $w) use ($terms) {
                foreach ($terms as $term) {
                    foreach (self::caseForms($term) as $form) {
                        $like = '%'.addcslashes($form, '%_\\').'%';
                        $w->orWhere('name', 'like', $like)
                            ->orWhereHas('category', fn (Builder $c) => $c->where('name', 'like', $like));
                    }
                }
            });
        }

        return $query;
    }

    /** «gfdth,fyr» → «павербанк», «зщцук ифтл» → «power bank». null — якщо міняти нічого */
    public static function swapLayout(string $query): ?string
    {
        $text = mb_strtolower(trim($query));
        if ($text === '') {
            return null;
        }

        $map = preg_match('/[a-z]/', $text) && ! preg_match('/[а-яіїєґ]/u', $text)
            ? self::LAYOUT
            : (preg_match('/[а-яіїєґ]/u', $text) && ! preg_match('/[a-z]/', $text) ? array_flip(self::LAYOUT) : null);

        if ($map === null) {
            return null;
        }

        $swapped = strtr($text, $map);

        return $swapped !== $text ? $swapped : null;
    }

    private static function normalize(string $query): string
    {
        $text = mb_strtolower(mb_substr(trim($query), 0, 100));
        $text = str_replace(['’', 'ʼ', '`', 'ё', 'ы', 'э'], ["'", "'", "'", 'е', 'и', 'е'], $text);
        $text = (string) preg_replace('/[^\p{L}\p{N}\s\'\-.,]/u', ' ', $text);

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /** Слово й простий «корінь»: футболки → футболк, 20000 → «20 000» як у назвах */
    private static function wordForms(string $word): array
    {
        $word = trim($word, " '-.,");
        $forms = [$word];

        if (ctype_digit($word) && strlen($word) >= 4) {
            $forms[] = number_format((int) $word, 0, '', ' ');
        } elseif (mb_strlen($word) >= 5) {
            $stem = (string) preg_replace('/(ами|ями|ів|ях|ах|ам|ям|ий|ій|ою|ею|[аеиіоуюяїєьй])$/u', '', $word);
            if (mb_strlen($stem) >= 4) {
                $forms[] = $stem;
            }
        }

        return array_values(array_unique(array_filter($forms, fn ($f) => $f !== '')));
    }

    /**
     * SQLite (тести) порівнює без регістру лише латиницю, тож для кирилиці
     * додаємо форми з великої літери й великими літерами — у MySQL це те саме.
     */
    private static function caseForms(string $term): array
    {
        return array_values(array_unique([
            $term,
            mb_strtoupper(mb_substr($term, 0, 1)).mb_substr($term, 1),
            mb_strtoupper($term),
        ]));
    }
}
