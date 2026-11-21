<?php

namespace Tests\Feature;

use App\Support\Search;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public static function powerbankSpellings(): array
    {
        return [['павербанк'], ['повербанк'], ['УМБ'], ['power bank'], ['powerbank'], ['Павербанки'], ['gfdth,fyr']];
    }

    #[DataProvider('powerbankSpellings')]
    public function test_spelling_variants_find_the_same_powerbanks(string $query): void
    {
        $this->get(route('search', ['q' => $query]))
            ->assertOk()
            ->assertSee('Результати для «павербанк»', false)
            ->assertSee('4 товари')
            ->assertSee('Павербанк 10 000 мАг, компактний')
            ->assertSee('Павербанк 30 000 мАг, 65 Вт для ноутбука')
            ->assertDontSee('Термос 1 л');
    }

    public function test_search_is_case_insensitive_and_matches_word_forms(): void
    {
        $this->get(route('search', ['q' => 'ТЕРМОС']))->assertOk()->assertSee('Термос 1 л, нержавіюча сталь');
        $this->get(route('search', ['q' => 'футболки']))->assertOk()->assertSee('Футболка oversize, щільна')->assertSee('Футболки бавовняні, 2 шт');
        $this->get(route('search', ['q' => 'павербанк 20000']))->assertOk()
            ->assertSee('Павербанк 20 000 мАг з дисплеєм')
            ->assertDontSee('Павербанк 10 000 мАг, компактний');
    }

    public function test_no_results_shows_popular_queries_and_viber(): void
    {
        config(['shop.viber' => 'viber://chat?number=test']);

        $this->get(route('search', ['q' => 'кавоварка']))
            ->assertOk()
            ->assertSee('За запитом «кавоварка» нічого немає', false)
            ->assertSee('ЧАСТО ШУКАЮТЬ')
            ->assertSee('Спитати у Viber')
            ->assertSee('viber://chat?number=test', false)
            ->assertSee('Що беруть цього тижня');
    }

    public function test_empty_query_does_not_fail(): void
    {
        $this->get(route('search'))->assertOk()->assertSee('ЧАСТО ШУКАЮТЬ');
    }

    public function test_parse_reports_canonical_spelling_and_alternatives(): void
    {
        $parsed = Search::parse('повербанк');

        $this->assertSame('павербанк', $parsed['display']);
        $this->assertSame(['УМБ', 'power bank', 'powerbank'], $parsed['also']);
        $this->assertSame('павербанк', Search::swapLayout('gfdth,fyr'));
        $this->assertSame('power bank', Search::swapLayout('зщцук ифтл'));
    }
}
