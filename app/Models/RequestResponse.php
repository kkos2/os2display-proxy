<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestResponse extends Model
{
    protected $fillable = ['path', 'content_type', 'data'];
}
