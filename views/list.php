<?php
if (count($filter_fields) > 0) {
    ?>
    <form method="GET" action="" id="autolist-filter-form">
        <select id="autolist-filter-by" name="filter_by">
            <option value="" <?php echo !Input::get('filter_by')?'selected="selected"':'' ?>>Select a field</option>
            <?php foreach ($filter_fields as $field): ?>
            <?php $operators = $filter_opmap[is_array($field['filter_type'])?'enum':$field['filter_type']] ?>
            <?php $optitles = array() ?>
            <?php foreach ($operators as $o => $w): ?>
            <?php $optitles[$o] = $filter_optitles[$o] ?>
            <?php endforeach ?>
            <option value="<?php echo e($field['attribute'])?>" <?php echo (Input::get('filter_by')&&Input::get('filter_by')==e($field['attribute']))?'selected="selected"':'' ?> data-filter-type='<?php echo json_encode($field['filter_type'])?>' data-filter-operators='<?php echo json_encode($operators)?>' data-filter-optitles='<?php echo json_encode($optitles)?>' ><?php echo e($field['title'])?></option>
            <?php endforeach; ?>
        </select>
        <span id="autolist-filter-operators">
        <?php if (Input::get('filter_by')) { ?>
        <select id="autolist-filter-op" name="filter_op" data-filter-ops='<?php echo json_encode($filter_opmap)?>' data-filter-optitles='<?php echo json_encode($filter_optitles)?>'>
            <option value="" <?php if (!Input::get('filter_op')) echo 'selected="selected"' ?>>Select an operator</option>
            <?php $key = $filter_fields[Input::get('filter_by')]['filter_type'] ?>
            <?php if (is_array($key)) $key = 'enum' ?>
            <?php foreach ($filter_opmap[$key] as $operator => $widget): ?>
            <option value="<?php echo e($operator)?>" <?php if (Input::get('filter_op')&&Input::get('filter_op')==$operator) echo 'selected="selected"' ?>  data-filter-operator='<?php echo $widget?>'><?php echo e($filter_optitles[$operator])?></option>
            <?php endforeach; ?>
        </select>
        <?php } ?>
        </span>
        <span id="autolist-filter-inputs">
            <?php if ($key!='enum' && count(Input::get('filter_str')) == 1) { ?>
            <input id="autolist-filter-text1" value="<?php echo implode(',',Input::get('filter_str')) ?>" type="text" name="filter_str[]"/>
            <?php } else if ($key != 'enum' && count(Input::get('filter_str')) == 2) { ?>
            <?php $text = Input::get('filter_str') ?>
            <input id="autolist-filter-text1" value="<?php echo $text[0] ?>" type="text" name="filter_str[]"/> <input id="autolist-filter-text2" value="<?php echo $text[1] ?>" type="text" name="filter_str[]"/>
            <?php } else if ($key == 'enum') { ?>
            <select multiple class="input-medium search-query" id="autolist-filter-select" name="filter_str[]">
                <?php foreach ($filter_fields[Input::get('filter_by')]['filter_type'] as $option => $show): ?>
                <option value="<?php echo $option ?>" <?php if (in_array($option,Input::get('filter_str'))) echo 'selected="selected"' ?> data-filter-str="<?php echo $option ?>"><?php echo $show ?></option>
                <?php endforeach; ?>
            </select>
            <?php } ?>
        </span>
        <button type="submit" >Submit</button>
    </form>
    <?php
}
?>
<table class="autolist">
    <caption><?php echo $title ; ?> </caption>
    <thead>
        <tr>
            <?php foreach ($header_columns as $attribute => $header_column): ?>
                <th>
                    <?php echo $header_column ; ?> 
                </th>
            <?php endforeach; ?> 
            <?php if ($has_item_actions): ?> 
                <th>Actions</th>
            <?php endif; ?> 
        </tr>
    </thead>
    <tbody>
        <?php if (empty($items)): ?> 
            <tr>
                <td colspan="<?php echo count($header_columns) + ($has_item_actions?1:0) ; ?> ">There are no items to show.</td>
            </tr>
        <?php else: ?> 
            <?php foreach ($items as $item): ?> 
                <tr>
                    <?php foreach (array_keys($header_columns) as $attribute): ?> 
                        <td><?php echo $item[$attribute] ; ?> </td>
                    <?php endforeach; ?> 
                    <?php if ($has_item_actions): ?> 
                        <td><?php echo implode(' | ', $item['action_links']) ; ?> </td>
                    <?php endif; ?> 
                </tr>
            <?php endforeach; ?> 
        <?php endif; ?> 
    </tbody>
    <?php if (!empty($global_action_links) || $page_links): ?> 
        <tfoot>
            <tr>
                <td colspan="<?php echo count($header_columns) + ($has_item_actions?1:0) -1 ; ?> ">
                    <?php echo $page_links ; ?> 
                </td>
                <td><?php if (!empty($global_action_links)): ?> 
                        <?php echo implode(' | ', $global_action_links) ; ?> 
                    <?php endif; ?> 
                </td>
            </tr>
        </tfoot>
    <?php endif; ?> 
</table>