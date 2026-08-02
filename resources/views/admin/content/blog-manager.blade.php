<section class="cms-block" id="cms-blog">
    <div class="cms-block-head">
        <div>
            <p class="eyebrow">ბლოგის მართვა</p>
            <h2>სტატიები და ქავერები</h2>
            <p>ქავერი ინახება PostgreSQL-safe ფორმატში. სტატუსის „გამოქვეყნებული“ არჩევისას ცარიელი თარიღი ავტომატურად შეივსება მიმდინარე დროით.</p>
        </div>
        <span class="cms-count">{{ $posts->count() }} სტატია</span>
    </div>

    <details class="cms-create-box" {{ $errors->has('source_url') ? 'open' : '' }}>
        <summary>↗ Marketer.ge-ის სტატიიდან დრაფტის შექმნა</summary>
        <form method="post" action="{{ route('admin.content.blog.import') }}" class="cms-item-form">
            @csrf
            <div class="cms-field-grid">
                <label class="wide">
                    <span>Marketer.ge-ის სტატიის ბმული</span>
                    <input type="url" name="source_url" value="{{ old('source_url') }}" placeholder="https://www.marketer.ge/ines-bagi-batumshi/" required>
                    <small>გადმოიტანს სათაურს, მოკლე აღწერას, გამოქვეყნების თარიღს, წყაროსა და ხელმისაწვდომ ქავერს. სტატია დრაფტად შეიქმნება.</small>
                </label>
            </div>
            <button class="primary" type="submit">ინფორმაციის გადმოტანა</button>
        </form>
    </details>

    <details class="cms-create-box">
        <summary>+ ახალი სტატიის ხელით შექმნა</summary>
        <form method="post" action="{{ route('admin.content.blog.store') }}" enctype="multipart/form-data" class="cms-item-form">
            @csrf
            <div class="cms-field-grid">
                <label class="wide"><span>სტატიის სათაური</span><input name="title" value="{{ old('title') }}" required></label>
                <label><span>კატეგორია</span><input name="category" value="{{ old('category') }}"></label>
                <label><span>სტატუსი</span><select name="status">@foreach($postStatuses as $key => $label)<option value="{{ $key }}" {{ old('status', 'draft') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></label>
                <label><span>გამოქვეყნების თარიღი</span><input type="datetime-local" name="published_at" value="{{ old('published_at') }}"></label>
                <label><span>რიგითობა</span><input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0"></label>
                <label class="wide"><span>მოკლე აღწერა</span><textarea name="excerpt" rows="3">{{ old('excerpt') }}</textarea></label>
                <label class="wide"><span>სტატიის სრული ტექსტი</span><textarea name="body" rows="8">{{ old('body') }}</textarea></label>
                <label><span>ქავერის ატვირთვა</span><input type="file" name="cover" accept="image/jpeg,image/png,image/webp"></label>
                <label><span>ქავერის ALT აღწერა</span><input name="cover_alt" value="{{ old('cover_alt') }}"></label>
            </div>
            <button class="primary" type="submit">სტატიის შექმნა</button>
        </form>
    </details>

    <div class="cms-item-list cms-blog-list">
        @forelse($posts as $post)
            <article class="cms-item-card">
                <div class="cms-card-top">
                    <div class="cms-card-identity">
                        @if($post->cover_image)
                            <img src="{{ route('content.blog-cover', $post) }}" alt="{{ $post->cover_alt ?: $post->title }}">
                        @else
                            <i class="cms-cover-placeholder">📝</i>
                        @endif
                        <div>
                            <strong>{{ $post->title }}</strong>
                            <small>{{ $post->category ?: 'კატეგორიის გარეშე' }} · {{ $post->published_at?->format('d.m.Y H:i') ?: 'თარიღის გარეშე' }}</small>
                            @if($post->source_url)
                                <small><a href="{{ $post->source_url }}" target="_blank" rel="noopener noreferrer">წყარო: {{ $post->source_name ?: parse_url($post->source_url, PHP_URL_HOST) }} ↗</a></small>
                            @endif
                        </div>
                    </div>
                    <span class="status {{ $post->status === 'published' ? 'status-active' : 'status-pending' }}">{{ $postStatuses[$post->status] ?? $post->status }}</span>
                </div>

                <form method="post" action="{{ route('admin.content.blog.update', $post) }}" enctype="multipart/form-data" class="cms-item-form">
                    @csrf
                    @method('patch')
                    <div class="cms-field-grid">
                        <label class="wide"><span>სათაური</span><input name="title" value="{{ $post->title }}" required></label>
                        <label><span>კატეგორია</span><input name="category" value="{{ $post->category }}"></label>
                        <label><span>სტატუსი</span><select name="status">@foreach($postStatuses as $key => $label)<option value="{{ $key }}" {{ $post->status === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></label>
                        <label><span>გამოქვეყნების თარიღი</span><input type="datetime-local" name="published_at" value="{{ $post->published_at?->format('Y-m-d\TH:i') }}"></label>
                        <label><span>რიგითობა</span><input type="number" name="sort_order" value="{{ $post->sort_order }}" min="0"></label>
                        <label class="wide"><span>მოკლე აღწერა</span><textarea name="excerpt" rows="3">{{ $post->excerpt }}</textarea></label>
                        <label class="wide"><span>სრული ტექსტი</span><textarea name="body" rows="8">{{ $post->body }}</textarea></label>
                        <label class="wide"><span>პირველწყაროს ბმული</span><input type="url" name="source_url" value="{{ $post->source_url }}"></label>
                        <label><span>წყაროს სახელი</span><input name="source_name" value="{{ $post->source_name }}"></label>
                        <label><span>წყაროზე გამოქვეყნების თარიღი</span><input type="datetime-local" name="source_published_at" value="{{ $post->source_published_at?->format('Y-m-d\TH:i') }}"></label>
                        <label><span>ახალი ქავერი</span><input type="file" name="cover" accept="image/jpeg,image/png,image/webp"></label>
                        <label><span>ქავერის ALT</span><input name="cover_alt" value="{{ $post->cover_alt }}"></label>
                        @if($post->cover_image)<label class="check-label"><input type="checkbox" name="remove_cover" value="1"><span>არსებული ქავერის წაშლა</span></label>@endif
                    </div>
                    <div class="cms-form-actions">
                        <button class="primary" type="submit">სტატიის შენახვა</button>
                        @if($post->status === 'published')
                            <a class="primary" href="{{ route('public.blog.show', ['slug' => $post->slug]) }}" target="_blank" rel="noopener">საჯაროდ ნახვა ↗</a>
                        @endif
                        <button class="cms-danger" type="submit" form="delete-post-{{ $post->id }}" onclick="return confirm('ნამდვილად წავშალოთ სტატია?')">წაშლა</button>
                    </div>
                </form>
                <form id="delete-post-{{ $post->id }}" method="post" action="{{ route('admin.content.blog.destroy', $post) }}">@csrf @method('delete')</form>
            </article>
        @empty
            <div class="empty-state">ბლოგის სტატია ჯერ არ არის.</div>
        @endforelse
    </div>
</section>
