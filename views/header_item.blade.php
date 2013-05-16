<span class="{{ $sortable?"sortable":"" }}">{{ $title }}</span> 
 @if($sortable)
    @if($attribute == Input::query('sort_by',false) && 'ASC' == Input::query('sort_dir','ASC') )
     	&uarr;
    @else
    {{ HTML::link($sort_url_asc,'↑', array('title'=>'Sort Ascending'), Request::secure())}}
    @endif

    @if($attribute == Input::query('sort_by',false) && 'DESC' == Input::query('sort_dir','ASC'))
    &darr;
    @else
    {{ HTML::link($sort_url_desc,'↓', array('title'=>'Sort Descending'), Request::secure())}}
    @endif
 @endif