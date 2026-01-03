<?php

namespace App\Services\Groups;

use App\Models\Group;
use App\Models\User;
use App\Models\Invitation;
use App\Services\Notifications\Interfaces\NotificationServiceInterface;
use App\Services\Groups\DTO\CreateGroupDTO;
use App\Services\Groups\DTO\UpdateGroupDTO;
use App\Services\Groups\DTO\InviteUserDTO;
use App\Services\Groups\Interfaces\GroupServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GroupService implements GroupServiceInterface 
{
    public function __construct(
        private NotificationServiceInterface $notificationService
    ) {}

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

        GroupUser::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        return $group->load('members');
    }

    public function getGroup(User $user, string $groupId): Group 
    {
        $group = Group::with(['users', 'expenses.payer', 'expenses.category'])
            ->findOrFail($groupId);

        if(!$group->groupUsers()->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'group' => ['Вы не являетесь членом этой группы'],
            ]);
        }

        return $group;
    }

    public function updateGroup(User $user, string $groupId, UpdateGroupDTO $dto): Group 
    {
        $group = Group::findOrFail($groupId);

        $this->checkUserPermissions($user, $group, ['owner', 'admin']);

        if ($dto->name) {
            $group->name = $dto->name;
        }
        if ($dto->currency) {
            $group->currency = $dto->currency;
        }
        if ($dto->description !== null) {
            $group->description = $dto->description;
        }

        $group->save();
        return $group->load('members');
    }

    public function deleteGroup(User $user, string $groupId): void 
    {
        $group = Group::findOrFail($groupId);

        $this->checkUserPermissions($user, $group, ['owner']);

        $group->delete();
    }

    public function inviteUser(User $user, string $groupId, InviteUserDTO $dto): void 
    {
        $group = Group::findOrFail($groupId);

        $this->checkUserPermissions($user, $group, ['owner', 'admin']);

        $invitedUser = User::where('email', $dto->email_or_username)
            ->orWhere('username', $dto->email_or_username)
            ->orWhere('phone', $dto->email_or_username)
            ->first();

        if(!$invitedUser) {
            throw ValidationException::withMessages([
                'email_or_username' => ['Пользователь не найден'],
            ]);
        }

        $existingMembers = GroupUser::where('group_id', $group->id)
            ->where('user_id', $invitedUser->id)
            ->exists();
            
        if($existingMembers) {
            throw ValidationException::withMessages([
                'email_or_username' => ['Пользователь уже находится в группе'],
            ]);
        }

        GroupUser::create([
            'group_id' => $group->id,
            'user_id' => $invitedUser->id,
            'role' => $dto->role ?? 'member',
        ]);

        $this->notifyInvitation($invitedUser, $user, $group);
    }


    public function removeUser(User $user, string $groupId, string $userId): void
    {
        $group = Group::findOrFail($groupId);

        $this->checkUserPermissions($user, $group, ['owner', 'admin']);

        if ($user->id === $userId) {
            throw ValidationException::withMessages([
                'user' => ['Вы не можете самостоятельно удалиться из группы'],
            ]);
        }

        GroupUser::where('group_id', $group->id)
            ->where('user_id', $userId)
            ->delete();
    }

    public function leaveGroup(User $user, string $groupId): void
    {
        $group = Group::findOrFail($groupId);

        $groupUser = GroupUser::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if($groupUser->role === 'owner') {
            throw ValidationException::withMessages([
                'group' => ['Владелец не может группу. Передайте права или удалите группу'],
            ]);
        }

        $groupUser->delete();
    }

    private function checkUserPermissions(User $user, Group $group, array $allowedRoles): void
    {
        $groupUser = GroupUser::Where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->first();

        if(!$groupUser || in_array($groupUser->role, $allowedRoles)) {
            throw ValidationException::withMessages([
                'permission' => ['У вас нет разрещения для выполнения этого действия'],
            ]);
        }
    }

    private function notifyInvitation(User $invitedUser, User $inviter, Group $group): void
    {
        $this->notificationService->notifyInvitation($invitedUser, [
            'group_id' => $group->id,
            'group_name' => $group->name,
            'inviter_name' => $inviter->first_name ?? $inviter->username,
            'invited_user_id' => $invitedUser->id,
        ]);
    }

    public function getUserRoleInGroup(User $user, Group $group): ?string 
    {
        $groupUser = GroupUser::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->first();
        
        return $groupUser?->role;
    }

    //функция для передачи прав собственности

    public function transferOwnership(User $currentOwner, string $groupId, string $newOwnerId): void 
    {
        $group = Group::firstOrFail($groupId);

        //проверка, что текущий юзер - смотрящий
        $currentGroupOwner = GroupUser::where('group_id', $group->id)
            ->where('user_id', $currentOwner->id)
            ->where('role', 'owner')
            ->firstOrFail();

        //чекаем, что новый смотрящий находится в группе
        $newOwnerGroupUser = GroupUser::where('group_id', $group->id)
            ->where('user_id', $newOwnerId)
            ->first();

        if(!$newOwnerGroupUser) {
            throw ValidationException::withMessages([
                'user' => ['Пользователь не состоит в группе'],
            ]);
        }

        DB::transaction(function () use ($group, $currentOwner, $newOwnerId) {
            //старый смотрящий в красные переходит
            GroupUser::where('group_id', $group->id)
                ->where('user_id', $currentOwner->id)
                ->update(['role'=> 'admin']);

            GroupUser::where('group_id', $group->id)
                ->where('user_id', $newOwnerId)
                ->update(['role' => 'owner']);
        });
    }
}