<?php

namespace App\Http\Controllers;

use App\Http\Requests\Groups\CreateGroupRequest;
use App\Http\Requests\Groups\UpdateGroupRequest;
use App\Http\Requests\Groups\InviteUserRequest;
use App\Http\Resources\GroupResource;
use App\Http\Resources\Collections\GroupCollection;
use App\Services\Groups\Interfaces\GroupServiceInterface;
use App\Services\Groups\DTO\CreateGroupDTO;
use App\Services\Groups\DTO\UpdateGroupDTO;
use App\Services\Groups\DTO\InviteUserDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function __construct(
        private GroupServiceInterface $groupService
    ) {}

    public function index(Request $request): GroupCollection
    {
        $user = $request->user();
        $groups = $this->groupService->getUserGroups($user);

        return new GroupCollection($groups);
    }

    public function store(CreateGroupRequest $request): GroupResource
    {
        $user = $request->user();
        $dto = CreateGroupDTO::from($request->validated());
        $group = $this->groupService->createGroup($user, $dto);

        return new GroupResource($group);
    }


    public function show(Request $request, string $groupId): GroupResource
    {
        $user = $request->user();
        $group = $this->groupService->getGroup($user, $groupId);

        return new GroupResource($group);
    }


    public function update(UpdateGroupRequest $request, string $groupId): GroupResource
    {
        $user = $request->user();
        $dto = UpdateGroupDTO::from($request->validated());
        $group = $this->groupService->updateGroup($user, $groupId, $dto);

        return new GroupResource($group);
    }


    public function destroy(Request $request, string $groupId): JsonResponse
    {
        $user = $request->user();
        $this->groupService->deleteGroup($user, $groupId);

        return response()->json([
            'success' => true,
            'message' => 'Группа успешно удалена',
        ]);
    }


    public function invite(InviteUserRequest $request, string $groupId): JsonResponse
    {
        $user = $request->user();
        $dto = InviteUserDTO::from($request->validated());
        $this->groupService->inviteUser($user, $groupId, $dto);

        return response()->json([
            'success' => true,
            'message' => 'Пользователь добавлен',
        ]);
    }

    public function removeUser(Request $request, string $groupId, string $userId): JsonResponse
    {
        $user = $request->user();
        $this->groupService->removeUser($user, $groupId, $userId);

        return response()->json([
            'success' => true,
            'message' => 'Пользователь удален',
        ]);
    }

    public function leave(Request $request, string $groupId): JsonResponse
    {
        $user = $request->user();
        $this->groupService->leaveGroup($user, $groupId);

        return response()->json([
            'success' => true,
            'message' => 'Вы покинули группу',
        ]);
    }
}