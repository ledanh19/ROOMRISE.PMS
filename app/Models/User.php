<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\Auth\CustomResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    const ROLE_ADMIN = 'Admin';
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'verify_code',
        'verified',
        'profile_photo_path',
        'verify_register_code',
        'verify_forgot_code',
        'phone',
        'first_name',
        'last_name',
        'username',
        'partner_group_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomResetPassword($token));
    }

    public function getProfilePhotoUrlAttribute()
    {
        $path = $this->profile_photo_path;

        // Check if the profile_photo_path is a full URL
        if ($path && filter_var($path, FILTER_VALIDATE_URL)) {
            return $path; // Return as is if it's a full URL
        }

        // Otherwise, treat it as a relative path and prepend it with asset()
        if ($path) {
            // return asset($path);
            return Storage::url($path);
        }

        return '';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    /**
     * Get all usermeta
     *
     * @return array<string, string>
     */

    public function usermeta()
    {
        return $this->hasMany(UserMeta::class, 'user_id')->select(['user_id', 'meta_key', 'meta_value']);
    }

    public function getMetaValue($key)
    {
        return UserMeta::where('user_id', $this->id)->where('meta_key', $key)->value('meta_value');
    }

    public function updateMeta($key, $value)
    {
        return UserMeta::updateOrCreate(
            ['user_id' => $this->id, 'meta_key' => $key],
            ['meta_value' => $value]
        );
    }

    public function groupManaged()
    {
        return $this->hasOne(PartnerGroup::class, 'partner_admin_id');
    }

    public function partnerGroup()
    {
        return $this->belongsTo(PartnerGroup::class, 'partner_group_id');
    }

    public function messageHistories(): HasMany
    {
        return $this->hasMany(MessageHistory::class, 'user_id', 'id');
    }

    public function messageDetails(): HasMany
    {
        return $this->hasMany(\App\Models\MessageDetail::class, 'user_id');
    }

    public function revenueHistories()
    {
        return $this->hasMany(\App\Models\UserTestRevenueHistory::class, 'user_id');
    }
}
