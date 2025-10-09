<?php

namespace Clearlyip\LaravelFlipt\Commands;

use Clearlyip\LaravelFlipt\Flipt;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CacheClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flipt:cache:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the flipt caches';

    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        $flipt = app(Flipt::class);
        $this->info('Clearing the local flipt cache');

        Cache::tags($flipt->getCacheTags())->flush();
    }
}
