<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ProfileImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EditProfileController extends Controller
{
    public function __construct(private ProfileImageService $profileImageService)
    {
    }

    public function show(Request $request)
    {
        $userId = $request->session()->get('user_id');

        if (! $userId) {
            $request->session()->put('redirect_after_login', '/settings/edit-profile');

            return redirect()->route('login');
        }

        $user = User::query()
            ->select('name', 'email', 'department', 'session', 'phone', 'room_number', 'hall', 'profile_pic', 'bio')
            ->find($userId);

        if (! $user) {
            abort(404, 'User not found.');
        }

        return view('settings.edit-profile', [
            'seoTitle' => 'Account Management - OpenShelf',
            'seoDesc' => 'Update your OpenShelf profile details, department, hall residency, and bio.',
            'user' => $user,
            'halls' => $this->halls(),
        ]);
    }

    public function update(Request $request)
    {
        $userId = $request->session()->get('user_id');

        if (! $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Please log in to update settings.',
            ], 401);
        }

        $user = User::find($userId);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'phone' => [
                'required',
                'regex:/^01[3-9]\d{8}$/',
                Rule::unique('users', 'phone')->ignore($userId),
            ],
            'department' => ['required', 'string', 'max:100'],
            'session' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'room_number' => ['required', 'string', 'max:50'],
            'hall' => ['required', 'in:' . implode(',', array_keys($this->halls()))],
            'bio' => ['nullable', 'string', 'max:500'],
            'profile_image' => ['nullable', 'file', 'image', 'mimes:jpeg,png,gif,webp', 'max:5120'],
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
                'errors' => $validator->errors(),
            ]);
        }

        $newImageFile = null;

        if ($request->hasFile('profile_image')) {
            $uploadResult = $this->profileImageService->process($request->file('profile_image'), $userId);

            if (isset($uploadResult['error'])) {
                return response()->json([
                    'success' => false,
                    'errors' => ['profile_image' => [$uploadResult['error']]],
                ]);
            }

            $newImageFile = $uploadResult['filename'];
        }

        try {
            $user->name = trim($request->input('name'));
            $user->phone = trim($request->input('phone'));
            $user->department = trim($request->input('department'));
            $user->session = trim($request->input('session'));
            $user->room_number = trim($request->input('room_number'));
            $user->hall = $request->input('hall');
            $user->bio = trim($request->input('bio', ''));

            if ($newImageFile) {
                $this->profileImageService->delete($user->profile_pic);
                $user->profile_pic = $newImageFile;
            }

            $user->save();

            $request->session()->put([
                'user_name' => $user->name,
                'user_hall' => $user->hall,
                'user_avatar' => $user->profile_pic,
            ]);
        } catch (\Throwable $e) {
            if ($newImageFile) {
                $this->profileImageService->delete($newImageFile);
            }

            return response()->json([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully!',
            'profile_pic_url' => asset('uploads/profile/' . $user->profile_pic),
        ]);
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
