<?php

namespace TCGunel\ShprBot\Tests\Unit;

use Illuminate\Support\Facades\Http;
use TCGunel\ShprBot\Constants\OptionType;
use TCGunel\ShprBot\Models\Category;
use TCGunel\ShprBot\Models\Product;
use TCGunel\ShprBot\Models\ProductOption;
use TCGunel\ShprBot\Models\ProductVariation;
use TCGunel\ShprBot\ShprBot;
use TCGunel\ShprBot\Tests\TestCase;

class ShprBotTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function test_can_get_categories()
    {
        $http_client = Http::fake(function ($request) {

            return Http::response(file_get_contents(__DIR__ . "/../ShopHomePage.html"));

        });

        $this->bot = new ShprBot($http_client, "");

        $categories = $this->bot->getCategories();

        $this->assertNotEmpty($categories);

        $this->assertContainsOnlyInstancesOf(Category::class, $categories);
    }

    public function test_can_get_products_by_category_name()
    {
        $categories = array_map(function ($category) {
            return new Category([
                "id"       => rand(9999, 99999),
                "name"     => $category,
                "products" => [],
            ]);
        }, ["Ev Dekorasyon"]);

        $response = '[
            {
                "productId": "6853334",
                "title": "Balıklar Dekoratif Duvar Sticker. (10 cm 30 adet)",
                "price": "29,90&nbsp;TL",
                "originalPrice": "75",
                "freeShipping": true,
                "new": false,
                "stock": "9000"
            },
            {
                "productId": "6849541",
                "title": "Gülen Yüz İfadeler Dekoratif Duvar Sticker. (20 cm 6 adet)",
                "price": "29,90&nbsp;TL",
                "originalPrice": "75",
                "freeShipping": true,
                "new": false,
                "stock": "5500"
            },
            {
                "productId": "6849042",
                "title": "Kirpik Puantiye Dekoratif Duvar Sticker. (7 cm kirpik  32 adet, 4 cm puantiye 81 adet)",
                "price": "29,90&nbsp;TL",
                "originalPrice": "75",
                "freeShipping": true,
                "new": false,
                "stock": "5500"
            },
            {
                "productId": "6823002",
                "title": "Büyük Uçan Balon Dekoratif Duvar Sticker. 11 Renk (60 cm 1 adet)",
                "price": "29,90&nbsp;TL",
                "originalPrice": "75",
                "freeShipping": true,
                "new": false,
                "stock": "5500"
            },
            {
                "productId": "6822939",
                "title": "Büyük Uçan Balon Dekoratif Duvar Sticker. 11 Renk (90 cm 1 adet)",
                "price": "29,90&nbsp;TL",
                "originalPrice": "75",
                "freeShipping": true,
                "new": false,
                "stock": "5500"
            },
            {
                "productId": "6787324",
                "title": "Mavi Filler Çiçek Desenli Dekoratif Duvar Sticker.(18,38,58 cm 3 Adet)",
                "price": "59,90&nbsp;TL",
                "originalPrice": "100",
                "freeShipping": true,
                "new": false,
                "stock": "500"
            }
        ]';

        $http_client = Http::fake(function ($request) use ($response) {

            return Http::response($response);

        });

        $this->bot = new ShprBot($http_client, "");

        $this->bot->setCategories($categories);

        $this->bot->run();

        $this->assertNotEmpty($this->bot->getCategories()[0]->products);

        $this->assertContainsOnlyInstancesOf(Product::class, $this->bot->getCategories()[0]->products);
    }

    public function test_can_find_product_description_from_html()
    {
        $description = '<p>TRC-Trafik marka TBK-08-00-00 Sevimli Mavi Baykuş Dekoratif Duvar Sticker. &nbsp;(10 cm 18 adet)<br/> Çocuklar için özel olarak üretilen bu ürünler.<br/> <br/> 150 mikron birinci kalite folyolara basılmaktadır.<br/> <br/> Sağlık açısından sakıncası olmayan su bazlı boyalarla Latex makinalarda yüksek kalite dijital baskı ile üretilmektedir.<br/> <br/> Zararlı solvent veya VOC içermez, havalandırma gerektirmez,koku yapmaz.<br/> <br/> Baskı ve kesim yapıldıktan sonra paketlenmiş bir halde kullanıma hazır olarak gönderilmektedir.<br/> <br/> Sudan etkilenmez yırtılmaz rahatlıkla silinebilir.<br/> <br/> Kendinden yapışkanlıdır.&nbsp;<br/> <br/> İstenildiğinde sökülebilir İz ve leke bırakmaz.</p>';

        $simple_product_detail_page = file_get_contents(__DIR__ . "/../SimpleProductDetailPage.html");

        ShprBot::setMultilineContentToOneLiner($simple_product_detail_page);

        $description_from_html = ShprBot::findProductDescription($simple_product_detail_page);

        $this->assertEquals($description, $description_from_html);
    }

    public function test_can_find_product_images_from_html()
    {
        $images = [
            "https://dmih5ui1qqea9.cloudfront.net/pictures_large/TRC_Sticker_dd4fa5b8e3a48b93583808fd323c1b5e.jpg",
            "https://dmih5ui1qqea9.cloudfront.net/pictures_large/TRC_Sticker_e2295cf6c4a4f2b36a739dd02972da76.jpg",
        ];

        $simple_product_detail_page = file_get_contents(__DIR__ . "/../SimpleProductDetailPage.html");

        ShprBot::setMultilineContentToOneLiner($simple_product_detail_page);

        $description_from_html = ShprBot::findProductImages($simple_product_detail_page);

        $this->assertEquals($images, $description_from_html);
    }

    public function test_can_find_options_from_product_detail_html()
    {
        $options = [
            new ProductOption([
                "title"  => "A",
                "price" => 100.0,
            ]),
            new ProductOption([
                "title"  => "B",
                "price" => 0.0,
            ]),
            new ProductOption([
                "title"  => "C",
                "price" => 300.0,
            ]),
        ];

        $html = file_get_contents(__DIR__ . "/../OptionsProductDetailPage.html");

        ShprBot::setMultilineContentToOneLiner($html);

        $options_found = ShprBot::findOptions($html);

        $this->assertEquals($options, $options_found);
    }

    public function test_can_find_option_type_from_product_detail_html()
    {
        $html = file_get_contents(__DIR__ . "/../OptionsProductDetailPage.html");

        ShprBot::setMultilineContentToOneLiner($html);

        $option_type_found = ShprBot::findOptionType($html);

        $this->assertContains((int)$option_type_found, [OptionType::MULTIPLE_CHOICE, OptionType::SINGLE_CHOICE]);
    }

    public function test_can_find_variations_from_product_detail_html()
    {
        $options = [
            new ProductVariation([
                "title"  => "Beden",
                "options" => ["S","M","L","XL"],
            ]),
            new ProductVariation([
                "title"  => "Renk",
                "options" => ["KIRMIZI", "SARI", "MAVİ", "YEŞİL"],
            ]),
        ];

        $html = file_get_contents(__DIR__ . "/../OptionsProductDetailPage.html");

        ShprBot::setMultilineContentToOneLiner($html);

        $variations_found = ShprBot::findVariations($html);

        $this->assertEquals($options, $variations_found);
    }
}
