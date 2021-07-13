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

    protected static function fakeHttp()
    {
        Http::fake([

            '*/categories' => Http::response('[
                {
                    "id": "251464",
                    "name": "Ev Dekorasyon"
                },
                {
                    "id": "251470",
                    "name": "Duvar Sticker"
                },
                {
                    "id": "251474",
                    "name": "Çocuk Genç Odası Duvar Sticker"
                },
                {
                    "id": "300566",
                    "name": "Geometrik Sticker"
                },
                {
                    "id": "300567",
                    "name": "Hayvan Figürlü Sticker"
                },
                {
                    "id": "300570",
                    "name": "Modern Sticker"
                },
                {
                    "id": "300586",
                    "name": "Salon Oturma Odası Sticker"
                }
            ]'),

            '*/products' => Http::response('[
                {
                    "productId": "5689755",
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
            ]'),

            '*/product' => Http::response('[{
                "id": "5689755",
                "description": "<strong>KAPIDA ÖDEME için Whatsapp iletişim hattımız:&nbsp;+90 545 230 16 16\'dır. Ürünün fotoğrafını çekip whatsappdan bize gönderebilirsiniz.<br>\n<br>\nSpor Evde Yapılır!</strong><br>\nEvinizin her odasında ve her köşesinde rahatlıkla kullanabileceğiniz her yaşa uygun boks torbaları ile pandemi sürecinde sizde hareketsiz kalmayın.<br>\n<br>\n<strong>Ürün Özellikleri</strong><br>\n- 16+ Yaşa Uygun<br>\n- 15kg ağırlık<br>\n- Sağlığa zararlı madde içermez<br>\n- Dayanıklı ve zincirli<br>\n- Özel iç doldurma<br>\n- Kırmızı Renk<br>\n- 80cm",
                "images": [
                    "https://dmih5ui1qqea9.cloudfront.net/pictures_large/SporumEvde_7b0b6bdff4a1b4d592d3101be8b3c9c4.jpg"
                ],
                "option_type": 0,
                "options": [
                    {
                        "title": "A",
                        "price": "(+100,00 TL)"
                    },
                    {
                        "title": "B",
                        "price": "Ücretsiz"
                    },
                    {
                        "title": "C",
                        "price": "(+300,00 TL)"
                    }
                ],
                "variations": [
                    {
                        "title": "Renk",
                        "options": [
                        "S",
                        "M",
                        "L",
                        "XL"
                    ]
                    },
                    {
                        "title": "Renk",
                        "options": [
                        "KIRMIZI",
                        "SARI",
                        "MAVİ",
                        "YEŞİL"
                    ]
                    }
                ]
            }]'),

        ]);
    }

    public function test_can_get_categories()
    {
        self::fakeHttp();

        $this->bot = new ShprBot(null, "Test_Shop");

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

        self::fakeHttp();

        $this->bot = new ShprBot(null, "Test_Shop");

        $this->bot->setCategories($categories);

        $categories = $this->bot->getCategories();

        $this->bot->findProductsOfCategory($categories[0]);

        $this->assertNotEmpty($categories[0]->products);

        $this->assertContainsOnlyInstancesOf(Product::class, $categories[0]->products);
    }

    public function test_can_get_product_details()
    {
        self::fakeHttp();

        $this->bot = new ShprBot(null, "Test_Shop");

        $this->bot->findCategories();

        $categories = $this->bot->getCategories();

        $this->bot->findProductsOfCategory($categories[0]);

        $this->bot->findProductsDetails($categories[0]->products);

        $this->assertNotEmpty($categories);

        $this->assertContainsOnlyInstancesOf(Category::class, $categories);

        $this->assertContainsOnlyInstancesOf(ProductOption::class, $categories[0]->products[0]->options);

        $this->assertContainsOnlyInstancesOf(ProductVariation::class, $categories[0]->products[0]->variations);
    }
}
