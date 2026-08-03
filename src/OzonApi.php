<?php

namespace Tdkomplekt\OzonApi;

use Tdkomplekt\OzonApi\Models\OzonProduct;

class OzonApi
{
    protected string $language = 'DEFAULT';
    protected int $importLimit = 100;
    protected int $productListLimit = 1000;

    protected string $clientId;
    protected string $apiKey;

    public function __construct($clientId = null, $apiKey = null)
    {
        $this->clientId = $clientId ?? config('ozon-api.client_id');
        $this->apiKey = $apiKey ?? config('ozon-api.api_key');
    }

    public function getClientId()
    {
        return $this->clientId;
    }

    public function getCategoriesTree()
    {
        $url = 'https://api-seller.ozon.ru/v1/description-category/tree';
        $data = [
            'language' => $this->language,
        ];
        return $this->sendRequest($url, $data);
    }

    public function getCategoryTree($categoryId)
    {
        $url = 'https://api-seller.ozon.ru/v2/category/tree';

        $data = [
            'category_id' => $categoryId,
            'language' => $this->language,
        ];

        return $this->sendRequest($url, $data);
    }

    public function getCategoryAttributes($categoryId, $typeId)
    {
        $url = 'https://api-seller.ozon.ru/v1/description-category/attribute';

        $data = [
            'description_category_id' => $categoryId,
            'language' => $this->language,
            'type_id' => $typeId,
        ];

        return $this->sendRequest($url, $data);
    }

    public function getCategoryAttributeValues($categoryId, $attributeId, $typeId, $latsValueId = 0, $limit = 5000)
    {
        $url = 'https://api-seller.ozon.ru/v1/description-category/attribute/values';

        $data = [
            'attribute_id' => $attributeId,
            'description_category_id' => $categoryId,
            'language' => $this->language,
            'last_value_id' => $latsValueId,
            'limit' => $limit,
            'type_id' => $typeId,
        ];

        return $this->sendRequest($url, $data);
    }

    public function getProductInfo(?string $offerId = null, ?int $ozonProductId = null, ?int $ozonSku = null)
    {
        $url = 'https://api-seller.ozon.ru/v3/product/info';

        $data = [
            'offer_id' => $offerId ?: '',
            'product_id' => $ozonProductId ?: 0,
            'sku' => $ozonSku ?: 0,
        ];

        return $this->sendRequest($url, $data);
    }

    public function getProductInfoAttributes(
        array $offerIds,
        array $ozonProductIds,
        string $lastId,
        int $limit,
        string $sortBy,
        string $sortDir,
    )
    {
        $url = 'https://api-seller.ozon.ru/v4/product/info/attributes';

        $data['filter'] = [
            'visibility' => 'ALL',
        ];

        if ($offerIds) {
            $data['filter']['offer_id'] = $offerIds;
        }

        if ($ozonProductIds) {
            $data['filter']['product_id'] = $ozonProductIds;
        }

        $data['last_id']  = $lastId;
        $data['limit']    = $limit;
        $data['sort_by']  = $sortBy;
        $data['sort_dir'] = $sortDir;

        return $this->sendRequest($url, $data);
    }

    public function getProductImportInfo($taskId)
    {
        $url = 'https://api-seller.ozon.ru/v1/product/import/info';

        $data = [
            'task_id' => $taskId,
        ];

        return $this->sendRequest($url, $data);
    }

    public function importProduct(OzonProduct $ozonProduct)
    {
        return $this->importProducts(collect([$ozonProduct]));
    }

    public function importProducts(\Illuminate\Support\Collection $ozonProducts)
    {
        $url = 'https://api-seller.ozon.ru/v3/product/import';

        if(count($ozonProducts) > $this->importLimit) {
            dd('error import limit > 100'); // todo throw exception
        }

        $data = [
            'items' => $ozonProducts->map(function ($ozonProduct) {
                return $this->getProductData($ozonProduct);
            })->toArray()
        ];

        return $this->sendRequest($url, $data);
    }

    /**
     * @param object $ozonProduct
     * @return bool|string
     */
    public final function importImages(object $ozonProduct)
    {
        $url = 'https://api-seller.ozon.ru/v1/product/pictures/import';

        $data['images'] = $ozonProduct->images;
        $data['images360'] = [];
        $data['product_id'] = $ozonProduct->product_id;

        return $this->sendRequest($url, $data);
    }

    public function getProductList($lastId = null)
    {
        $url = 'https://api-seller.ozon.ru/v3/product/list';

        $data = [
            "filter" => [
                "offer_id" => [],
                "product_id" => [],
                "visibility" => "ALL"
            ],
            "last_id" => $lastId ?? "",
            "limit" => $this->productListLimit
        ];

        return $this->sendRequest($url, $data);
    }

    public function getProductData(OzonProduct $ozonProduct): array // todo move in OzonProduct
    {
        return [
            'attributes' => $ozonProduct->getAttribute('attributes'),
            "complex_attributes" => $ozonProduct->getAttribute('complex_attributes'),
            "type_id" => (int)$ozonProduct->getAttribute('type_id'),

            "offer_id" => $ozonProduct->getAttribute('offer_id'),
            "description_category_id" => $ozonProduct->getAttribute('category_id'),
            "barcode" => $ozonProduct->getAttribute('barcode') ?? '',
            "name" => $ozonProduct->getAttribute('name'),

            "old_price" => (string) $ozonProduct->getAttribute('old_price') ?? '',
            "price" => (string) $ozonProduct->getAttribute('price') ?? '',
            "premium_price" => (string) $ozonProduct->getAttribute('premium_price') ?? '',
            "vat" => (string) $ozonProduct->getAttribute('vat') ?? "0.2",

            "weight" => $ozonProduct->getAttribute('weight'),
            "weight_unit" => $ozonProduct->getAttribute('weight_unit') ?? "g",

            "depth" => $ozonProduct->getAttribute('depth'),
            "height" => $ozonProduct->getAttribute('height'),
            "width" => $ozonProduct->getAttribute('width'),
            "dimension_unit" => $ozonProduct->getAttribute('dimension_unit') ?? "mm",

            "primary_image" => $ozonProduct->getAttribute('primary_image') ?? "",
            "images" => $ozonProduct->getAttribute('images') ?? [],
            "images360" => $ozonProduct->getAttribute('images360') ?? [],
            "color_image" => $ozonProduct->getAttribute('color_image') ?? "",
            "pdf_list" => $ozonProduct->getAttribute('pdf_list') ?? []
        ];
    }

    protected function sendRequest($url, ?array $data = null)
    {
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        if ($data) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $headers = array(
            "X-Custom-Header: value",
            "Content-Type: application/json",
            "Client-Id: ".$this->clientId,
            "Api-Key: ".$this->apiKey,
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }
}
