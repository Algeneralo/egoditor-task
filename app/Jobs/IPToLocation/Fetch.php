<?php

namespace App\Jobs\IPToLocation;

use App\Models\Operation;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Fetch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const URL = "https://db-ip.com/account/adcda1fff413ac2395a751f7cb7fdd28706cc197/db/ip-to-location-isp/";

    public function handle()
    {
        write_to_console("Start Fetching & Downloading...");
        //get csv data
        $response = Http::get(self::URL);

        if ($response->successful())
            $this->downloadFile($response->json()["csv"]);
        else
            throw new HttpException($response->status(), "Cannot fetch API result from " . self::URL);
    }

    /**
     * Download zip file
     *
     * @param array $csv
     */
    private function downloadFile($csv)
    {
        $oldPercent = 0;
        $response = Http::withOptions(
            [
                'progress' => function ($downloadTotal, $downloadedBytes) use (&$oldPercent) {
                    $percent = 0;
                    if ($downloadTotal)
                        $percent = (number_format(($downloadedBytes / $downloadTotal), 2) * 100);
                    if ($oldPercent != $percent) {
                        write_to_console("Download completed: $percent%");
                        $oldPercent = $percent;
                    }
                },
            ]
        )->get($csv["url"]);

        if ($response->successful())
            $this->saveFile($csv, $response->body());
        else
            throw new HttpException($response->status(), "cannot download file from {$csv["url"]}");
    }

    /**
     * Save file in storage & create new DB entry
     *
     * @param $csv
     * @param $fileContent
     */
    private function saveFile($csv, $fileContent)
    {
        DB::transaction(function () use ($csv, $fileContent) {
            $status = Storage::put($csv["name"], $fileContent);
            if ($status && md5_file(storage_path("app/" . $csv["name"])) == $csv["md5sum"])
                Operation::create([
                    "file_name" => $csv["name"],
                    "md5sum" => $csv["md5sum"],
                    "rows" => $csv["rows"],
                    "downloaded_at" => now(),
                    "status" => Operation::STATUSES['downloaded'],
                ]);
            else
                throw new \Exception("saving file failed, file is probably corrupt or altered");
        });
        write_to_console("Done Fetching & Downloading");
    }
}