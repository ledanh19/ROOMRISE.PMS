<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class RoleHelper
{
    public static function partnerRoles(): array
    {
        return [
            'Partner Admin',
            'Kế Toán',
            'CSKH Staff',
            'OPS Staff',
            'OTA Admin',
            'OTA Staff',
        ];
    } 
    public static function isPartnerScopedUser(): bool
    {
        $user = Auth::user();
        return $user && $user->hasAnyRole(self::partnerRoles());
    }
  
    public static function getScopedPartnerGroupId(): ?int
    {
        if (self::isPartnerScopedUser()) {
            return Auth::user()?->partner_group_id;
        }

        return null;
    }
}
