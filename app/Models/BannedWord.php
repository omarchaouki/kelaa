<?php

namespace App\Models;

use App\Observers\BannedWordObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $word
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[ObservedBy(BannedWordObserver::class)]
#[Fillable(['word'])]
class BannedWord extends Model {}
