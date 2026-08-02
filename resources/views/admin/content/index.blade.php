@extends('admin.layout')

@section('title', 'პლატფორმის მართვა')
@section('heading', 'პლატფორმის სრული მართვა')

@section('content')
<section class="cms-intro panel">
    <div>
        <p class="eyebrow">Content Management System</p>
        <h2>ყველაფერი, რასაც მომხმარებელი ხედავს — ერთი სივრციდან</h2>
        <p>შეცვალეთ ტექსტები, კონტაქტები, ჯგუფები, გუნდი, FAQ, გალერეა და ბლოგი. შენახული ცვლილებები მონაცემთა ბაზაში რჩება და საჯარო საიტზე ავტომატურად აისახება.</p>
    </div>
    <a class="primary" href="{{ route('home') }}" target="_blank" rel="noopener">საჯარო საიტის ნახვა ↗</a>
</section>

<nav class="cms-section-nav" aria-label="კონტენტის სექციები">
    <a href="#cms-texts">ტექსტები</a>
    <a href="#cms-group">ჯგუფები</a>
    <a href="#cms-team">გუნდი</a>
    <a href="#cms-faq">FAQ</a>
    <a href="#cms-gallery">გალერეა</a>
    <a href="#cms-blog">ბლოგი</a>
</nav>

<section class="cms-block" id="cms-texts">
    <div class="cms-block-head">
        <div><p class="eyebrow">საჯარო გვერდები</p><h2>ტექსტები და საკონტაქტო ინფორმაცია</h2><p>ცვლილებები ყველა შესაბამის ადგილას ავტომატურად გავრცელდება.</p></div>
    </div>

    <form method="post" action="{{ route('admin.content.texts.update') }}" class="cms-text-form">
        @csrf
        @method('put')

        @foreach($textSections as $section => $entries)
            <details class="cms-accordion" {{ $loop->first ? 'open' : '' }}>
                <summary><strong>{{ $sectionLabels[$section] ?? $section }}</strong><span>{{ count($entries) }} ველი</span></summary>
                <div class="cms-field-grid">
                    @foreach($entries as $entry)
                        <label class="{{ in_array($entry['type'], ['textarea', 'linebreak'], true) ? 'wide' : '' }}">
                            <span>{{ $entry['label'] }}</span>
                            @if(in_array($entry['type'], ['textarea', 'linebreak'], true))
                                <textarea name="content[{{ $entry['key'] }}]" rows="{{ $entry['type'] === 'linebreak' ? 3 : 5 }}">{{ old('content.'.$entry['key'], $entry['value']) }}</textarea>
                            @else
                                <input type="text" name="content[{{ $entry['key'] }}]" value="{{ old('content.'.$entry['key'], $entry['value']) }}">
                            @endif
                            <small>{{ $entry['key'] }}</small>
                        </label>
                    @endforeach
                </div>
            </details>
        @endforeach

        <div class="cms-save-bar"><span>ყველა ტექსტური ცვლილება ერთდროულად შეინახება.</span><button class="primary" type="submit">ტექსტების შენახვა</button></div>
    </form>
</section>

