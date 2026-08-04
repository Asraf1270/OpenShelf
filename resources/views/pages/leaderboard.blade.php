@extends('layouts.app')

@push('styles')
<style>
    :root {
        --primary: #2C3E50;
        --secondary: #4C9F8A;
        --accent: #3A7B6B;
        --bg: #F8F9FA;
        --surface: #ffffff;
        --text-main: #0F172A;
        --text-muted: #5A6C7D;
        --border: #E2E8F0;
        --gold: #FFD700;
        --silver: #C0C0C0;
        --bronze: #CD7F32;
        --glass-bg: rgba(255, 255, 255, 0.8);
        --glass-border: rgba(255, 255, 255, 0.2);
    }

    [data-theme="dark"] {
        --bg: #0F172A;
        --surface: #1E293B;
        --text-main: #F8F9FA;
        --text-muted: #94A3B8;
        --border: #334155;
        --glass-bg: rgba(30, 41, 59, 0.7);
        --glass-border: rgba(255, 255, 255, 0.1);
    }

    .leaderboard-container {
        max-width: 800px;
        margin: 6rem auto 4rem;
        padding: 0 1.5rem;
    }

    .leaderboard-header {
        text-align: center;
        margin-bottom: 4rem;
    }

    .leaderboard-header h1 {
        font-size: clamp(2.5rem, 6vw, 3.5rem);
        font-weight: 850;
        letter-spacing: -0.03em;
        margin-bottom: 1rem;
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .leaderboard-header p {
        color: var(--text-muted);
        font-size: 1.15rem;
        max-width: 600px;
        margin: 0 auto;
        line-height: 1.6;
    }

    .podium {
        display: flex;
        justify-content: center;
        align-items: flex-end;
        gap: 1.5rem;
        margin-bottom: 4rem;
        padding-top: 2rem;
    }

    .podium-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 2rem 1.5rem;
        width: 180px;
        position: relative;
        box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        text-decoration: none;
        color: inherit;
        cursor: pointer;
    }

    [data-theme="dark"] .podium-item {
        box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.3);
    }

    .podium-item:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.2);
    }

    .podium-item.rank-1 {
        height: 280px;
        border-color: var(--gold);
        background: linear-gradient(to bottom, var(--surface), rgba(255, 215, 0, 0.05));
    }

    .podium-item.rank-2 {
        height: 240px;
        border-color: var(--silver);
    }

    .podium-item.rank-3 {
        height: 210px;
        border-color: var(--bronze);
    }

    .rank-badge {
        position: absolute;
        top: -20px;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1.2rem;
        color: #fff;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }

    .rank-1 .rank-badge { background: linear-gradient(135deg, #FFDF00, #D4AF37); }
    .rank-2 .rank-badge { background: linear-gradient(135deg, #E0E0E0, #9E9E9E); }
    .rank-3 .rank-badge { background: linear-gradient(135deg, #CD7F32, #A0522D); }

    .podium-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid var(--surface);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        margin-bottom: 1rem;
    }

    .podium-name {
        font-weight: 700;
        font-size: 1.1rem;
        color: var(--text-main);
        text-align: center;
        margin-bottom: 0.5rem;
        width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .podium-points {
        color: var(--secondary);
        font-weight: 800;
        font-size: 1.25rem;
    }

    .boipoka-icon {
        color: var(--gold);
        margin-left: 0.25rem;
        font-size: 1.1rem;
    }

    .leaderboard-list {
        background: var(--surface);
        border-radius: 24px;
        border: 1px solid var(--border);
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    }

    .list-header {
        display: grid;
        grid-template-columns: 60px 1fr 100px;
        padding: 1rem 1.5rem;
        background: rgba(0,0,0,0.02);
        border-bottom: 1px solid var(--border);
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-size: 0.9rem;
    }

    .list-item {
        display: grid;
        grid-template-columns: 60px 1fr 100px;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--border);
        align-items: center;
        transition: background 0.2s ease;
        text-decoration: none;
        color: inherit;
        cursor: pointer;
    }

    .list-item:hover {
        background: rgba(76, 159, 138, 0.05);
    }

    .list-item:last-child {
        border-bottom: none;
    }

    .list-rank {
        font-weight: 700;
        color: var(--text-muted);
        font-size: 1.1rem;
    }

    .list-user {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .list-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }

    .list-name {
        font-weight: 600;
        color: var(--text-main);
    }

    .list-points {
        font-weight: 700;
        color: var(--secondary);
        text-align: right;
    }

    @media (max-width: 640px) {
        .podium {
            flex-direction: column;
            align-items: center;
            gap: 2rem;
            padding-top: 1rem;
        }
        
        .podium-item {
            width: 100%;
            height: auto !important;
            flex-direction: row;
            padding: 1.5rem;
            justify-content: flex-start;
        }

        .rank-badge {
            left: -10px;
            top: 50%;
            transform: translateY(-50%);
        }

        .podium-avatar {
            margin-bottom: 0;
            margin-left: 1.5rem;
            margin-right: 1.5rem;
            width: 60px;
            height: 60px;
        }

        .podium-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .list-header, .list-item {
            grid-template-columns: 40px 1fr 80px;
            padding: 1rem;
        }
    }
</style>
@endpush

@section('content')
<main class="leaderboard-container">
    <section class="leaderboard-header">
        <h1>Monthly Boipoka</h1>
        <p>Celebrating our top contributors! Lend and borrow books to earn points and claim the prestigious Boipoka badge.</p>
    </section>

    @if($topUsers->isEmpty())
        <div style="text-align: center; padding: 4rem 1rem; background: var(--surface); border-radius: 20px; border: 1px solid var(--border);">
            <i class="fas fa-trophy" style="font-size: 3rem; color: var(--border); margin-bottom: 1rem;"></i>
            <h2 style="color: var(--text-muted);">No points awarded yet this month.</h2>
            <p style="color: var(--text-muted); margin-top: 0.5rem;">Start sharing books to get on the leaderboard!</p>
        </div>
    @else
        <div class="podium">
            @php
                $rank2 = $topUsers->get(1);
                $rank1 = $topUsers->get(0);
                $rank3 = $topUsers->get(2);
            @endphp

            @if($rank2)
            <a href="{{ route('profile', ['id' => $rank2->id]) }}" class="podium-item rank-2">
                <div class="rank-badge">2</div>
                <img src="{{ $rank2->profile_image_url }}" alt="{{ $rank2->name }}" class="podium-avatar">
                <div class="podium-info">
                    <div class="podium-name">{{ $rank2->name }}</div>
                    <div class="podium-points">{{ $rank2->boipoka_points }} pts</div>
                </div>
            </a>
            @endif

            @if($rank1)
            <a href="{{ route('profile', ['id' => $rank1->id]) }}" class="podium-item rank-1">
                <div class="rank-badge">1</div>
                <img src="{{ $rank1->profile_image_url }}" alt="{{ $rank1->name }}" class="podium-avatar">
                <div class="podium-info">
                    <div class="podium-name">
                        {{ $rank1->name }}
                        @if($rank1->boipoka_badge)
                            <i class="fas fa-certificate boipoka-icon" title="Boipoka Winner"></i>
                        @endif
                    </div>
                    <div class="podium-points">{{ $rank1->boipoka_points }} pts</div>
                </div>
            </a>
            @endif

            @if($rank3)
            <a href="{{ route('profile', ['id' => $rank3->id]) }}" class="podium-item rank-3">
                <div class="rank-badge">3</div>
                <img src="{{ $rank3->profile_image_url }}" alt="{{ $rank3->name }}" class="podium-avatar">
                <div class="podium-info">
                    <div class="podium-name">{{ $rank3->name }}</div>
                    <div class="podium-points">{{ $rank3->boipoka_points }} pts</div>
                </div>
            </a>
            @endif
        </div>

        @if($topUsers->count() > 3)
        <div class="leaderboard-list">
            <div class="list-header">
                <div>Rank</div>
                <div>User</div>
                <div style="text-align: right;">Points</div>
            </div>
            @foreach($topUsers->skip(3) as $index => $user)
            <a href="{{ route('profile', ['id' => $user->id]) }}" class="list-item">
                <div class="list-rank">#{{ $index + 4 }}</div>
                <div class="list-user">
                    <img src="{{ $user->profile_image_url }}" alt="{{ $user->name }}" class="list-avatar">
                    <span class="list-name">{{ $user->name }}</span>
                </div>
                <div class="list-points">{{ $user->boipoka_points }}</div>
            </a>
            @endforeach
        </div>
        @endif
    @endif
</main>
@endsection
