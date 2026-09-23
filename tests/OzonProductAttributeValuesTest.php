<?php

namespace Team7745\OzonApi\Tests;

use Team7745\OzonApi\Models\OzonProduct;

/**
 * Тестовый класс поднимает приложение: HandleExceptions Laravel превращает warning в
 * ErrorException, поэтому «Trying to access array offset on null» роняет тест, а не
 * проходит молча.
 */
class OzonProductAttributeValuesTest extends TestCase
{
    public function testReturnsValuesOfFilledAttribute()
    {
        $product = new OzonProduct([
            'attributes' => [
                ['id' => 85, 'values' => [['value' => 'TDK']]],
                ['id' => 9048, 'values' => [['value' => 'Model X']]],
            ],
        ]);

        $this->assertSame([['value' => 'Model X']], $product->getOzonAttributeValuesById(9048));
    }

    /**
     * Карточка товара в админке 7745 перебирает все атрибуты категории Ozon, а у товара
     * заполнена только часть из них: каждый незаполненный давал warning (59932).
     */
    public function testMissingAttributeGivesEmptyArray()
    {
        $product = new OzonProduct([
            'attributes' => [['id' => 85, 'values' => [['value' => 'TDK']]]],
        ]);

        $this->assertSame([], $product->getOzonAttributeValuesById(4180));
    }

    public function testProductWithoutAttributesGivesEmptyArray()
    {
        $this->assertSame([], (new OzonProduct)->getOzonAttributeValuesById(85));
        $this->assertSame([], (new OzonProduct(['attributes' => null]))->getOzonAttributeValuesById(85));
    }
}