@foreach($itemTypeLabels as $type => $typeLabel)
<section class="cms-block" id="cms-{{ $type }}">
    <div class="cms-block-head">
        <div><p class="eyebrow">სტრუქტურული კონტენტი</p><h2>{{ $typeLabel }}</h2><p>შეგიძლიათ დაამატოთ, შეცვალოთ, დამალოთ, გადაალაგოთ ან წაშალოთ ჩანაწერები.</p></div>
        <span class="cms-count">{{ ($itemsByType[$type] ?? collect())->count() }} ჩანაწერი</span>
    </div>

    <details class="cms-create-box">
        <summary>+ ახალი ჩანაწერის დამატება</summary>
        <form method="post" action="{{ route('admin.content.items.store', $type) }}" enctype="multipart/form-data" class="cms-item-form">
            @csrf
            <div class="cms-field-grid">
                <label><span>{{ $type === 'team' ? 'სახელი და გვარი' : ($type === 'faq' ? 'კითხვა' : 'სათაური') }}</span><input name="title" required></label>
                <label><span>{{ $type === 'group' ? 'აღმზრდელი' : ($type === 'team' ? 'როლი' : ($type === 'gallery' ? 'ჯგუფი' : 'ქვესათაური')) }}</span><input name="subtitle"></label>
                <label class="wide"><span>{{ $type === 'faq' ? 'პასუხი' : 'აღწერა' }}</span><textarea name="body" rows="4"></textarea></label>
                <label><span>{{ $type === 'group' ? 'ტექნიკური გასაღები (მაგ. 3-4)' : ($type === 'team' ? 'ინიციალი' : ($type === 'gallery' ? 'თარიღი' : 'Badge')) }}</span><input name="badge"></label>
                <label><span>ფერი</span><input name="color" value="#A9D3C9" pattern="^#[0-9A-Fa-f]{6}$"></label>
                <label><span>რიგითობა</span><input type="number" name="sort_order" value="0" min="0"></label>
                <label class="check-label"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" checked><span>საჯაროდ გამოჩნდეს</span></label>
                @if(in_array($type, ['group', 'gallery'], true))
                    <label class="wide"><span>{{ $type === 'group' ? 'დამატებითი მონაცემები JSON ფორმატში' : 'დამატებითი მონაცემები (სურვილისამებრ)' }}</span><textarea name="meta_json" rows="{{ $type === 'group' ? 7 : 3 }}">{{ $type === 'group' ? json_encode(['free' => 0, 'total' => 20, 'schedule' => [['08:00', 'მიღება']]], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '{}' }}</textarea></label>
                @else
                    <input type="hidden" name="meta_json" value="{}">
                @endif
                @if($type === 'gallery')
                    <label><span>ფოტოს ატვირთვა</span><input type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>
                    <label><span>ფოტოს აღწერა (ALT)</span><input name="image_alt"></label>
                @endif
            </div>
            <button class="primary" type="submit">დამატება და შენახვა</button>
        </form>
    </details>

    <div class="cms-item-list">
        @forelse($itemsByType[$type] ?? collect() as $item)
            <article class="cms-item-card">
                <div class="cms-card-top">
                    <div class="cms-card-identity">
                        @if($item->image)
                            <img src="{{ route('content.item-image', $item) }}" alt="{{ $item->image_alt ?: $item->title }}">
                        @else
                            <i style="background:{{ $item->color ?: '#A9D3C9' }}">{{ $item->badge ?: mb_substr($item->title, 0, 1) }}</i>
                        @endif
                        <div><strong>{{ $item->title }}</strong><small>{{ $item->subtitle ?: $typeLabel }}</small></div>
                    </div>
                    <span class="status {{ $item->is_active ? 'status-active' : 'status-archived' }}">{{ $item->is_active ? 'აქტიური' : 'დამალული' }}</span>
                </div>

                <form method="post" action="{{ route('admin.content.items.update', $item) }}" enctype="multipart/form-data" class="cms-item-form">
                    @csrf
                    @method('patch')
                    <div class="cms-field-grid">
                        <label><span>სათაური</span><input name="title" value="{{ $item->title }}" required></label>
                        <label><span>ქვესათაური</span><input name="subtitle" value="{{ $item->subtitle }}"></label>
                        <label class="wide"><span>აღწერა</span><textarea name="body" rows="4">{{ $item->body }}</textarea></label>
                        <label><span>Badge / გასაღები / თარიღი</span><input name="badge" value="{{ $item->badge }}"></label>
                        <label><span>ფერი</span><input name="color" value="{{ $item->color ?: '#A9D3C9' }}" pattern="^#[0-9A-Fa-f]{6}$"></label>
                        <label><span>რიგითობა</span><input type="number" name="sort_order" value="{{ $item->sort_order }}" min="0"></label>
                        <label class="check-label"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" {{ $item->is_active ? 'checked' : '' }}><span>საჯაროდ გამოჩნდეს</span></label>
                        <label class="wide"><span>დამატებითი მონაცემები JSON</span><textarea name="meta_json" rows="{{ $type === 'group' ? 8 : 3 }}">{{ json_encode($item->meta ?: new stdClass(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</textarea></label>
                        @if($type === 'gallery')
                            <label><span>ახალი ფოტო</span><input type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>
                            <label><span>ფოტოს აღწერა (ALT)</span><input name="image_alt" value="{{ $item->image_alt }}"></label>
                            @if($item->image)<label class="check-label"><input type="checkbox" name="remove_image" value="1"><span>არსებული ფოტოს წაშლა</span></label>@endif
                        @endif
                    </div>
                    <div class="cms-form-actions">
                        <button class="primary" type="submit">ცვლილებების შენახვა</button>
                        <button class="cms-danger" type="submit" form="delete-item-{{ $item->id }}" onclick="return confirm('ნამდვილად წავშალოთ ეს ჩანაწერი?')">წაშლა</button>
                    </div>
                </form>
                <form id="delete-item-{{ $item->id }}" method="post" action="{{ route('admin.content.items.destroy', $item) }}">@csrf @method('delete')</form>
            </article>
        @empty
            <div class="empty-state">ამ სექციაში ჩანაწერი ჯერ არ არის.</div>
        @endforelse
    </div>
</section>
@endforeach

@include('admin.content.blog-manager')
@endsection
