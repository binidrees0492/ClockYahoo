<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'description',
        'auditable_type',
        'auditable_id',
        'changes',
        'ip_address',
    ];

    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function auditable()
    {
        return $this->morphTo();
    }

    public static function record(string $description, ?array $changes = null, ?Model $auditable = null, string $action = 'Edit'): self
    {
        $user = auth()->user();
        return self::create([
            'user_id'        => $user?->id,
            'user_name'      => $user?->name ?? 'System',
            'action'         => $action,
            'description'    => $description,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id'   => $auditable?->getKey(),
            'changes'        => $changes ?? [],
            'ip_address'     => request()->ip(),
        ]);
    }
}
