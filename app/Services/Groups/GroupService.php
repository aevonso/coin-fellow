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

        $group->users()->attach($user->id, ['role' => 'owner']);

        return $group->load('users');
    }

    public function getGroup(User $user, string $groupId): Group 
    {
        $group = Group::with(['users', 'expenses.payer', 'expenses.category'])
            ->findOrFail($groupId);

        if (!$group->users->contains($user->id)) {
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
        return $group->load('users');
    }

    public function deleteGroup(User $user, string $groupId): void 
    {
        $group = Group::findOrFail($groupId);

        $this->checkUserPermissions($user, $group, ['owner']);

        $group->delete();
    }

public function inviteUser(User $user, string $groupId, InviteUserDTO $dto): void 
{
    \Log::info('=== INVITE USER DEBUG START ===');
    \Log::info('Search term:', [$dto->email_or_username]);
    \Log::info('Group ID:', [$groupId]);
    \Log::info('Inviter ID:', [$user->id]);
    
    $group = Group::findOrFail($groupId);
    \Log::info('Group found:', [$group->id, $group->name]);

    $this->checkUserPermissions($user, $group, ['owner', 'admin']);

    //search user
    $invitedUser = User::where('email', $dto->email_or_username)
        ->orWhere('username', $dto->email_or_username)
        ->orWhere('phone', $dto->email_or_username)
        ->first();

    \Log::info('User search result:', [
        'found' => $invitedUser ? 'YES' : 'NO',
        'user_id' => $invitedUser ? $invitedUser->id : null,
        'username' => $invitedUser ? $invitedUser->username : null,
        'email' => $invitedUser ? $invitedUser->email : null,
        'phone' => $invitedUser ? $invitedUser->phone : null
    ]);

    if (!$invitedUser) {
        \Log::info('ERROR: User not found');
        throw ValidationException::withMessages([
            'email_or_username' => ['Пользователь не найден'],
        ]);
    }

    //проверка на участие в группе
    $alreadyMember = $group->users()->where('user_id', $invitedUser->id)->exists();
    \Log::info('Already in group?:', [$alreadyMember ? 'YES' : 'NO']);

    if ($alreadyMember) {
        \Log::info('ERROR: User already in group');
        throw ValidationException::withMessages([
            'email_or_username' => ['Пользователь уже находится в группе'],
        ]);
    }

    //invite
    $group->users()->attach($invitedUser->id, ['role' => $dto->role ?? 'member']);
    \Log::info('SUCCESS: User added to group');
    \Log::info('=== INVITE USER DEBUG END ===');

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

        $group->users()->detach($userId);
    }

    public function leaveGroup(User $user, string $groupId): void
    {
        $group = Group::findOrFail($groupId);

        $userRole = $group->users()->where('user_id', $user->id)->first()->pivot->role;
        
        if ($userRole === 'owner') {
            throw ValidationException::withMessages([
                'group' => ['Владелец не может покинуть группу. Передайте права собственности или удалите группу'],
            ]);
        }

        $group->users()->detach($user->id);
    }

    private function checkUserPermissions(User $user, Group $group, array $allowedRoles): void
    {
        $userRole = $group->users()->where('user_id', $user->id)->first()->pivot->role ?? null;
        
        if (!$userRole || !in_array($userRole, $allowedRoles)) {
            throw ValidationException::withMessages([
                'permission' => ['У вас нет разрешения на выполнение этого действия'],
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
}