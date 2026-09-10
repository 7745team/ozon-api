<?php

namespace Team7745\OzonApi\Console\Commands;

use DB;
use Team7745\OzonApi\Base\Command;
use Team7745\OzonApi\Models\OzonAttribute;
use Team7745\OzonApi\Models\OzonBrand;
use Team7745\OzonApi\Models\OzonCategory;

class SyncOptions extends Command
{
    protected $signature = 'ozon:sync-options {category_id?} {attribute_id?} {type_id?}';

    protected array $commonAttributes = [
        85, // Бренд
        88, // Серии
        4389, // Страна-изготовитель
        6383, // Материал
        9461, // Коммерческий тип
        9782, // Класс опасности товара
        10096, // Цвет товара
        10400, // Гарантия
    ];

    public function handle()
    {
        $categoryId = $this->argument('category_id');
        $attributeId = $this->argument('attribute_id');
        $typeId = $this->argument('type_id');

        $startTime = now();

        if (!empty($categoryId) && !empty($attributeId) && !empty($typeId)) {
            $this->syncAttributeOptions($categoryId, $attributeId, $typeId);
        }

        if (isset($categoryId) && empty($attributeId) && empty($typeId)) {
            $ozonCategories = OzonCategory::where('parent_id', $categoryId)->where('type_id', '>', 0)->get();
            foreach ($ozonCategories as $category) {
                foreach ($category->attributes()->where('dictionary_id', '>', 0)->get() as $ozonAttribute) {
                    $this->syncAttributeOptions($category->parent_id, $ozonAttribute->id, $category->type_id);
                }
            }
        }

        if (empty($categoryId) && empty($attributeId) && empty($typeId)) {
            foreach (OzonCategory::where('last_node', 1)->where('type_id', '>', 0)->get() as $ozonCategory) {
                foreach ($ozonCategory->attributes()->where('dictionary_id', '>', 0)->get() as $ozonAttribute) {
                    $this->syncAttributeOptions($ozonCategory->parent_id, $ozonAttribute->id, $ozonCategory->type_id);
                }
            }
        }

        $endTime = now();

        return $this->success($startTime, $endTime);
    }


    protected function syncAttributeOptions($categoryId, $attributeId, $typeId)
    {
        $this->saveNextOptions($categoryId, $attributeId, $typeId);
    }

    protected function saveNextOptions($categoryId, $attributeId, $typeId, $lastValueId = 0, $limit = 5000)
    {
        $json = $this->ozonApi->getCategoryAttributeValues($categoryId, $attributeId, $typeId, $lastValueId, $limit);

        $ozonAttribute = OzonAttribute::find($attributeId);

        $dataArray = json_decode($json, true);

        if(isset($dataArray['result']) && count($dataArray['result']) > 0) {

            DB::table('ozon_attribute_options')->upsert(
                $dataArray['result'], 'id'
            );

            foreach ($dataArray['result'] as $value) {

                DB::table('ozon_category_attribute_option')->updateOrInsert([
                    'ozon_category_id' => in_array($attributeId, $this->commonAttributes) ?  0 : $typeId,
                    'ozon_attribute_id' => $ozonAttribute->id,
                    'ozon_attribute_option_id' => $value['id'],
                ]);
            }

            $lastValueId = last($dataArray['result'])['id'];

            if(isset($dataArray['has_next']) && $dataArray['has_next'] == true) {
                $this->saveNextOptions($categoryId, $attributeId, $typeId, $lastValueId, $limit);
            }
        }
    }
}
