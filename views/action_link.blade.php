<a @if($id)
    id="{{ $action }}_{{ $id }}" 
@endif class="autolist-action {{ $action }}" title="{{ $title }}" href="{{ URL::to_action($controller_action,array($id)) }}">{{ $text }}</a>