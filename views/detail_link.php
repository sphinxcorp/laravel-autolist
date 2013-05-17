<a id="<?php echo "{$action}_{$id}_{$attribute}" ; ?>" 
   class="autolist-action <?php echo $action ; ?>" 
   title="<?php echo e($raw_value) ; ?>" 
   href="<?php echo URL::to_action($controller_action,array($id)) ; ?>">
    <?php echo $value ; ?>
</a>