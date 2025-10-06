<?php

namespace App\Services\Groups;

use App\Models\Group;
use App\Models\User;
use App\Services\Groups\DTO\CreateGroupDTO;
use App\Services\Groups\DTO\UpdateGroupDTO;
use App\Services\Groups\DTO\InviteUserDTO;
use App\Services\Groups\Interfaces\GroupServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GroupService implements GroupServiceInterface {
    public function getUserGroups(User $user): LengthAwarePaginator 
    {
        return $user->groups()
            ->withCount('users')
            ->with('users')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }
    
    public function createGroup(User $user, CreateGroupDTO $dto): Group 
    {
        $group = Group::create([
            'name' => $dto->name,
            'currency' => $dto->currency,
            'description' => $dto->description,
            'invite_code' => Str::random(10),
        ]);

        $group->user()->attach($user->id, ['role' => 'owner']);

        return $group->load('users');
    }

    public function getGroup(User $user, strin $groupId): Group {
        $group = Group::with(['users', 'expenses.payer', 'expenses.category'])
            ->findOrFail($groupId);

        if(!$group->users->contains($user->id)) {
            throw ValidationException::withMessages([
                'group' => ['You are not a member of this group'],
            ]);
        }

        return $group;
    }

    public function updateGroup(User $user, string $groupId, UpdateGroupDTO $dto): Group 
    {
        $group = Group::findOrFail($groupId);

        $this->checkUserPermissions($user, $group, ['owner', 'admin']);

        if($dto->name) {
            $group->name = $dto->name;
        }
        if($dto->currency) {
            $group->currency = $dto->currency;
        }
        if($dto->description !==null) {
            $group->description = $dto->description;
        }

        $group->save();
        return $group->load('users');
    }

    public function deleteGroup(User $user, string $groupId): void 
    {
        $group = Group::findOrFail($groupId);

        //только владелец может удалить таблицу
        $this->checkUserPermissions($user, $group, ['owner']);

        $group->delete();
    }

    public function inviteUser(User $user, string $groupId, inviteUserDTO $dto) {
        $group = Group::findOrFail($groupId);

        $this->checkUserPermissions($user, $group, ['owner' , 'admin']);

        $invitedUser - User::where('email', $dto->email_or_username)
            ->orWhere('username', $dto->email_or_username)
            ->first();

        if(!$invitedUser) {
            throw ValidationException::withMessages([
                'email_or_username' => ['User not found'],
            ]);
        }

        if($group->users->contains($invitedUser->id)) {
            throw ValidationException::withMessages([
                'email_or_username' => ['User is already in the group'],
            ]);
        }

        $group->users()->attach($invitedUser->id, ['role' => $dto->role ?? 'members']);
    }


    public function removeUser(User $user, string $groupId, string $userId): void
    {
        $group = Group::findOrFail($groupId);

        // Проверяем права (только owner/admin могут удалять)
        $this->checkUserPermissions($user, $group, ['owner', 'admin']);

        //нельзя удалить самого себя
        if ($user->id === $userId) {
            throw ValidationException::withMessages([
                'user' => ['You cannot remove yourself from the group'],
            ]);
        }

        $group->users()->detach($userId);
    }

    public function leaveGroup(User $user, string $groupId): void
    {
        $group = Group::findOrFail($groupId);

        //владелец не может покинуть, пусть передает права 
        $userRole = $group->users()->where('user_id', $user->id)->first()->pivot->role;
        
        if ($userRole === 'owner') {
            throw ValidationException::withMessages([
                'group' => ['Owner cannot leave the group. Transfer ownership or delete the group.'],
            ]);
        }

        $group->users()->detach($user->id);
    }

    private function checkUserPermissions(User $user, Group $group, array $allowedRoles): void
    {
        $userRole = $group->users()->where('user_id', $user->id)->first()->pivot->role ?? null;
        
        if (!$userRole || !in_array($userRole, $allowedRoles)) {
            throw ValidationException::withMessages([
                'permission' => ['You do not have permission to perform this action'],
            ]);
        }
    }
}
