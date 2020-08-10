<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\IPToLocation\Unzip;
use App\Jobs\IPToLocation\Insert;

class Update extends Command
{
    protected $signature = 'dbip:update';

    protected $description = 'Fetch the lasted download url from this json request';

    public function handle()
    {
        $this->info("Start updating dbip");
        \App\Jobs\IPToLocation\Fetch::withChain([
            new Unzip,
            new Insert,
        ])->dispatch();
        $this->info("Done updating dbip");
    }
}
