<a <?php if ($id): ?> id="<?php echo "{$action}_{$id}"; ?>" <?php endif; ?> 
                      class="autolist-action <?php echo $action; ?>" 
                      title="<?php echo $title; ?>" 
                      href="<?php echo URL::to_action($controller_action, array($id)); ?>">
                          <?php echo $text; ?>
</a>