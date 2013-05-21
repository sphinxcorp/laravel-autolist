/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
(function($) {
    $(function(){
        $('#autolist-filter-by').change(function (){
            $fields = $.parseJSON($('#autolist-filter-by').attr("data-filter-types"));
            $field = $fields[$(this).val()]['filter_type'];
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
    });
    
})($,undefined);
