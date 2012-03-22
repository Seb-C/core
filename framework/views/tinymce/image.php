<?php
/**
 * NOVIUS OS - Web OS for digital communication
 *
 * @copyright  2011 Novius
 * @license    GNU Affero General Public License v3 or (at your option) any later version
 *             http://www.gnu.org/licenses/agpl-3.0.html
 * @link http://www.novius-os.org
 */
?>
<?php
	$mp3view = (string) Request::forge('cms/admin/media/list/index')->execute(array('image_pick'))->response();
?>
<div id="<?= $uniqid = uniqid('tabs_') ?>">
	<ul class="tabs">
		<li><a href="#<?= $id_library = $uniqid.'_library' ?>"><?= __('1. Pick your image') ?></a></li>
		<li><a href="#<?= $id_properties = $uniqid.'_properties' ?>"><?= __('2. Set the properties') ?></a></li>
	</ul>
	<div id="<?= $id_library ?>" style="width: 100%; padding: 0;"></div>

	<form action="#" id="<?= $uniqid_form = uniqid('form_') ?>">
		<div id="<?= $id_properties ?>">
			<table class="fieldset">
				<tr>
					<th><label><?= __('Title:') ?> </label></th>
					<td><input type="text" name="title" data-id="title" size="30" /></td>
				</tr>
				<tr>
					<th><label><?= __('Description:') ?> </label></th>
					<td><input type="text" name="alt" data-id="alt" size="30" /> &nbsp; <label><input type="checkbox" data-id="same_title_alt" checked> &nbsp;<?= strtr(__('Use {field}'), array('{field}' => __('title'))) ?></label></td>
				</tr>
				<tr>
					<th><label><?= __('Width:') ?> </label></th>
					<td><input type="text" name="width" data-id="width" size="5" /> &nbsp; <label><input type="checkbox" data-id="proportional" checked> &nbsp;<?= __('Keep proportions') ?></label></td>
				</tr>
				<tr>
					<th><label><?= __('Height:') ?> </label></th>
					<td><input type="text" name="height" data-id="height" readonly /></td>
				</tr>
				<tr>
					<th><label><?= __('Style:') ?> </label></th>
					<td><input type="text" name="style" data-id="style" size="50" /></td>
				</tr>
				<tr>
					<th></th>
					<td> <button type="submit" class="primary" data-icon="check" data-id="save"><?= __('Insert this image') ?></button> &nbsp; <?= __('or') ?> &nbsp; <a data-id="close" href="#"><?= __('Cancel') ?></a></td>
				</tr>
			</table>
		</div>
	</form>
</div>

