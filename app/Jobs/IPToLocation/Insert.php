<?php

namespace App\Jobs\IPToLocation;

use App\Models\Operation;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class Insert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const INDEXES = [
        "ip_start",
        "ip_end",
        "continent",
        "country",
        "stateprov",
        "district",
        "city",
        "zipcode",
        "latitude",
        "longitude",
        "geoname_id",
        "timezone_offset",
        "timezone_name",
        "weather_code",
        "isp_name",
        "autonomous_number",
        "connection_type",
        "organization_name",
    ];
    private $ipApiData = [];
    private $ipStackData = [];

    public function handle()
    {

        write_to_console("Start data inserting");
        //create temp table to store data on it
        DB::statement("Create table if not exists ip_lookups_temp like ip_lookups");
        DB::table("ip_lookups_temp")->truncate();

        Operation::where('status', Operation::STATUSES['unzipped'])->get()->each(function ($operation) {
            //LazyCollection is used to keep memory usage low
            $counter = 0;
            $chunkSize = 1000;
            LazyCollection::make(function () use ($operation) {
                $handle = fopen(public_path("extractedFiles/$operation->file_name"), "r");

                while (($line = fgets($handle)) !== false) {
                    yield $line;
                }
                //ignore first line
            })->skip(1)
                //chunk data to avoid high memory usage
                ->chunk($chunkSize)
                ->map(function (LazyCollection $lines) {
                    return $this->prepareData($lines);
                })->each(function ($lines) use ($operation, $chunkSize, &$counter) {
                    //DB facade is used here because it is faster than normal modal
                    DB::table("ip_lookups_temp")->insert($lines);

                    $percent = number_format((($counter * $chunkSize) / $operation->rows) * 100, 2);
                    write_to_console("Inserted completed: {$percent}%");
                    $counter++;
                });
            $operation->inserted_at = now();
            $operation->status = Operation::STATUSES['completed'];
            $operation->save();
            if (is_file(public_path("extractedFiles/$operation->file_name")))
                unlink(public_path("extractedFiles/$operation->file_name"));
        });
        Schema::dropIfExists("ip_lookups");
        Schema::rename("ip_lookups_temp", "ip_lookups");
        write_to_console("Done inserting data");
    }

    /**
     * Return column index in csv file
     *
     * @param $columnName
     * @return false|int|string
     */
    private function getColumnIndex($columnName)
    {
        return array_search($columnName, self::INDEXES);
    }

    /**
     * Prepare data before insert it in the DB
     *
     * @param LazyCollection $lines
     * @return array
     */
    private function prepareData(LazyCollection $lines)
    {
        return $lines->map(function ($line) {
            $rowData = str_getcsv($line);
            $data = [
                "address_type" => $this->getColumnValue($rowData, "address_type"),
                "ip_start" => $this->getColumnValue($rowData, "ip_start"),
                "ip_end" => $this->getColumnValue($rowData, "ip_end"),
                "continent" => $this->getColumnValue($rowData, "continent"),
                "country" => $this->getColumnValue($rowData, "country"),
                "stateprov" => $this->getColumnValue($rowData, "stateprov"),
                "district" => $this->getColumnValue($rowData, "district"),
                "city" => $this->getColumnValue($rowData, "city"),
                "zipcode" => $this->getColumnValue($rowData, "zipcode"),
                "latitude" => $this->getColumnValue($rowData, "latitude"),
                "longitude" => $this->getColumnValue($rowData, "longitude"),
                "geoname_id" => $this->getColumnValue($rowData, "geoname_id"),
                "timezone_offset" => $this->getColumnValue($rowData, "timezone_offset"),
                "timezone_name" => $this->getColumnValue($rowData, "timezone_name"),
                "weather_code" => $this->getColumnValue($rowData, "weather_code"),
                "isp_name" => $this->getColumnValue($rowData, "isp_name"),
                "autonomous_number" => $this->getColumnValue($rowData, "autonomous_number"),
                "connection_type" => $this->getColumnValue($rowData, "connection_type"),
                "organization_name" => $this->getColumnValue($rowData, "organization_name"),
            ];
            $this->ipApiData = [];
            $this->ipStackData = [];
            return $data;
        })->toArray();
    }

    /**
     * Get value for the column depend on conditions
     *
     * @param $data
     * @param $columnName
     * @return mixed|string|null
     * @throws \Exception
     */
    private function getColumnValue($data, $columnName)
    {

        switch ($columnName) {
            case 'address_type':
                return $this->getAddressType($data[$this->getColumnIndex('ip_start')]);
            //nullable data
            case "autonomous_number":
            case "connection_type":
            case "geoname_id":
                return !empty($data[$this->getColumnIndex($columnName)]) ? $data[$this->getColumnIndex($columnName)] : null;
            case "organization_name":
            case "zipcode":
                return !empty($data[$this->getColumnIndex($columnName)]) ? $data[$this->getColumnIndex($columnName)] : "";
            //other data
            default:
                return $data[$this->getColumnIndex($columnName)];
        }
    }

    /**
     * Get Address type from giving ip address
     *
     * @param $ipAddress
     * @return string
     * @throws \Exception
     */
    private function getAddressType($ipAddress)
    {
        //get ip address type
        if (ip2long($ipAddress) !== false) {
            return "ipv4";
        } else if (preg_match('/^[0-9a-fA-F:]+$/', $ipAddress) && inet_pton($ipAddress)) {
            return "ipv6";
        }
        throw new \Exception("unknown address type for {$ipAddress}");
    }

}