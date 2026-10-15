<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BorrowRequest;
use App\Models\User;
use App\Services\ProfileImageService;
use App\Support\ImageUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProfileApiController extends Controller
{
    public function __construct(private ProfileImageService $profileImageService)
    {
    }

    public function me(Request $request): JsonResponse
    {
        $userId = $request->session()->get('user_id');

        if (! $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Please log in to view your profile.',
            ], 401);
        }

        return $this->profileResponse($request, (string) $userId, true);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        return $this->profileResponse($request, $id, false);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $this->currentUser($request);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Please log in to update your profile.',
            ], 401);
        }

        $input = $request->all();

        if (! isset($input['room_number']) && isset($input['roomNumber'])) {
            $input['room_number'] = $input['roomNumber'];
        }

        $validator = Validator::make($input, [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'phone' => [
                'required',
                'regex:/^01[3-9]\d{8}$/',
                Rule::unique('users', 'phone')->ignore($user->id),
            ],
            'department' => ['required', 'string', 'max:100'],
            'session' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'room_number' => ['required', 'string', 'max:50'],
            'hall' => ['required', 'in:' . implode(',', array_keys($this->halls()))],
            'bio' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'Name is required',
            'name.min' => 'Name must be at least 3 characters',
            'name.max' => 'Name must be less than 100 characters',
            'phone.required' => 'Phone number is required',
            'phone.regex' => 'Please enter a valid Bangladeshi phone number',
            'phone.unique' => 'This phone number is already registered to another account',
            'department.required' => 'Department is required',
            'department.max' => 'Department name is too long',
            'session.required' => 'Session is required',
            'session.regex' => 'Session must be in format YYYY-YY (e.g., 2023-24)',
            'room_number.required' => 'Room number is required',
            'room_number.max' => 'Room number is too long',
            'hall.required' => 'Hall selection is required',
            'hall.in' => 'Invalid hall selected',
            'bio.max' => 'Bio must be less than 500 characters',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user->name = trim((string) $input['name']);
            $user->phone = trim((string) $input['phone']);
            $user->department = trim((string) $input['department']);
            $user->session = trim((string) $input['session']);
            $user->room_number = trim((string) $input['room_number']);
            $user->hall = (string) $input['hall'];
            $user->bio = trim((string) ($input['bio'] ?? ''));
            $user->save();

            $request->session()->put([
                'user_name' => $user->name,
                'user_hall' => $user->hall,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => $this->profilePayload($user, (string) $user->id, true),
        ]);
    }

    public function updateAvatar(Request $request): JsonResponse
    {
        $user = $this->currentUser($request);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Please log in to update your profile picture.',
            ], 401);
        }

        $image = $request->file('profile_image')
            ?? $request->file('avatar')
            ?? $request->file('image');

        if (! $image) {
            return response()->json([
                'success' => false,
                'message' => 'Profile image is required.',
            ], 422);
        }

        $validator = Validator::make(['profile_image' => $image], [
            'profile_image' => ['required', 'file', 'image', 'mimes:jpeg,png,gif,webp', 'max:5120'],
        ], [
            'profile_image.required' => 'Profile image is required',
            'profile_image.image' => 'Please upload a valid image file',
            'profile_image.mimes' => 'Only JPG, PNG, GIF, and WebP images are allowed',
            'profile_image.max' => 'Profile image must be less than 5MB',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $uploadResult = $this->profileImageService->process($image, (string) $user->id);

        if (isset($uploadResult['error'])) {
            return response()->json([
                'success' => false,
                'message' => $uploadResult['error'],
                'errors' => ['profile_image' => [$uploadResult['error']]],
            ], 422);
        }

        $this->profileImageService->delete((string) $user->profile_pic);
        $user->profile_pic = $uploadResult['filename'];
        $user->save();

        $request->session()->put('user_avatar', $user->profile_pic);

        return response()->json([
            'success' => true,
            'message' => 'Profile picture updated successfully.',
            'data' => [
                'profile_pic' => $user->profile_pic,
                'profile_image_url' => $user->profile_image_url,
            ],
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $this->currentUser($request);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Please log in to change your password.',
            ], 401);
        }

        $input = $request->all();
        if (! isset($input['password_confirmation']) && isset($input['confirm_password'])) {
            $input['password_confirmation'] = $input['confirm_password'];
        }

        $validator = Validator::make($input, [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'current_password.required' => 'Current password is required',
            'new_password.required' => 'New password is required',
            'new_password.min' => 'New password must be at least 6 characters',
            'new_password.confirmed' => 'Passwords do not match',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (! Hash::check((string) $input['current_password'], (string) $user->password_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Incorrect current password.',
                'errors' => [
                    'current_password' => ['Incorrect current password'],
                ],
            ], 422);
        }

        $user->password_hash = Hash::make((string) $input['new_password']);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully.',
        ]);
    }

    private function profileResponse(Request $request, string $viewUserId, bool $requireOwn): JsonResponse
    {
        $user = User::find($viewUserId);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $currentUserId = $request->session()->get('user_id');
        $isOwnProfile = $currentUserId !== null && (string) $currentUserId === (string) $user->id;

        if ($requireOwn && ! $isOwnProfile) {
            return response()->json([
                'success' => false,
                'message' => 'You can only view your own profile through this endpoint.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $this->profilePayload($user, $currentUserId !== null ? (string) $currentUserId : null, $isOwnProfile),
        ]);
    }

    private function profilePayload(User $user, ?string $currentUserId, bool $isOwnProfile): array
    {
        $ownedBooks = Book::query()
            ->where('owner_id', $user->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Book $book) {
                return [
                    'id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author,
                    'category' => $book->category,
                    'status' => $book->display_status,
                    'cover_url' => $book->cover_url,
                    'created_at' => $book->created_at?->toISOString(),
                ];
            })
            ->values();

        $borrowData = $isOwnProfile ? $this->getBorrowProfileData($user->id) : [
            'borrowed' => [],
            'lent' => [],
        ];

        $publicUser = [
            'id' => $user->id,
            'name' => $user->name,
            'department' => $user->department,
            'session' => $user->session,
            'room_number' => $user->room_number,
            'hall' => (string) $user->hall,
            'hall_name' => $user->hall_name,
            'gender' => $user->gender,
            'role' => $user->role,
            'status' => $user->status,
            'verified' => (bool) $user->verified,
            'profile_pic' => $user->profile_pic,
            'profile_image_url' => $user->profile_image_url,
            'bio' => $user->bio,
            'boipoka_badge' => $user->boipoka_badge,
            'boipoka_position' => $user->boipoka_position,
            'created_at' => $user->created_at?->toISOString(),
        ];

        if ($isOwnProfile) {
            $publicUser['email'] = $user->email;
            $publicUser['phone'] = $user->phone;
        }

        return [
            'user' => $publicUser,
            'stats' => [
                'owned' => $ownedBooks->count(),
                'borrowed' => count($borrowData['borrowed']),
                'lent' => count($borrowData['lent']),
            ],
            'is_own_profile' => $isOwnProfile,
            'show_sensitive_info' => $isOwnProfile,
            'member_since' => $user->created_at?->format('M Y') ?? now()->format('M Y'),
            'owned_books' => $ownedBooks,
            'borrowed_books' => $borrowData['borrowed'],
            'lent_books' => $borrowData['lent'],
        ];
    }

    private function currentUser(Request $request): ?User
    {
        $userId = $request->session()->get('user_id');

        if (! $userId) {
            return null;
        }

        return User::find($userId);
    }

    private function getBorrowProfileData(string $userId): array
    {
        $requests = BorrowRequest::query()
            ->with(['owner', 'borrower'])
            ->where(function ($query) use ($userId) {
                $query->where('borrower_id', $userId)
                    ->orWhere('owner_id', $userId);
            })
            ->whereIn('status', ['approved', 'returned'])
            ->get();

        $borrowedBooks = [];
        $lentBooks = [];

        foreach ($requests as $borrowRequest) {
            $owner = $borrowRequest->owner;
            $borrower = $borrowRequest->borrower;

            if ($borrowRequest->borrower_id === $userId) {
                $borrowedBooks[] = [
                    'id' => $borrowRequest->book_id,
                    'title' => $borrowRequest->book_title,
                    'author' => $borrowRequest->book_author,
                    'cover_image' => $borrowRequest->book_cover,
                    'status' => $borrowRequest->status,
                    'owner_id' => $owner?->id ?? '',
                    'owner_name' => $owner?->name ?? $borrowRequest->owner_name ?? 'Unknown Owner',
                    'owner_hall' => $owner?->hall ?? '',
                    'display_hall' => $owner?->hall_name ?? 'N/A',
                ];
            }

            if ($borrowRequest->owner_id === $userId) {
                $lentBooks[] = [
                    'id' => $borrowRequest->book_id,
                    'title' => $borrowRequest->book_title,
                    'author' => $borrowRequest->book_author,
                    'cover_image' => $borrowRequest->book_cover,
                    'status' => $borrowRequest->status,
                    'owner_id' => $borrower?->id ?? '',
                    'owner_name' => $borrower?->name ?? $borrowRequest->borrower_name ?? 'Unknown Borrower',
                    'owner_hall' => $borrower?->hall ?? '',
                    'display_hall' => $borrower?->hall_name ?? 'N/A',
                ];
            }
        }

        return [
            'borrowed' => $borrowedBooks,
            'lent' => $lentBooks,
        ];
    }

    private function halls(): array
    {
        return [
            '1' => 'Amar Ekushey Hall',
            '2' => 'Dr. Muhammad Shahidullah Hall',
            '3' => 'Fazlul Huq Muslim Hall',
            '4' => 'Salimullah Muslim Hall',
            '5' => 'Shahid Sergeant Zahurul Haq Hall',
            '6' => 'Haji Muhammad Mohsin Hall',
            '7' => 'Sir A.F. Rahman Hall',
            '8' => 'Masterda Surja Sen Hall',
            '9' => 'Kobi Jashimuddin Hall',
            '10' => 'Muktijoddha Ziaur Rahman Hall',
            '11' => 'Shaheed Sharif Osman Hadi Hall',
            '12' => 'Bijoy Ekattor Hall',
            '13' => 'Jagannath Hall',
            '14' => 'Ruqayyah Hall',
            '15' => 'Shamsun Nahar Hall',
            '16' => 'Bangladesh-Kuwait Maitree Hall',
            '17' => 'Begum Fazilatunnesa Mujib Hall',
            '18' => 'Kobi Sufiya Kamal Hall',
        ];
    }
}