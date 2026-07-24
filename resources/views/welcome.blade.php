@extends('layouts.app')

@section('content')
<div style="max-width: 1200px; margin: 0 auto; padding: 2rem 1.5rem;">
    <!-- Welcome Section -->
    <div style="text-align: center; margin-bottom: 3rem; padding: 2rem 0;">
        <h1 style="font-size: 2.5rem; margin-bottom: 1rem;">Welcome to OpenShelf</h1>
        <p style="font-size: 1.125rem; color: var(--text-secondary); max-width: 600px; margin: 0 auto;">
            Share books, share knowledge. A peer-to-peer book sharing platform for the campus community.
        </p>
    </div>

    <!-- CTA Buttons -->
    <div style="display: flex; gap: 1rem; justify-content: center; margin-bottom: 3rem; flex-wrap: wrap;">
        @if(session('user_id'))
            <a href="/books" style="background: var(--secondary); color: white; padding: 0.75rem 2rem; border-radius: 0.5rem; text-decoration: none; font-weight: 600; transition: all 0.2s; display: inline-block;">
                Browse Books
            </a>
            <a href="/add-book" style="background: white; color: var(--secondary); border: 2px solid var(--secondary); padding: 0.75rem 2rem; border-radius: 0.5rem; text-decoration: none; font-weight: 600; transition: all 0.2s; display: inline-block;">
                Add Your Book
            </a>
        @else
            <a href="/login" style="background: var(--secondary); color: white; padding: 0.75rem 2rem; border-radius: 0.5rem; text-decoration: none; font-weight: 600; transition: all 0.2s; display: inline-block;">
                Login
            </a>
            <a href="/register" style="background: white; color: var(--secondary); border: 2px solid var(--secondary); padding: 0.75rem 2rem; border-radius: 0.5rem; text-decoration: none; font-weight: 600; transition: all 0.2s; display: inline-block;">
                Sign Up
            </a>
        @endif
    </div>

    <!-- Featured Section -->
    <div style="background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 1rem; padding: 2rem; margin-bottom: 3rem;">
        <h2 style="margin-bottom: 1rem;">Features</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
            <div>
                <h3 style="font-size: 1.125rem; margin-bottom: 0.5rem;"><i class="fas fa-book"></i> Share Books</h3>
                <p style="color: var(--text-secondary);">Add books from your collection and share them with the community.</p>
            </div>
            <div>
                <h3 style="font-size: 1.125rem; margin-bottom: 0.5rem;"><i class="fas fa-handshake"></i> Connect</h3>
                <p style="color: var(--text-secondary);">Discover fellow book lovers and borrow from their collections.</p>
            </div>
            <div>
                <h3 style="font-size: 1.125rem; margin-bottom: 0.5rem;"><i class="fas fa-free"></i> Free</h3>
                <p style="color: var(--text-secondary);">Completely free to use. No hidden fees or charges.</p>
            </div>
        </div>
    </div>

    <!-- How It Works -->
    <div style="background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 1rem; padding: 2rem;">
        <h2 style="margin-bottom: 1rem;">How It Works</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
            <div style="text-align: center;">
                <div style="font-size: 2rem; margin-bottom: 1rem;"><i class="fas fa-user-plus"></i></div>
                <h3>1. Sign Up</h3>
                <p style="color: var(--text-secondary);">Create your account and join the community.</p>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 2rem; margin-bottom: 1rem;"><i class="fas fa-plus-circle"></i></div>
                <h3>2. Add Books</h3>
                <p style="color: var(--text-secondary);">List the books you want to share.</p>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 2rem; margin-bottom: 1rem;"><i class="fas fa-search"></i></div>
                <h3>3. Browse</h3>
                <p style="color: var(--text-secondary);">Search and find books you're looking for.</p>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 2rem; margin-bottom: 1rem;"><i class="fas fa-exchange-alt"></i></div>
                <h3>4. Borrow</h3>
                <p style="color: var(--text-secondary);">Send requests and borrow books from others.</p>
            </div>
        </div>
    </div>
</div>
@endsection
