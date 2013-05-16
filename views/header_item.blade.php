<span class="{{ $sortable?"sortable":"" }}">{{ $title }}</span> 
 @if($sortable)
    @if($attribute == Input::query('sort_by',false) && 'ASC' == Input::query('sort_dir',false) )
    ^
    @else
    {{ HTML::link($sort_url_asc,'^', array(), Request::secure())}}
    @endif

    @if($attribute == Input::query('sort_by',false) && 'DESC' == Input::query('sort_dir',false))
    v
    @else
    {{ HTML::link($sort_url_desc,'v', array(), Request::secure())}}
    @endif
 @endif

