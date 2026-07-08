<?php

namespace Tdkomplekt\OzonApi\Tests;

use Illuminate\Console\OutputStyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tdkomplekt\OzonApi\Console\Commands\TablesRefresh;
use Tdkomplekt\OzonApi\Facades\OzonApi as OzonApiFacade;
use Tdkomplekt\OzonApi\Helpers\OzonHelper;
use Tdkomplekt\OzonApi\Models\OzonAttribute;
use Tdkomplekt\OzonApi\Models\OzonCategory;
use Tdkomplekt\OzonApi\Models\OzonProduct;
use Tdkomplekt\OzonApi\Models\OzonTask;
use Tdkomplekt\OzonApi\OzonApi;

class PackageSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function testServiceIsRegisteredAsSingleton()
    {
        $service = $this->app->make('ozon-api');

        $this->assertInstanceOf(OzonApi::class, $service);
        $this->assertSame($service, $this->app->make('ozon-api'));
    }

    public function testConfigIsMerged()
    {
        $this->assertSame('test-client-id', config('ozon-api.client_id'));
        $this->assertSame('test-api-key', config('ozon-api.api_key'));
    }

    public function testFacadeResolves()
    {
        $this->assertSame('test-client-id', OzonApiFacade::getClientId());
    }

    public function testCommandsAreRegistered()
    {
        $commands = Artisan::all();

        foreach ([
            'ozon:tables-refresh',
            'ozon:sync-categories',
            'ozon:sync-attributes',
            'ozon:sync-options',
        ] as $name) {
            $this->assertArrayHasKey($name, $commands, "Command $name is not registered");
        }
    }

    public function testMigrationsCreateTables()
    {
        foreach ([
            'ozon_categories',
            'ozon_attributes',
            'ozon_category_attribute',
            'ozon_attribute_options',
            'ozon_category_attribute_option',
            'ozon_products',
            'ozon_tasks',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table $table is missing");
        }
    }

    public function testModelsWorkAgainstMigratedSchema()
    {
        $root = OzonCategory::create(['id' => 1, 'name' => 'Root', 'parent_id' => 0, 'type_id' => 0]);
        $child = OzonCategory::create(['id' => 2, 'name' => 'Child', 'parent_id' => 1, 'type_id' => 10]);

        $this->assertTrue($root->isParent());
        $this->assertSame('Root > Child', $child->getFullName());

        $attribute = OzonAttribute::create([
            'id' => 85,
            'name' => 'Бренд',
            'type' => 'String',
            'is_collection' => false,
            'dictionary_id' => 28732849,
        ]);

        DB::table('ozon_category_attribute')->insert([
            'ozon_category_id' => $child->id,
            'ozon_attribute_id' => $attribute->id,
            'is_required' => true,
            'group_id' => 0,
            'group_name' => '',
        ]);

        $this->assertTrue($child->containsAttributeId($attribute->id));

        $product = OzonProduct::create([
            'offer_id' => 'SKU-1',
            'category_id' => $child->id,
            'type_id' => 10,
            'name' => 'Test product',
            'price' => 99.9,
            'attributes' => [['id' => 85, 'values' => [['value' => 'TDK']]]],
            'images' => ['https://example.com/1.jpg'],
        ]);

        $this->assertIsArray($product->fresh()->images);
        $this->assertSame([['value' => 'TDK']], $product->getOzonAttributeValuesById(85));
    }

    public function testProductDataIsBuiltFromModel()
    {
        OzonCategory::create(['id' => 3, 'name' => 'Cat', 'parent_id' => 0, 'type_id' => 10]);

        $product = OzonProduct::create([
            'offer_id' => 'SKU-2',
            'category_id' => 3,
            'type_id' => 10,
            'name' => 'Another product',
            'price' => 10,
        ]);

        $data = $this->app->make('ozon-api')->getProductData($product);

        $this->assertSame('SKU-2', $data['offer_id']);
        $this->assertSame(10, $data['type_id']);
        $this->assertSame('10', $data['price']);
        $this->assertSame([], $data['images']);
    }

    public function testHelperSavesTaskFromResponse()
    {
        $response = json_encode(['result' => ['task_id' => 123]]);

        $this->assertSame(123, OzonHelper::getTaskIdFromResponse($response));

        OzonHelper::saveTaskFromResponse($response);

        $this->assertDatabaseHas('ozon_tasks', ['id' => 123]);
    }

    public function testCommandSuccessReportsWholePositiveSeconds()
    {
        $command = $this->app->make(TablesRefresh::class);
        $command->setLaravel($this->app);

        $output = new BufferedOutput();
        $command->setOutput(new OutputStyle(new ArrayInput([]), $output));

        $startTime = Carbon::parse('2026-01-01 00:00:00');
        $endTime = Carbon::parse('2026-01-01 00:00:05');

        $method = new ReflectionMethod($command, 'success');
        $method->setAccessible(true);
        $result = $method->invoke($command, $startTime, $endTime);

        $this->assertSame(CommandAlias::SUCCESS, $result);
        // Carbon 3 (Laravel 11+) возвращает из diffIn* знаковый float — в выводе
        // должны остаться целые неотрицательные секунды
        $this->assertStringContainsString('in 5 seconds', $output->fetch());
    }
}