<script type="text/javascript">
require(['jquery-nos', 'jquery-ui', 'jquery'], function($) {
	$(function() {

		var $container = $('#<?= $uniqid ?>'),
			getMargin = function(el) {
				return el.outerHeight(true) - el.height();
			};

		require(['static/cms/js/vendor/wijmo/js/jquery.wijmo.wijtabs.js'], function() {
			setTimeout(function() {
				$container.wijtabs({
					alignment: 'left',
					load: function(e, ui) {
						var margin = $(ui.panel).outerHeight(true) - $(ui.panel).innerHeight();
						$(ui.panel).height($('#<?= $uniqid ?>').parent().height() - margin);
					}
				});
                $container.find('> ul').css({
                    width : '18%'
                });
                $container.find('> div').css({
                    width : '81%'
                });

				var $dialog_content = $container.closest('.ui-dialog-content');
				var $tabs = $container.find('.tabs');

				var $properties = $('#<?= $id_properties ?>');
				var $library    = $('#<?= $id_library ?>');

				var margin = 0;

				margin += getMargin($dialog_content);
				margin += getMargin($tabs);

				var height = $container.parent().height() - margin;

				$tabs.height(height);
				$properties.height(height - getMargin($properties));
				$library.css({padding:0, margin:0}).height(height);

				// Now tabs are created and the appropriate dimensions are set, initialize the mp3grid
				$library.html(<?= \Format::forge()->to_json($mp3view) ?>);

				var $dialog = $container.closest('.ui-dialog-content');
				$dialog.bind('select.media', function(e, data) {
					tinymce_image_select(data);
				});

				$container.find('a[data-id=close]').click(function(e) {
					$dialog.wijdialog('close');
					e.preventDefault();
				});

				$container.find('input[data-id=save]').click(function(e) {
					var img = $('<img />');

					if (!media || !media.id) {
						alert(<?= \Format::forge()->to_json(__('Please choose an image first')) ?>);
						return;
					}

					img.attr('height', $height.val());
					img.attr('width',  $width.val());
					img.attr('title',  $title.val());
					img.attr('alt',    $alt.val());
					img.attr('style',  $style.val());

					img.attr('data-media', JSON.stringify(media));
					img.attr('src', base_url + media.path);

					$dialog.trigger('insert.media', img);
					e.stopPropagation();
					e.preventDefault();
				});

				$.nos.ui.form('#<?= $uniqid ?>');
			}, 1);
		});

		var base_url = '<?= \Uri::base(true) ?>';

		var $height = $container.find('input[data-id=height]');
		var $width  = $container.find('input[data-id=width]');
		var $title  = $container.find('input[data-id=title]');
		var $alt    = $container.find('input[data-id=alt]');
		var $style  = $container.find('input[data-id=style]');

		var $proportional   = $container.find('input[data-id=proportional]');
		var $same_title_alt = $container.find('input[data-id=same_title_alt]');

		var media = null;

		var tinymce_image_select = function(media_json, image_dom) {
			media = media_json;

			if (image_dom == null)
			{
				$height.val(media_json.height);
				$width.val(media_json.width);
				$title.val(media_json.title);
				$alt.val(media_json.title);
				$style.val('');

				$($('#<?= $uniqid ?> li a').get(1)).click();
				return;
			}

			$height.val(image_dom.attr('height'));
			$width.val(image_dom.attr('width'));
			$title.val(image_dom.attr('title'));
			$alt.val(image_dom.attr('alt'));
			$style.val(image_dom.attr('style'));

			if (media && (Math.round($width.val() * media.height / media.width) != $height.val())) {
				$proportional.prop('checked', false).removeAttr('checked', true).change();
			}

			if ($title.val() != $alt.val())
			{
				$same_title_alt.prop('checked', false).removeAttr('checked').change();
			}
		}

		$('#<?= $uniqid_form ?>').submit(function(e) {
			$container.find('input[data-id=save]').triggerHandler('click');
			e.stopPropagation();
			e.preventDefault();
		});

		// Proportianal width & height
		$width.bind('change keyup', function() {
			if ($proportional.is(':checked') && media && media.width && media.height) {
				$height.val(Math.round($width.val() * media.height / media.width));
			}
		});
		$proportional.change(function() {
			if ($(this).is(':checked')) {
				$height.attr('readonly', true).addClass('ui-state-disabled').removeClass('ui-state-default');
				$width.triggerHandler('change');
			} else {
				$height.removeAttr('readonly').addClass('ui-state-default').removeClass('ui-state-disabled');
			}
		}).triggerHandler('change');

		// Same title and description (alt)
		$title.bind('change keyup', function() {
			if ($same_title_alt.is(':checked')) {
				$alt.val($title.val());
			}
		});
		$same_title_alt.change(function() {
			if ($(this).is(':checked')) {
				$alt.attr('readonly', true).addClass('ui-state-disabled').removeClass('ui-state-default');
			} else {
				$alt.removeAttr('readonly').addClass('ui-state-default').removeClass('ui-state-disabled');
			}
		}).triggerHandler('change');


		var tinymce = $.nos.data('tinymce');
		var ed = tinymce.editor;
		var e = ed.selection.getNode();

		// Editing the current image
		if (e.nodeName == 'IMG')
		{
			var $img = $(e);
			var media_id = $img.data('media-id');

			// No data available yet, we need to fetch them
			if (media_id) {

				$.ajax({
					method: 'GET',
					url: base_url + 'admin/media/info/media/' + media_id,
					dataType: 'json',
					success: function(item) {
						tinymce_image_select(item, $img);
					}
				})
			} else {
				tinymce_image_select($img.data('media'), $img);
			}
		}
	});
});
</script>