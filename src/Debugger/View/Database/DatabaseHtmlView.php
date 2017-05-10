<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Debugger\View\Database;

use Windwalker\Data\Data;
use Windwalker\Debugger\Helper\TimelineHelper;
use Windwalker\Debugger\View\AbstractDebuggerHtmlView;

/**
 * The SystemHtmlView class.
 * 
 * @since  2.1.1
 */
class DatabaseHtmlView extends AbstractDebuggerHtmlView
{
	/**
	 * prepareData
	 *
	 * @param \Windwalker\Data\Data $data
	 *
	 * @return  void
	 */
	protected function prepareData($data)
	{
		$profiler = $data->item['profiler'];
		$data->collector = $collector = $data->item['collector'];

		// Information
		$data->options = new Data($collector['database.info']);

		// Find system process points
		$queries = $data->collector['database.queries'];

		$data->queryProcess = TimelineHelper::prepareQueryTimeline($queries);
	}

	/**
	 * Simple highlight for SQL queries.
	 *
	 * @param   string  $query  The query to highlight.
	 *
	 * @return  string  Highlighted query string.
	 */
	public function highlightQuery($query)
	{
		$newlineKeywords = '#\b(FROM|LEFT|INNER|OUTER|WHERE|SET|VALUES|ORDER|GROUP|HAVING|LIMIT|ON|AND|CASE)\b#i';

		$query = htmlspecialchars($query, ENT_QUOTES);

		$query = preg_replace($newlineKeywords, '<br />&#160;&#160;\\0', $query);

		$regex = [
			'/(=)/'
			=> '<strong class="text-error">$1</strong>',

			// All uppercase words have a special meaning.
			'/(?<!\w|>)([A-Z_]{2,})(?!\w)/x'
			=> '<span class="text-info">$1</span>'
		];

		$query = preg_replace(array_keys($regex), array_values($regex), $query);

		$query = str_replace('*', '<strong style="color: red;">*</strong>', $query);

		return $query;
	}
}
