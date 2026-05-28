<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use BelongsToUser, HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'vat_number',
        'tax_code',
        'bank_withholding',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'bank_withholding' => 'boolean',
        ];
    }
}
