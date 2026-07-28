<?php

namespace App\Models;

use App\Enums\UserType;
use App\Models\Concerns\HasUlid;
use App\Support\Rbac;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, HasUlid, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password',
        'company_id', 'branch_id', 'type', 'phone',
        'locale', 'is_active',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'type' => UserType::class,
        ];
    }

    // Relationships ---------------------------------------------------------

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function staffProfile(): HasOne
    {
        return $this->hasOne(StaffProfile::class);
    }

    public function customerProfile(): HasOne
    {
        return $this->hasOne(CustomerProfile::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(ChittyMembership::class, 'customer_id');
    }

    public function kycDocuments(): HasMany
    {
        return $this->hasMany(KycDocument::class, 'customer_id');
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(CustomerBankAccount::class, 'customer_id');
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class, 'customer_id');
    }

    public function installmentPayments(): HasMany
    {
        return $this->hasMany(InstallmentPayment::class, 'customer_id');
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    public function loginHistories(): HasMany
    {
        return $this->hasMany(LoginHistory::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'customer_id');
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    /**
     * Route push notifications to the user's most recently registered device.
     * (PushChannel reads this.)
     */
    public function routeNotificationForPush(): ?string
    {
        return $this->deviceTokens()->latest('last_used_at')->value('token')
            ?? $this->deviceTokens()->latest()->value('token');
    }

    /**
     * Whether the user accepts notifications on a channel. Defaults to true
     * unless an explicit preference row disables it.
     */
    public function acceptsNotificationOn(string $channel): bool
    {
        $pref = $this->notificationPreferences->firstWhere('channel', $channel)
            ?? $this->notificationPreferences()->where('channel', $channel)->first();

        return $pref?->enabled ?? true;
    }

    // Helpers ---------------------------------------------------------------

    public function isStaff(): bool
    {
        return $this->type === UserType::Staff;
    }

    public function isCustomer(): bool
    {
        return $this->type === UserType::Customer;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Rbac::SUPER_ADMIN);
    }

    /**
     * Whether this user operates company-wide rather than being confined to a
     * single branch. Super Admins and Company Owners see/manage every branch;
     * all other staff roles are branch-scoped to their own branch_id.
     */
    public function actsAcrossBranches(): bool
    {
        return $this->hasRole(Rbac::SUPER_ADMIN) || $this->hasRole(Rbac::COMPANY_OWNER);
    }

    /**
     * The branch id this user's queries/authorization must be restricted to,
     * or null when the user is unrestricted (company-wide). A branch-scoped
     * user with no branch assigned yields their (possibly null) branch_id,
     * which safely matches nothing until an admin assigns them a branch.
     */
    public function branchScopeId(): ?int
    {
        return $this->actsAcrossBranches() ? null : $this->branch_id;
    }

    /**
     * Route the user after login based on account type. Staff land on the
     * admin dashboard; customers land on the customer portal.
     */
    public function homeRoute(): string
    {
        return $this->isStaff() ? 'admin.dashboard' : 'portal.dashboard';
    }
}
