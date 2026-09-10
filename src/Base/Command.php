<?php

namespace Team7745\OzonApi\Base;

use Illuminate\Console\Command as ConsoleCommand;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Team7745\OzonApi\OzonApi;

class Command extends ConsoleCommand
{
    protected OzonApi $ozonApi;

    public function __construct()
    {
        parent::__construct();
        $this->ozonApi = app(OzonApi::class);
    }

    protected function success($startTime, $endTime): int
    {
        // Carbon 2 возвращает abs int, Carbon 3 (Laravel 11+) — знаковый float
        $seconds = (int) round(abs($endTime->diffInSeconds($startTime)));

        $this->info("Compiled Successfully in " . $seconds . " seconds");

        return CommandAlias::SUCCESS;
    }
}
