<span class="<?php echo $sortable ? "sortable" : "" ?>"><?php echo $title; ?></span><?php
echo "&nbsp;";
if ($sortable):
    if ($attribute == $active_sort_by && 'ASC' == $active_sort_dir):
        echo "&uarr;";
    else:
        echo HTML::link($sort_url_asc, '↑', array('title' => 'Sort Ascending'), Request::secure());
    endif;
    echo "&nbsp;";
    if ($attribute == $active_sort_by && 'DESC' == $active_sort_dir):
        echo "&darr;";
    else:
        echo HTML::link($sort_url_desc, '↓', array('title' => 'Sort Descending'), Request::secure());
    endif;
endif;
?> 