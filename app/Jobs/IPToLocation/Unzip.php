<?php

namespace App\Jobs\IPToLocation;

use ZipArchive;
use App\Models\Operation;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class Unzip implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        write_to_console("Start file unzipping....");
        Operation::where('status', Operation::STATUSES['downloaded'])->get()->each(function ($operation) {
            $zip = new ZipArchive;
            if ($zip->open(storage_path('app/' . $operation->file_name)) === true && $zip->extractTo(public_path('/extractedFiles')) == true) {
                if (md5_file(storage_path('app/' . $operation->file_name)) != $operation->md5sum)
                    throw new \Exception("MD5 signature verification failed, file is probably corrupt or altered");
                $operation->file_name = $zip->getNameIndex(0);
                $operation->status = Operation::STATUSES['unzipped'];
                $operation->unzipped_at = now();
                $operation->save();
                if (is_file(public_path("extractedFiles/$operation->file_name")))
                    Storage::delete('app/' . $operation->file_name);
            } else
                throw new \Exception("File may not be exists or damaged");
        });
        write_to_console("Done file unzipping");
    }
}