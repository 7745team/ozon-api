<?php

namespace Team7745\OzonApi\Helpers;

use Team7745\OzonApi\Exceptions\OzonApiException;
use Team7745\OzonApi\Models\OzonTask;
use Team7745\OzonApi\OzonApi;

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

        if(!isset($data['result']['items'])) {
            throw new OzonApiException(sprintf(
                'Ozon API product/list: некорректный ответ для клиента %s: %s',
                $this->ozonApi->getClientId(),
                is_string($response) && $response !== '' ? mb_substr($response, 0, 500) : var_export($response, true)
            ));
        }

        $items = $data['result']['items'];
        $total = $data['result']['total'] ?? count($items);
        $lastId = $data['result']['last_id'] ?? '';

        $left = $left ?? $total;
        foreach ($items as $item) {
            $resultArray[$item['offer_id']] = $item['product_id'];
        }
        $left = $left - count($items);

        // count($items) и last_id в условии — защита от бесконечной рекурсии,
        // если total в ответе не согласуется с фактическим числом позиций
        if ($left > 0 && $lastId !== '' && count($items) > 0) {
            $resultArray = self::getImportedProductsIdsRecursively($resultArray, $lastId, $left);
        }

        return $resultArray;
    }

    protected static function ozonApiResponseToArray($ozonApiResponse): array
    {
        if (!is_string($ozonApiResponse) || $ozonApiResponse === '') {
            return [];
        }

        $data = json_decode($ozonApiResponse, true);

        return is_array($data) ? $data : [];
    }
}
