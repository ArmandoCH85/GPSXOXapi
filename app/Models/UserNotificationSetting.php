<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

class UserNotificationSetting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'channel',
        'whatsapp_number',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the notification setting.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the validation rules for the model.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public static function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'channel' => ['required', 'string', Rule::in(['whatsapp', 'android'])],
            'whatsapp_number' => [
                'required_if:channel,whatsapp',
                'nullable',
                'string',
                'max:20',
                'regex:/^[+]?[0-9\s\-\(\)]+$/'
            ],
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Aplicar reglas de negocio
            if ($model->channel === 'whatsapp' && empty($model->whatsapp_number)) {
                throw new \InvalidArgumentException('El número de WhatsApp es obligatorio cuando el canal es WhatsApp.');
            }
        });
    }
}