(function($) {
    $(function(){
        $('#autolist-filter-by').change(function (){
            $field = $.parseJSON($(this).find(':selected').attr('data-filter-type')); //$fields[$(this).val()]['filter_type'];
            if ( $.type($field) == 'object' ) {
                $field = 'enum';
            }
            $operators = $.parseJSON($('#autolist-filter-op').attr("data-filter-ops"));
            $operators = $operators[$field];
            $operators_title = $.parseJSON($('#autolist-filter-op').attr("data-filter-optitles"));
            $op_select = $('#autolist-filter-op');
            $op_select.html('');
            $op_select.append('<option value="" selected="selected">Select</option>');
            $.each($operators, function($key, $value){
                $op_select.append('<option value="' + $key + '" data-filter-operator="' + $value + '">' + $operators_title[$key] + '</option>');
            });
        });
        $('#autolist-filter-op').change(function(){
            widget = $(this).find(':selected').attr('data-filter-operator');
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
