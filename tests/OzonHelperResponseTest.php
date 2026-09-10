<?php

namespace Team7745\OzonApi\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Team7745\OzonApi\Exceptions\OzonApiException;
use Team7745\OzonApi\Helpers\OzonHelper;
use Team7745\OzonApi\OzonApi;

class OzonHelperResponseTest extends BaseTestCase
{
    public function testGetTaskIdFromResponseSurvivesNonJsonResponse()
    {
        $this->assertNull(OzonHelper::getTaskIdFromResponse('404 page not found'));
        $this->assertNull(OzonHelper::getTaskIdFromResponse(false));
        $this->assertNull(OzonHelper::getTaskIdFromResponse(null));
        $this->assertNull(OzonHelper::getTaskIdFromResponse(''));
    }

    public function testGetTaskIdFromResponseReadsTaskId()
    {
        $this->assertSame(123, OzonHelper::getTaskIdFromResponse('{"result":{"task_id":123}}'));
    }

    public function testGetAllImportedProductsIdsCollectsAllPages()
    {
        $helper = new OzonHelper(new FakeOzonApi([
            '{"result":{"items":[{"offer_id":"A-1","product_id":11}],"total":2,"last_id":"abc"}}',
            '{"result":{"items":[{"offer_id":"B-2","product_id":22}],"total":2,"last_id":"def"}}',
        ]));

        $this->assertSame(['A-1' => 11, 'B-2' => 22], $helper->getAllImportedProductsIds());
    }

    public function testGetAllImportedProductsIdsOnEmptyShop()
    {
        $helper = new OzonHelper(new FakeOzonApi([
            '{"result":{"items":[],"total":0,"last_id":""}}',
        ]));

        $this->assertSame([], $helper->getAllImportedProductsIds());
    }

    public function testGetAllImportedProductsIdsThrowsOnApiError()
    {
        $helper = new OzonHelper(new FakeOzonApi([
            '{"code":7, "message":"Company is blocked, please contact support"}',
        ]));

        $this->expectException(OzonApiException::class);
        $this->expectExceptionMessage('Company is blocked');

        $helper->getAllImportedProductsIds();
    }

    public function testGetAllImportedProductsIdsThrowsOnNonJsonResponse()
    {
        $helper = new OzonHelper(new FakeOzonApi(['404 page not found']));

        $this->expectException(OzonApiException::class);
        $this->expectExceptionMessage('404 page not found');

        $helper->getAllImportedProductsIds();
    }

    public function testRecursionStopsWhenTotalExceedsActualItems()
    {
        // total врёт (3), позиций реально одна и last_id пуст —
        // не должно уйти в бесконечную рекурсию
        $helper = new OzonHelper(new FakeOzonApi([
            '{"result":{"items":[{"offer_id":"A-1","product_id":11}],"total":3,"last_id":""}}',
        ]));

        $this->assertSame(['A-1' => 11], $helper->getAllImportedProductsIds());
    }
}

class FakeOzonApi extends OzonApi
{
    protected array $responses;

    public function __construct(array $responses)
    {
        parent::__construct('test-client-id', 'test-api-key');
        $this->responses = $responses;
    }

    public function getProductList($lastId = null)
    {
        return array_shift($this->responses);
    }
}
