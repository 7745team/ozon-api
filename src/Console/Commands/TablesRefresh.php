<?php

namespace Team7745\OzonApi\Console\Commands;

use Artisan;
use DB;
use Team7745\OzonApi\Base\Command;

class TablesRefresh extends Command
{
    protected $signature = 'ozon:tables-refresh';

    public function handle()
    {
        DB::table('migrations')->where('migration', '2022_02_04_150000_create_ozon_tables')->delete();

        Artisan::call('migrate', [
            '--path' => 'vendor/7745team/ozon-api/database/migrations/2022_02_04_150000_create_ozon_tables.php'
        ]);
    }
}
