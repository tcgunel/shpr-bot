<?php

namespace TCGunel\ShprBot;

use Illuminate\Support\Facades\Http;
use TCGunel\ShprBot\Models\Category;
use TCGunel\ShprBot\Models\Product;
use TCGunel\ShprBot\Models\ProductOption;
use TCGunel\ShprBot\Models\ProductVariation;

class ShprBot extends ShprBotClient
{
    use HandleErrors;

    protected $categories;

    protected $shop;

    protected $http_client_options = [];

    protected $api_key = "";

    protected $api = [
        "f4l416k4x523u2v2n4o5l4v5m2w5t5c4b4z344t5n5m2k4t5c4f546y5c4p434l4c416z3h5t5f4q2x5p4",
        "!hGiNT#aQqtr@$&v5N%@ZVLSHSNS^%U9R&J&%xc2",
    ];

    /**
     * ShprBot constructor.
     * @param Http|null http_client
     * @param string $shop
     * @param array|null $http_client_options
     */
    public function __construct($http_client, string $shop, ?array $http_client_options = [], ?string $api_key = null)
    {
        $this->shop = $shop;

        $this->api_key = $api_key;

        $this->http_client_options = $http_client_options;

        parent::__construct($http_client);
    }

    /**
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function post($url, $data)
    {
        $response = $this->http_client::withHeaders([
            "x-api-key" => $this->api_key
        ])->timeout(14400)->connectTimeout(14400)->withOptions([
            'debug' => false,
        ])->post($url, $data);

        $response->throw();

        return $response->json();
    }

    /**
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function run()
    {
        $this->findCategories()->findProductsForEachCategory();
    }

    /**
     * @return Category[]
     * @throws \Illuminate\Http\Client\RequestException
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

    /**
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function findCategories(): ShprBot
    {
        $data = [
            "shop" => $this->shop,
        ];

        $categories = $this->post(Helper::d($this->api) . "/categories", $data);

        foreach ($categories as $category) {

            $this->categories[] = new Category([
                "id"       => $category["id"],
                "name"     => $category["name"],
                "products" => []
            ]);

        }

        return $this;
    }

    /**
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function findProductsOfCategory(Category $category)
    {
        $data = [
            "shop"     => $this->shop,
            "category" => $category->name,
        ];

        $response = $this->post(Helper::d($this->api) . "/products", $data);

        $products = collect($response);

        $category->products = $products->mapInto(Product::class)->toArray();
    }

    /**
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function findProductsForEachCategory()
    {
        foreach ($this->getCategories() as $category) {

            $this->findProductsOfCategory($category);
            $this->findProductsDetails($category->products);

        }
    }

    /**
     * @param Product[] $products
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function findProductsDetails(array $products)
    {
        $products = collect($products)->keyBy("id");

        $data = [
            "product_ids" => $products->keys(),
        ];

        $response = $this->post(Helper::d($this->api) . "/product", $data);

        $response = collect($response)->keyBy("id");

        foreach ($response as $k => $item) {

            $products[$k]->description = $item["description"];

            $products[$k]->images = $item["images"];

            $products[$k]->option_type = $item["option_type"];

            $products[$k]->options = $this->mapOptions($item["options"]);

            $products[$k]->variations = $this->mapVariations($item["variations"]);
        }
    }

    public static function mapOptions(array $options): array
    {
        $mapped_options = [];

        foreach ($options as $option) {

            $product_option = new ProductOption($option);

            $mapped_options[] = $product_option;

        }

        return $mapped_options;
    }

    public static function mapVariations(array $variations): array
    {
        $mapped_variations = [];

        foreach ($variations as $variation) {

            $product_variation = new ProductVariation($variation);

            $mapped_variations[] = $product_variation;

        }

        return $mapped_variations;
    }
}
