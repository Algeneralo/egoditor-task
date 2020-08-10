<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Operation extends Model
{
    protected $fillable = ["file_name", "md5sum", "rows", "downloaded_at", "unzipped_at", "inserted_at", "status"];

    public const STATUSES = [
        'failed' => '-1',
        'downloaded' => '1',
        'unzipped' => '2',
        'completed' => '3',
    ];
}