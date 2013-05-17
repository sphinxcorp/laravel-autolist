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