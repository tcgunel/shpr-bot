<?php

namespace TCGunel\ShprBot;

use Campo\UserAgent;
use Illuminate\Support\Facades\Http;
use TCGunel\ShprBot\Constants\OptionType;
use TCGunel\ShprBot\Models\Category;
use TCGunel\ShprBot\Models\Product;
use TCGunel\ShprBot\Models\ProductOption;
use TCGunel\ShprBot\Models\ProductVariation;

class ShprBot extends ShprBotClient
{
    use HandleErrors;

    protected $html_content;

    protected $categories;

    protected $shop;

    protected $http_client_options = [];

    protected $store = [
        "p5q4k4o4l4b4w21446o4s444m4a4k4j4f4j5m4p294n4u5q2o3q5v5o4n3x5x5540634p4j3n526r20616l4i4d484v5o4s516n2l4q5j453o4b4l4u553g2p4g4w5j4",
        "9YLMxbn@EWuWQaGrWNWep5VEZdcULA^nGfG9TTN@"
    ];

    protected $api = [
        "f4l416k4x523u2v2n4o5l4v5m2w5t5c4b4z344t5n5m2k4t5c4f546y5c4p434l4c416z3h5t5f4q2x5p4",
        "!hGiNT#aQqtr@$&v5N%@ZVLSHSNS^%U9R&J&%xc2",
    ];

    protected $product_detail = [
        "f4o4p4m4x5e404n226o4n4u2m4a4u5p4r5a4v504c4u5h4v2u394n416k3z5l4l5s4k516j3k51644j4p4j494r4h506w5m2v594g4b3c464g4m2r594",
        "bCg#Y6zcnTz2m%JmqWnvqtSQUaV&m@yYsSD3tgvM",
    ];

    /**
     * ShprBot constructor.
     * @param Http|null http_client
     * @param string $shop
     * @param array|null $http_client_options
     */
    public function __construct($http_client, string $shop, ?array $http_client_options = [])
    {
        $this->shop = $shop;

        $this->http_client_options = $http_client_options;

        parent::__construct($http_client);
    }

    public static function randomUserAgent()
    {
        return UserAgent::random([
            "os_type"     => ["Windows", "OS X"],
            "device_type" => ["Desktop"]
        ]);
    }

    public function get($url, $tries = 0, $max_tries = 10): string
    {
        $response = $this->http_client::withHeaders([
            "User-Agent"                => self::randomUserAgent(),
            "Pragma"                    => "no-cache",
            "Cache-Control"             => "no-cache",
            "Upgrade-Insecure-Requests" => "1",
            "Accept"                    => "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9",
            "Sec-Fetch-Site"            => "none",
            "Sec-Fetch-Mode"            => "navigate",
            "Sec-Fetch-User"            => "?1",
            "Sec-Fetch-Dest"            => "document",
            "Accept-Language"           => "en,en-US;q=0.9,tr;q=0.8,da;q=0.7",
        ])->withOptions($this->http_client_options)->get($url);

        if ($tries < $max_tries && (!$response->successful() || stripos($response->body(), "cloudflare") !== false)) {

            $tries++;

            return self::get($tries, $max_tries);

        } else if ($response->successful()) {

            return $response->body();

        }

        return "";
    }

    public function post($url, $data): string
    {
        $response = $this->http_client::asForm()->post($url, $data);

        if ($response->successful()) {

            return $response->body();

        }

        return "";
    }

    public function run()
    {
        $this->findCategories()->findProductsForEachCategory();
    }

    /**
     * @return Category[]
     */
    public function getCategories(): array
    {
        if (empty($this->categories)) {

            $this->findCategories();

        }

        return $this->categories;
    }

    /**
     * @param $categories
     * @return ShprBot
     */
    public function setCategories($categories): ShprBot
    {
        $this->categories = $categories;

        return $this;
    }

