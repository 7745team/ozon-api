<?php

namespace Team7745\OzonApi\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Team7745\OzonApi\Models\OzonTask;
use Team7745\OzonApi\OzonApi;
use Throwable;

class OzonCheckTaskResultJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected OzonApi $ozonApi;
    protected OzonTask $task;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(OzonTask $task)
    {
        $this->ozonApi = app('ozon-api');
        $this->task = $task;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $response = $this->ozonApi->getProductImportInfo($this->task->id);

        if ($response) {
            $this->task->update([
                'response' => json_decode($response, true)
            ]);
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception)
    {
        // Send user notification of failure, etc...
    }
}
