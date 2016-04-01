<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Router\PackageRouter;
use Windwalker\Registry\Registry;

/**
 * @var Registry        $uri
 * @var AbstractPackage $package
 * @var PackageRouter   $router
 */
?><!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title><?php $this->block('page_title'); ?><?php $this->endblock(); ?></title>

	<link rel="shortcut icon" type="image/x-icon" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAAWdEVYdFNvZnR3YXJlAHBhaW50Lm5ldCA0LjA76PVpAAABg0lEQVRYR+3UOyhHUQDH8euVFIO3SB5lYKaUiWRQsjBjUIqUyEKyktfkUSxEWaSQxKIYvEuxySqyI6/v75wuf48kuQadb33qnH90zv2f87+ey+Vyud6VjzaMYBgdqEURcpCLbHynNDRgCOPoRQk+LQz6wzvsYBknuMfTO+Xwi0aiHb6UhWlsoROVqEArjjCLcLxJOz2FnjAS/dCG4lCIGhzjDNqs0sLrSDEzWyN2UWpmH4uANldvZiFpt3V2aJ5Ai+4jWR+QFrtBj5l5Xjq04QUzs5uewAxi9MEXxaPZDl/TP/bZoakMG3ZoasEDdP6p8I+nClp8HgP4cXrSJRxgGyvQQn66F5uIhb6ZOVwiClMYw6+kM5fQ8vCIJixiELqko+jCKnS2gaVFbqGfpr4F3REdhy6qLqXONNAOcY0L6PK1Q+d/hWIEml4+/u+/Wh/QHjTXiyXw9PLQYpNmZu+D5vqt6/YH3hrO4V/Mbuh9UGBmf1AmEuzQlIQMO3S5XK5/kec9A3l6TjrQtGhFAAAAAElFTkSuQmCC" />
	<meta name="generator" content="Windwalker Framework" />
	<?php $this->block('meta'); ?>
	<?php $this->endblock(); ?>

	<link href='http://fonts.googleapis.com/css?family=Roboto:400,500,300,700' rel='stylesheet' type='text/css'>
	<link rel="stylesheet" href="<?php echo $router->html('asset', array('type' => 'css')); ?>" />
	<style>
		@font-face {
			font-family: 'Glyphicons Halflings';
			src: url('<?php echo $router->html('asset', array('type' => 'fonts')); ?>');
		}
	</style>
	<?php $this->block('style'); ?>
	<?php $this->endblock(); ?>

	<?php $this->block('script'); ?>
	<?php $this->endblock(); ?>
</head>
<body>
<?php $this->block('navbar'); ?>
<div class="navbar navbar-default navbar-fixed-top">
	<div class="container">
		<div class="navbar-header">
			<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
			<a class="navbar-brand" href="<?php echo $router->html('dashboard'); ?>">
				Windwalker Debugger
			</a>
		</div>
		<div class="collapse navbar-collapse">
			<ul class="nav navbar-nav">
				<?php $this->block('nav'); ?>
				<li class="<?php echo $helper->view->isActiveRoute('system') ?>"><a href="<?php echo $router->html('system'); ?>">System</a></li>
				<li class="<?php echo $helper->view->isActiveRoute('request') ?>"><a href="<?php echo $router->html('request'); ?>">Request</a></li>
				<li class="<?php echo $helper->view->isActiveRoute('routing') ?>"><a href="<?php echo $router->html('routing'); ?>">Routing</a></li>
				<li class="<?php echo $helper->view->isActiveRoute('timeline') ?>"><a href="<?php echo $router->html('timeline'); ?>">Timeline</a></li>
				<li class="<?php echo $helper->view->isActiveRoute('events') ?>"><a href="<?php echo $router->html('events'); ?>">Events</a></li>

				<li class="<?php echo $helper->view->isActiveRoute('database') ?>"><a href="<?php echo $router->html('database'); ?>">Database</a></li>
				<li class="<?php echo $helper->view->isActiveRoute('exception') ?>"><a href="<?php echo $router->html('exception'); ?>">Exception</a></li>
				<?php $this->endblock(); ?>
			</ul>
			<ul class="nav navbar-nav navbar-right">
				<li>
					<a class="" href="<?php echo $router->html($app->get('route.matched'), array('refresh' => 1, 'id' => $item['id'])); ?>"
						data-toggle="tooltip" data-placement="top" title="Refresh to latest URL">
						<span class="glyphicon glyphicon-refresh"></span>
						Refresh
					</a>
				</li>
				<li>
					<a target="_blank" href="<?php echo $uri['base.path'] . $uri['script']; ?>">
						<span class="glyphicon glyphicon-globe"></span>
						Preview
					</a>
				</li>
			</ul>
		</div>
		<!--/.nav-collapse -->
	</div>
</div>
<?php $this->endblock(); ?>

<?php $this->block('message') ?>
<?php echo $this->load('windwalker.message.default'); ?>
<?php $this->endblock(); ?>

<div class="header-title jumbotron">
	<div class="container">
		<h1><?php $this->block('page_title'); ?><?php $this->endblock(); ?></h1>
		<p>
			<a class="btn btn-sm btn-info" href="<?php echo $router->html('dashboard'); ?>"
				data-toggle="tooltip" data-placement="top" title="Choose other URLs">
				<span class="glyphicon glyphicon-list"></span>
			</a>
			<a class="btn btn-sm btn-success" href="<?php echo $router->html($app->get('route.matched'), array('refresh' => 1, 'id' => $item['id'])); ?>"
				data-toggle="tooltip" data-placement="top" title="Refresh to latest URL">
				<span class="glyphicon glyphicon-refresh"></span>
			</a>
			/
			ID: <span class="text-muted"><?php echo $item->id; ?></span>
			/
			<a class="text-muted" href="<?php echo $item['collector']['system.uri.full'] ?>" target="_blank">
				<?php echo $item['collector']['system.uri.full'] ?>
				<small class="glyphicon glyphicon-new-window"></small>
			</a>
		</p>
	</div>
</div>

<div class="main-body container">
	<?php $this->block('content') ?>
	Content
	<?php $this->endblock(); ?>
</div>

<?php $this->block('copyright') ?>
<div id="copyright">
	<div class="container">
		<div class="row">
			<div class="col-lg-12">

				<hr />

				<footer>
					&copy; Windwalker <?php echo $datetime->format('Y'); ?>
				</footer>
			</div>
		</div>
	</div>
</div>
<?php $this->endblock(); ?>
</body>
</html>