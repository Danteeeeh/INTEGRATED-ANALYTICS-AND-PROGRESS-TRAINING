<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'name' => $this->name,
            'email' => $this->email,
            'identifier' => $this->identifier,
            'phone' => $this->phone,
            'address' => $this->address,
            'profile_photo_path' => $this->profile_photo_path,
            'role' => $this->when($this->relationLoaded('role'), function () {
                return new RoleResource($this->role);
            }),
            'role_id' => $this->role_id,
            'status' => $this->status,
            'email_verified_at' => $this->email_verified_at?->format('Y-m-d H:i:s'),
            'last_login_at' => $this->last_login_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
