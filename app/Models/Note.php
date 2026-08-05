<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Note extends Model
{
    protected string $table = 'notes';
    protected array $fillable = ['customer_id', 'user_id', 'body', 'pinned'];
}
