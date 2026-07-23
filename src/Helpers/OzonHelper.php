<?php

namespace Tdkomplekt\OzonApi\Helpers;

use Tdkomplekt\OzonApi\Models\OzonTask;
use Tdkomplekt\OzonApi\OzonApi;

class OzonHelper
{
    protected OzonApi $ozonApi;

    public function __construct(OzonApi $ozonApi)
    {
        $this->ozonApi = $ozonApi;
    }

    public static function saveTaskFromResponse($ozonApiResponse)
    {
        if ($ozonApiResponse) {
            $taskId = OzonHelper::getTaskIdFromResponse($ozonApiResponse);
            if($taskId) {
                OzonTask::firstOrCreate([
                    'id' =>  $taskId
                ]);
            }
        }
    }

    public static function getTaskIdFromResponse($ozonApiResponse)
    {
        $data = self::ozonApiResponseToArray($ozonApiResponse);

        return isset($data['result']) && isset($data['result']['task_id']) ? $data['result']['task_id'] : null ;
    }

    public function getTaskIdFromResponseAndSaveTask($ozonApiResponse)
    {
        $taskId = self::getTaskIdFromResponse($ozonApiResponse);
        if($taskId) {
            self::saveTaskFromResponse($ozonApiResponse);
        }

        return $taskId;
    }

    public function getAllImportedProductsIds(): array
    {
        return $this->getImportedProductsIdsRecursively();
    }

    protected function getImportedProductsIdsRecursively(array &$resultArray = [], ?string $lastId = null, ?int $left = null): array
    {
        $response = $this->ozonApi->getProductList($lastId ?? null);
        $data = $this::ozonApiResponseToArray($response);

        if(isset($data['result'])) {
            $items = $data['result']['items'];
            $total = $data['result']['total'];
            $lastId = $data['result']['last_id'];

            $left = $left ?? $total;
            foreach ($items as $item) {
                $resultArray[$item['offer_id']] = $item['product_id'];
            }
            $left = $left - count($items);

            if ($left > 0) {
                $resultArray = self::getImportedProductsIdsRecursively($resultArray, $lastId, $left);
            }
        }

        return $resultArray;
    }

    protected static function ozonApiResponseToArray($ozonApiResponse): array
    {
        return json_decode($ozonApiResponse, true);
    }
}
