<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Router\PackageRouter;
use Windwalker\Structure\Structure;
use Windwalker\Uri\UriData;

/**
 * @var Structure       $uri
 * @var AbstractPackage $package
 * @var PackageRouter   $router
 * @var UriData         $uri
 */
?><!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0">
    <title><?php $this->block('page_title'); ?><?php $this->endblock(); ?></title>

    <link rel="shortcut icon" type="image/x-icon"
          href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAAWdEVYdFNvZnR3YXJlAHBhaW50Lm5ldCA0LjA76PVpAAABg0lEQVRYR+3UOyhHUQDH8euVFIO3SB5lYKaUiWRQsjBjUIqUyEKyktfkUSxEWaSQxKIYvEuxySqyI6/v75wuf48kuQadb33qnH90zv2f87+ey+Vyud6VjzaMYBgdqEURcpCLbHynNDRgCOPoRQk+LQz6wzvsYBknuMfTO+Xwi0aiHb6UhWlsoROVqEArjjCLcLxJOz2FnjAS/dCG4lCIGhzjDNqs0sLrSDEzWyN2UWpmH4uANldvZiFpt3V2aJ5Ai+4jWR+QFrtBj5l5Xjq04QUzs5uewAxi9MEXxaPZDl/TP/bZoakMG3ZoasEDdP6p8I+nClp8HgP4cXrSJRxgGyvQQn66F5uIhb6ZOVwiClMYw6+kM5fQ8vCIJixiELqko+jCKnS2gaVFbqGfpr4F3REdhy6qLqXONNAOcY0L6PK1Q+d/hWIEml4+/u+/Wh/QHjTXiyXw9PLQYpNmZu+D5vqt6/YH3hrO4V/Mbuh9UGBmf1AmEuzQlIQMO3S5XK5/kec9A3l6TjrQtGhFAAAAAElFTkSuQmCC"/>
    <meta name="generator" content="Windwalker Framework"/>
    <?php $this->block('meta'); ?>
    <?php $this->endblock(); ?>

    <link href='http://fonts.googleapis.com/css?family=Roboto:400,500,300,700' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" href="<?php echo $router->route('asset', ['type' => 'css']); ?>"/>
    <style>
        @font-face {
            font-family: 'Font Awesome 5 Free';
            font-style: normal;
            src: url('<?php echo $router->route('asset', ['type' => 'fonts']); ?>');
        }
    </style>
    <?php $this->block('style'); ?>
    <?php $this->endblock(); ?>

    <?php $this->block('script'); ?>
    <?php $this->endblock(); ?>

    <style>
        .navbar-toggler:focus + .collapse {
            display: block !important;

        }
    </style>
</head>
<body>
<?php $this->block('navbar'); ?>
<div class="navbar fixed-top navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="<?php echo $router->route('dashboard'); ?>">
            W Debugger
        </a>
        <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#main-nav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div id="main-nav" class="collapse navbar-collapse">
            <ul class="nav navbar-nav">
                <?php $this->block('nav'); ?>
                <li class="nav-item"><a class="nav-link <?php echo $helper->view->isActiveRoute('system') ?>"
                        href="<?php echo $router->route('system', $item->id); ?>">System</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $helper->view->isActiveRoute('request') ?>"
                        href="<?php echo $router->route('request', $item->id); ?>">Request</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $helper->view->isActiveRoute('routing') ?>"
                        href="<?php echo $router->route('routing', $item->id); ?>">Routing</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $helper->view->isActiveRoute('timeline') ?>"
                        href="<?php echo $router->route('timeline', $item->id); ?>">Timeline</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $helper->view->isActiveRoute('events') ?>"
                        href="<?php echo $router->route('events', $item->id); ?>">Events</a></li>

                <li class="nav-item"><a class="nav-link <?php echo $helper->view->isActiveRoute('database') ?>"
                        href="<?php echo $router->route('database', $item->id); ?>">Database</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $helper->view->isActiveRoute('exception') ?>"
                        href="<?php echo $router->route('exception', $item->id); ?>">Exception</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $helper->view->isActiveRoute('mail') ?>"
                        href="<?php echo $router->route('mail'); ?>">Mail</a></li>
                <?php $this->endblock(); ?>
            </ul>
            <ul class="nav navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="btn btn-outline-success mr-2"
                        href="<?php echo $router->route(
                            $app->get('route.matched'),
                            ['refresh' => 1, 'id' => $item['id']]
                        ); ?>"
                        data-toggle="tooltip" data-placement="top" title="Refresh to latest URL">
                        <span class="fa fa-sync"></span>
                        Refresh
                    </a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-outline-primary" target="_blank" href="<?php echo $uri->path . '/' . $uri->script; ?>">
                        <span class="fa fa-globe"></span>
                        Site
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

<?php $this->block('banner') ?>
<div class="header-title jumbotron">
    <div class="container">
        <h1><?php $this->block('page_title'); ?><?php $this->endblock(); ?></h1>
        <p>
            <a class="btn btn-sm btn-info" href="<?php echo $router->route('dashboard'); ?>"
               data-toggle="tooltip" data-placement="top" title="Choose other URLs">
                <span class="fa fa-list"></span>
            </a>
            <a class="btn btn-sm btn-success"
               href="<?php echo $router->route($app->get('route.matched'), ['refresh' => 1, 'id' => $item['id']]); ?>"
               data-toggle="tooltip" data-placement="top" title="Refresh to latest URL">
                <span class="fa fa-sync"></span>
            </a>
            /
            ID: <span class="text-muted"><?php echo $item->id; ?></span>
            /
            <a class="text-muted" href="<?php echo $item['collector']['system.uri.full'] ?? '#' ?>" style="word-break: break-all" target="_blank">
                <?php echo $item['collector']['system.uri.full'] ?? '' ?>
                <small class="fa fa-external-link-alt"></small>
            </a>
        </p>
    </div>
</div>
<?php $this->endblock() ?>

<?php $this->block('body') ?>
<div class="main-body container">
    <?php $this->block('content') ?>
    Content
    <?php $this->endblock(); ?>
</div>
<?php $this->endblock() ?>

<?php $this->block('copyright') ?>
<div id="copyright">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">

                <hr/>

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
