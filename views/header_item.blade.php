<span class="{{ $sortable?"sortable":"" }}">{{ $title }}</span> 
@if($sortable)
{{ HTML::link($sort_url_asc,'^', array(), Request::secure())}} {{ HTML::link($sort_url_desc,'v', array(), Request::secure())}}
@endif