<?php

namespace App\Services\Groups\Interfaces;

use App\Models\Group;
use App\Models\User;
use App\Services\Groups\DTO\CreateGroupDTO;
use App\Services\Groups\DTO\UpdateGroupDTO;
use App\Services\Groups\DTO\InviteUserDTO;
use Illuminate\Pagination\LengthAwarePaginator;

interface GroupServiceInterface 
{
    public function getUserGroups(User $user): LengthAwarePaginator;
    public function createGroup(User $user, CreateGroupDTO $dto): Group;
    public function getGroup(User $user, string $groupId): Group; 
    public function updateGroup(User $user, string $groupId, UpdateGroupDTO $dto): Group;
    public function deleteGroup(User $user, string $groupId): void;
    public function inviteUser(User $user, string $groupId, InviteUserDTO $dto): void; 
    public function removeUser(User $user, string $groupId, string $userId): void;
    public function leaveGroup(User $user, string $groupId): void;
}