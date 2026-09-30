<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'tbl_user';

    public $timestamps = false;

    protected $fillable = [
        'username', 'password', 'email', 'member_code',
        'password_reset_token', 'remember_token', 'last_login_at',
    ];

    protected $hidden = [
        'password', 'remember_token', 'password_reset_token',
    ];

    /**
     * Check if password is still MD5 (legacy format)
     */
    public function isMd5Password(): bool
    {
        return strlen($this->password) === 32 && !str_starts_with($this->password, '$2y$');
    }

    /**
     * Verify password with MD5 backward compatibility
     */
    public function verifyPassword(string $password): bool
    {
        if ($this->isMd5Password()) {
            // Legacy MD5 check
            if ($this->password === md5($password)) {
                // Auto-upgrade to bcrypt
                $this->password = bcrypt($password);
                $this->save();
                return true;
            }
            return false;
        }

        return password_verify($password, $this->password);
    }
}
