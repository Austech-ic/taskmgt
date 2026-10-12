@props(['title', 'subtitle' => null])
<div class="page-head">
    <div>
        <h1>{{ $title }}</h1>
        @if($subtitle)<p>{{ $subtitle }}</p>@endif
    </div>
    @if(trim($slot))<div class="row">{{ $slot }}</div>@endif
</div>
