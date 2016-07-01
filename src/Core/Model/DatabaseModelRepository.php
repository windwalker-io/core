<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Model;

use Windwalker\Core\Model\Traits\DatabaseModelRepositoryTrait;

/**
 * The ModelRepository class.
 *
 * @since  {DEPLOY_VERSION}
 */
class DatabaseDatabaseModelRepository extends ModelRepository implements DatabaseModelRepositoryInterface
{
	use DatabaseModelRepositoryTrait;
}
