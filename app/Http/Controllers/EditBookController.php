<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\User;
use App\Services\BookCoverService;
use App\Services\ProfileImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EditBookController extends Controller
{
    public function __construct(
        private BookCoverService $bookCoverService,
        private ProfileImageService $profileImageService,
    ) {
    }

    public function edit(Request $request)
    {
        $userId = $request->session()->get('user_id');

        if (! $userId) {
            $request->session()->put('redirect_after_login', $request->fullUrl());

            return redirect()->route('login');
        }

        $bookId = $request->query('id');

        if (! $bookId) {
            return redirect()->route('books');
        }

        $book = Book::find($bookId);

        if (! $book) {
            return redirect()->route('books');
        }

        if ($book->owner_id !== $userId) {
            return redirect()
                ->route('book.show', ['id' => $bookId])
                ->with('error', 'You do not have permission to edit this book');
        }

        $user = User::query()
            ->select('id', 'name', 'profile_pic')
            ->find($userId);

        return view('edit-book', [
            'seoTitle' => 'Edit Book - OpenShelf',
            'seoDesc' => 'Update your book details on OpenShelf.',
            'book' => $book,
            'user' => $user,
            'categories' => $this->categories(),
            'conditions' => $this->conditions(),
            'coverThumbUrl' => $book->cover_url,
            'avatarUrl' => $user?->profile_image_url ?? asset('images/avatars/default.jpg'),
        ]);
    }

    public function update(Request $request)
    {
        $userId = $request->session()->get('user_id');

        if (! $userId) {
            $request->session()->put('redirect_after_login', $request->fullUrl());

            return redirect()->route('login');
        }

        $bookId = $request->query('id');

        if (! $bookId) {
            return redirect()->route('books');
        }

        $book = Book::find($bookId);

        if (! $book) {
            return redirect()->route('books');
        }

        if ($book->owner_id !== $userId) {
            return redirect()
                ->route('book.show', ['id' => $bookId])
                ->with('error', 'You do not have permission to edit this book');
        }

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'min:2', 'max:200'],
            'author' => ['required', 'string', 'min:2', 'max:100'],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'category' => ['required', 'string', 'in:' . implode(',', $this->categories())],
            'condition' => ['required', 'string', 'in:' . implode(',', array_keys($this->conditions()))],
            'cover_image' => ['nullable', 'file', 'image', 'mimes:jpeg,png,gif,webp', 'max:10240'],
            'user_profile_pic' => ['nullable', 'file', 'image', 'mimes:jpeg,png,gif,webp', 'max:5120'],
        ], [
            'title.required' => 'Book title is required',
            'title.min' => 'Title must be at least 2 characters',
            'title.max' => 'Title must be less than 200 characters',
            'author.required' => 'Author name is required',
            'author.min' => 'Author name must be at least 2 characters',
            'author.max' => 'Author name must be less than 100 characters',
            'description.required' => 'Description is required',
            'description.min' => 'Description must be at least 20 characters',
            'description.max' => 'Description must be less than 5000 characters',
            'category.required' => 'Please select a category',
            'condition.required' => 'Please select a condition',
        ]);

        if ($validator->fails()) {
            return back()->withInput()->withErrors($validator);
        }

        $newCoverImage = null;

        if ($request->hasFile('cover_image')) {
            $uploadResult = $this->bookCoverService->process($request->file('cover_image'), $bookId);

            if (isset($uploadResult['error'])) {
                return back()->withInput()->withErrors(['cover_image' => $uploadResult['error']]);
            }

            $newCoverImage = $uploadResult['filename'];

            if ($book->cover_image) {
                $this->bookCoverService->delete($book->cover_image);
            }
        }

        if ($request->hasFile('user_profile_pic')) {
            $user = User::find($userId);

            if ($user) {
                $profileResult = $this->profileImageService->process($request->file('user_profile_pic'), $userId);

                if (isset($profileResult['error'])) {
                    if ($newCoverImage) {
                        $this->bookCoverService->delete($newCoverImage);
                    }

                    return back()->withInput()->withErrors(['user_profile_pic' => $profileResult['error']]);
                }

                $this->profileImageService->delete($user->profile_pic);
                $user->profile_pic = $profileResult['filename'];
                $user->save();

                $request->session()->put('user_avatar', $profileResult['filename']);
            }
        }

        try {
            $book->update([
                'title' => trim($request->input('title')),
                'author' => trim($request->input('author')),
                'description' => trim($request->input('description')),
                'category' => $request->input('category'),
                'condition' => $request->input('condition'),
                'cover_image' => $newCoverImage ?? $book->cover_image,
            ]);
        } catch (\Throwable) {
            if ($newCoverImage) {
                $this->bookCoverService->delete($newCoverImage);
            }

            return back()->withInput()->withErrors([
                'general' => 'Failed to save book. Please try again.',
            ]);
        }

        return redirect()
            ->route('book.show', ['id' => $bookId])
            ->with('success', 'Book updated successfully!');
    }

    private function categories(): array
    {
        return [
            'Fiction',
            'Self Development',
            'Science',
            'Religion',
            'Islamic',
            'Technology',
            'Business',
            'Health',
            'Arts',
            'Education',
            'History',
            'Biography',
            'Law',
        ];
    }

    private function conditions(): array
    {
        return [
            'New' => 'Brand new, never read',
            'Like New' => 'Perfect condition, no wear',
            'Very Good' => 'Minor wear, clean copy',
            'Good' => 'Normal wear, may have markings',
            'Acceptable' => 'Well-read, usable condition',
            'Poor' => 'Damaged, but readable',
        ];
    }
}
