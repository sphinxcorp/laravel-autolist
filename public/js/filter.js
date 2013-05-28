(function($) {
    $(function(){
        $('#autolist-filter-by').change(function (){
            $operators = $.parseJSON($(this).find(':selected').attr("data-filter-operators"));
            $operators_titles = $.parseJSON($(this).find(':selected').attr("data-filter-optitles"));
            select_list = '<select id="autolist-filter-op" name="filter_op" >';
            select_list += ('<option value="" selected="selected">Select an operator</option>');
            $.each($operators, function($key, $value){
                select_list += ('<option value="' + $key + '" data-filter-operator="' + $value + '">' + $operators_titles[$key] + '</option>');
            });
            select_list += '</select>';
            $op_select = $('#autolist-filter-operators').empty();
            $op_select.html('');
            $op_select.append(select_list);
            $widget = $('#autolist-filter-inputs').empty();
            $widget.html('');
        });
        $('#autolist-filter-operators').change(function(){
            $select_list = $('#autolist-filter-op');
            widget = $select_list.find(':selected').attr('data-filter-operator');
            $field_type = $.parseJSON( $('#autolist-filter-by').find(':selected').attr('data-filter-type') );
            if ( $.type($field_type) == 'object' ) {
                $field_type = 'enum';
            }
            st = '<input id="autolist-filter-text1" type="text" name="filter_str[]"/>';
            dt = '<input id="autolist-filter-text1" type="text" name="filter_str[]"/> <input id="autolist-filter-text2" type="text" name="filter_str[]"/>';
            $widget_select= $('#autolist-filter-inputs').empty();
            $widget_select.html('');
            if ( widget == 'st' ) {
                $widget_select.append(st);
            } else if ( widget == 'dt' ) {
                $widget_select.append(dt);
            } else if ( widget == 'select' ) {
                $enum_array = $.parseJSON($('#autolist-filter-by').find(':selected').attr('data-filter-type'));
                widget_select_list = '';
                widget_select_list += '<select multiple class="input-medium search-query" id="autolist-filter-select" name="filter_str[]">';
                $.each($enum_array, function($key, $value) {
                    widget_select_list += '<option value="' + $key + '" data-filter-str="' + $key + '">' + $value + '</option>';
                });
                widget_select_list += '</select>';
                $widget_select.append(widget_select_list);
            }
        });
    });
    
})($,undefined);
