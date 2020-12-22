<?php

namespace App\Models\Queue;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TranscribeJob extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'transcribe_jobs';

    protected $fillable = [
        'video_id',
        'job_name',
        'status',
        'url',
        'complete'
    ];
}
