<?php
/* Autolist form start */
if (count($filter_fields) > 0) {
    //Former::framework('Nude');
    //echo Former::open()->class('form-search')->method('get')->action('')->id('autolist-filter-form');
    ?>
<form class="form-search" method="GET" action="" id="autolist-filter-form">
    <select class="input-medium search-query" id="autolist-filter-by" name="filter_by">
        <option value="" selected="selected">Select</option>
        <?php foreach ($filter_fields as $field): ?>
        <option value="<?php echo e($field['attribute'])?>" data-filter-type='<?php echo json_encode($field['filter_type'])?>'><?php echo e($field['title'])?></option>
        <?php endforeach; ?>
    </select>
    <select class="input-medium search-query" id="autolist-filter-op" name="filter_op" data-filter-ops='<?php echo json_encode($filter_opmap)?>' data-filter-optitles='<?php echo json_encode($filter_optitles)?>'>
        <option value="" selected="selected">Select</option>
        <?php foreach ($filter_opmap[$filter_fields['venue_name']['filter_type']] as $operator => $widget): ?>
        <option value="<?php echo e($operator)?>" data-filter-operator='<?php echo json_encode($widget)?>'><?php echo e($filter_optitles[$operator])?></option>
        <?php endforeach; ?>
    </select>
    <span id="autolist-filter-inputs">
    </span>
    <button class="btn" type="submit" >Submit</button>
</form>
    <?php
    //echo Former::button('Search')->class('btn')->type('submit');
    //echo Former::close();
    //Former::framework('TwitterBootstrap');
}
/* Autolist form end */
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