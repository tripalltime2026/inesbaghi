@extends('admin.layout')

@section('title', 'მიმოხილვა')
@section('heading', 'დღის მიმოხილვა')

@section('content')
<section class="admin-section compact">
    <div class="metric-grid">
        <article class="metric-card"><span>ახალი განაცხადები</span><strong>{{ $newApplications }}</strong><a href="{{ route('admin.admissions.index', ['status' => 'new']) }}">ნახვა</a></article>
        <article class="metric-card"><span>დღევანდელი ტურები</span><strong>{{ $toursToday }}</strong><a href="{{ route('admin.admissions.index', ['tour' => 'today']) }}">კალენდარი</a></article>
        <article class="metric-card"><span>ბავშვები</span><strong>{{ $children }}</strong><small>სისტემაში რეგისტრირებული</small></article>
        <article class="metric-card"><span>აქტიური ჩარიცხვები</span><strong>{{ $activeEnrollments }}</strong><small>მოქმედ ჯგუფებში</small></article>
    </div>
</section>

<section class="admin-section compact">
    <div class="panel-heading">
        <div><p class="eyebrow">Admissions CRM</p><h2>ბოლო განაცხადები</h2></div>
        <a class="secondary" href="{{ route('admin.admissions.index') }}">ყველა განაცხადი</a>
    </div>

    <div class="table-wrap">
        <table class="admin-table">
            <thead><tr><th>ბავშვი / მშობელი</th><th>ჯგუფი</th><th>სტატუსი</th><th>პასუხისმგებელი</th><th>თარიღი</th><th></th></tr></thead>
            <tbody>
            @forelse ($recentApplications as $application)
                <tr>
                    <td><strong>{{ $application->child_name }}</strong><small>{{ $application->parent_name }} · {{ $application->phone }}</small></td>
                    <td>{{ $application->preferred_group }} წელი</td>
                    <td><span class="status status-{{ $application->status }}">{{ \App\Models\AdmissionApplication::STATUSES[$application->status] ?? $application->status }}</span></td>
                    <td>{{ $application->assignedTo?->name ?? 'არ არის დანიშნული' }}</td>
                    <td>{{ $application->created_at->format('d.m.Y H:i') }}</td>
                    <td><a class="row-link" href="{{ route('admin.admissions.show', $application) }}">გახსნა →</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty-state">განაცხადები ჯერ არ არის.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="admin-section compact">
    <div class="metric-grid small">
        <article class="metric-card"><span>ყველა მომხმარებელი</span><strong>{{ $users }}</strong></article>
        <article class="metric-card"><span>დასამტკიცებელი წევრები</span><strong>{{ $pendingUsers }}</strong></article>
        <article class="metric-card"><span>ყველა განაცხადი</span><strong>{{ $applications }}</strong></article>
    </div>
</section>
@endsection