    public function findCategories(): ShprBot
    {
        $html_content = $this->get(strtr(Helper::d($this->store), ["%shop" => $this->shop]));

        $this->setMultilineContentToOneLiner($html_content);

        preg_match_all('/nav-link\scategorytab".*?data-id="(\d+)".*?>(.*?)</', $html_content, $matches);

        if (!empty($matches)) {

            foreach ($matches[1] as $k => $id) {

                if (!empty($matches[2][$k])) {

                    $category = new Category([
                        "id"       => $id,
                        "name"     => $matches[2][$k],
                        "products" => []
                    ]);

                    $this->categories[] = $category;

                }

            }

        }

        return $this;
    }

    public function findProductsOfCategory(Category $category)
    {
        $response = $this->post(Helper::d($this->api), array_merge([
            "shop"                => $this->shop,
            "category"            => $category->name,
        ], $this->http_client_options));

        $products = collect(json_decode($response, true));

        $category->products = $products->mapInto(Product::class)->toArray();
    }

    public function findProductsForEachCategory()
    {
        foreach ($this->getCategories() as $category) {

            $this->findProductsOfCategory($category);
            $this->findDetailsOfCategoryProduct($category);

        }
    }

    public function findDetailsOfCategoryProduct(Category $category)
    {
        foreach ($category->products as $product) {

            $this->findProductsDetails($product);

            sleep(rand(1, 2));
        }
    }

    public function findProductsDetails(Product $product)
    {
        $html_content = $this->get(strtr(Helper::d($this->product_detail), ["%id" => $product->id]));

        $this->setMultilineContentToOneLiner($html_content);

        $product->description = $this->findProductDescription($html_content);

        $product->images = $this->findProductImages($html_content);

        $product->options = $this->findOptions($html_content);

        if (!empty($product->options)) {

            $product->option_type = $this->findOptionType($html_content);

        }

        $product->variations = $this->findVariations($html_content);
    }

    public static function setMultilineContentToOneLiner(&$content)
    {
        $content = preg_replace('/\n/', '', $content);
        $content = preg_replace('/[\s]{2,}/', ' ', $content);
    }

    public static function findProductDescription($html_content): string
    {
        preg_match('/.*?id="tab-description".*?>(.*?)<div class="product-share/', $html_content, $matches);

        if (isset($matches[1]))
            return trim($matches[1]);

        return "";
    }

    public static function findProductImages($html_content): array
    {
        preg_match_all('/product__image swiper-lazy" data-src="(.*?)"/', $html_content, $matches);

        if (isset($matches[1]))
            return $matches[1];

        return [];
    }

    public static function findOptions($html_content): array
    {
        $options = [];

        preg_match_all('/<label for="option-section-\d".*?custom-control-description.*?>(.*?)<span.*?>(.*?)<\/span>/', $html_content, $matches);

        if (isset($matches[1]) && isset($matches[2])) {

            foreach ($matches[1] as $k => $title) {

                if (!empty($matches[2][$k])) {

                    $product_option = new ProductOption([
                        "title" => $title,
                        "price" => $matches[2][$k]
                    ]);

                    $options[] = $product_option;

                }

            }

        }

        return $options;
    }

    public static function findOptionType($html_content): string
    {
        preg_match('/product-info__checkboxes input:checkbox.*?not.*?checked.*?false/', $html_content, $matches);

        if (isset($matches[0]) && !empty($matches[0]))
            return OptionType::SINGLE_CHOICE;

        return OptionType::MULTIPLE_CHOICE;
    }

    public static function findVariations($html_content): array
    {
        $variations = [];

        preg_match_all('/select\w+VariationGroup.*?>(.*?)<(.*?)<\/select/', $html_content, $matches);

        if (isset($matches[1]) && isset($matches[2]) && count($matches[1]) === count($matches[2])) {

            foreach ($matches[1] as $k => $title) {

                if (!empty($matches[2][$k])) {

                    $product_variation = new ProductVariation([
                        "title"   => $title,
                        "options" => []
                    ]);

                    preg_match_all('/<option value="\d+">(.*?)<\/option>/', $matches[2][$k], $options);

                    if (isset($options[1]) && !empty($options[1])) {

                        foreach ($options[1] as $option) {

                            $product_variation->options[] = $option;

                        }

                        $variations[] = $product_variation;

                    }

                }

            }

        }

        return $variations;
    }
}
