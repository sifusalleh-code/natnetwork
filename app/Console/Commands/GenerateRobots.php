<?php

namespace App\Console\Commands;

use App\Engines\Cms\Services\SeoService;
use Illuminate\Console\Command;

class GenerateRobots extends Command
{
    protected $signature = 'seo:robots {--stdout : Cetak kandungan tanpa menulis fail}';

    protected $description = 'Jana public/robots.txt berdasarkan APP_URL dan config/seo.php';

    public function handle(SeoService $seo): int
    {
        if ($this->option('stdout')) {
            $this->output->write($seo->robotsTxt());

            return self::SUCCESS;
        }
        file_put_contents(public_path('robots.txt'), $seo->robotsTxt());
        $this->info('robots.txt dijana: '.$seo->absolute('/robots.txt'));

        return self::SUCCESS;
    }
}
