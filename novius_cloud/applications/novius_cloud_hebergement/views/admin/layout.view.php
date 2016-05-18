<?php
$form_id = uniqid('nc_hebergement_form');
$view['params']['form_id'] = $form_id;
?>

<div id="<?= $form_id ?>" class="page">
    <div class="line">
        <div class="col c12">
            <form action="<?= $form_action ?>" method="post">
                <?= View::forge($view['view'], $view['params'], false); ?>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
    require(['jquery-nos'], function($) {
        var $container = $('#<?= $form_id ?>'),
            $form = $container.find('form');
        $form.submit(function(e) {
            e.preventDefault();
            $container.load('<?= $form_action ?>?' + $(this).serialize(), function() {
                $container.find(':first').unwrap();
            });
        });
        $form.find('select.js_autosubmit').change(function() {
            $form.submit();
        });
        $form.nosFormUI();
        $container.nosOnShow();
    });
</script>