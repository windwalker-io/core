<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

$this->extend('_global.html');

/**
 * @var  $message  \Windwalker\Core\Mailer\MailMessage
 */
?>

<?php $this->block('page_title') ?>Mail Tester: <?php echo $class; ?><?php $this->endblock(); ?>

<?php $this->block('banner') ?>
<div class="header-title jumbotron">
	<div class="container">
		<h1>Mail Tester</h1>
		<p class="lead"><code><?php echo $class; ?></code></p>
	</div>
</div>
<?php $this->endblock() ?>

<?php $this->block('content') ?>
<div id="mail-tester-body" class="container">
	<div class="row">
		<div id="mail-tester-wrapper" class="col-md-8 col-md-offset-2" style="margin-top: 30px; margin-bottom: 30px; padding: 0">
			<?php if (isset($message)): ?>
				<iframe id="mail-tester-frame" frameborder="0" style="width: 100%; height: 550px;"></iframe>
			<?php else: ?>
				<h3 class="text-center">Mail Provider: <code><?php echo $class; ?></code> Not Found</h3>
			<?php endif; ?>
		</div>
	</div>
</div>

<script id="mail-tester-template" type="text/template">
	<?php if (isset($message)): ?>
	<?php echo $message->getBody(); ?>
	<?php endif; ?>
</script>

<script>
	var iframe = document.getElementById('mail-tester-frame');
	var html = document.getElementById('mail-tester-template');
	iframe.src = 'data:text/html;charset=utf-8,' + encodeURI(html.innerHTML);
</script>
<?php $this->endblock() ?>


