<code><b>{{ $name }}</b></code>&nbsp; @if($type)<small>{{ $type }}</small>@endif @if(!$required)
    <i>optional</i>@endif @if($description ) | {!! $description !!}@endif <br>
