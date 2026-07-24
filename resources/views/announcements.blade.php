@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 800px; margin: 0 auto; padding: var(--space-5);">
    <div style="margin-bottom: var(--space-6);">
        <h1 style="font-size: var(--font-size-xl);">
            <i class="fas fa-bullhorn" style="color: var(--primary);"></i>
            Announcements
        </h1>
        <p style="color: var(--text-tertiary);">Important updates and news from the OpenShelf team</p>
    </div>

    @if ($selectedAnnouncement)
        <a href="{{ route('announcements.index') }}" class="btn btn-outline btn-sm mb-4">
            <i class="fas fa-arrow-left"></i> Back to All Announcements
        </a>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">{{ $selectedAnnouncement->title }}</h2>
                <div class="flex justify-between items-center mt-2">
                    <span class="badge badge-{{ $selectedAnnouncement->priority_badge }}">
                        {{ $selectedAnnouncement->priority_label }}
                    </span>
                    <span class="text-muted" style="font-size: var(--font-size-xs);">
                        <i class="far fa-calendar"></i> {{ $selectedAnnouncement->created_at?->format('F j, Y') }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div style="line-height: 1.8;">
                    {!! nl2br(e($selectedAnnouncement->content)) !!}
                </div>
            </div>
        </div>
    @else
        @if ($activeAnnouncements->isEmpty())
            <div class="card text-center" style="padding: var(--space-8);">
                <i class="fas fa-bullhorn" style="font-size: 3rem; color: var(--text-disabled); margin-bottom: var(--space-4);"></i>
                <h3>No Announcements</h3>
                <p class="text-muted">Check back later for updates from the OpenShelf team.</p>
            </div>
        @else
            @foreach ($activeAnnouncements as $announcement)
                @php
                    $isRead = in_array($announcement->id, $readIds, true);
                @endphp
                <div class="card mb-4 {{ $isRead ? '' : 'border-l-4 border-l-primary' }}">
                    <div class="card-body">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="card-title mb-0">
                                <a href="{{ route('announcements.index', ['id' => $announcement->id]) }}" class="text-dark hover:text-primary">
                                    {{ $announcement->title }}
                                </a>
                            </h3>
                            <span class="badge badge-{{ $announcement->priority_badge }}">
                                {{ $announcement->priority_label }}
                            </span>
                        </div>
                        <div class="announcement-preview">
                            {!! nl2br(e(Str::limit($announcement->content, 200, ''))) !!}
                            @if (strlen($announcement->content) > 200)
                                <a href="{{ route('announcements.index', ['id' => $announcement->id]) }}" class="text-primary">Read more</a>
                            @endif
                        </div>
                        <div class="flex justify-between items-center mt-3">
                            <span class="text-muted" style="font-size: var(--font-size-xs);">
                                <i class="far fa-calendar"></i> {{ $announcement->created_at?->format('M j, Y') }}
                            </span>
                            @if (! $isRead)
                                <span class="badge badge-primary">New</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    @endif
</div>
@endsection
