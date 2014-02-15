<?php
/**
 *
 * @package primetime
 * @copyright (c) 2013 Daniel A. (blitze)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace primetime\primetime\blocks;

/**
 * @ignore
 */
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
 * Login Block
 */
class members extends \primetime\primetime\core\blocks\driver\block
{
	/** @var \phpbb\user */
	private $user;

	private	$mode_options = array('visits' => 'LAST_VISITED', 'bots' => 'RECENT_BOTS', 'recent' => 'RECENT_MEMBERS', 'tenured' => 'MOST_TENURED', 'posts' => 'TOP_POSTERS');
	private	$range_options = array('' => 'ALL', 'today' => 'TODAY', 'week' => 'THIS_WEEK', 'month' => 'THIS_MONTH', 'year' => 'THIS_YEAR');

	/**
	 * Constructor
	 *
	 * @param \phpbb\user							$user	User object
	 * @param \primetime\primetime\core\members		$user	Members object
	 */
	public function __construct(\phpbb\user $user, \primetime\primetime\core\members $members)
	{
		$this->user = $user;
		$this->members = $members;
	}

	public function get_config($settings)
	{
		$mode_options = preg_replace('#^([A-Z_]+)$#e', "(!empty(\$this->user->lang['\\1'])) ? \$this->user->lang['\\1'] : '\\1'", $this->mode_options);
		$range_options = preg_replace('#^([A-Z_]+)$#e', "(!empty(\$this->user->lang['\\1'])) ? \$this->user->lang['\\1'] : '\\1'", $this->range_options);

		$query_type = (!empty($settings['query_type'])) ? $settings['query_type'] : 'recent';
		$date_range = (!empty($settings['date_range'])) ? $settings['date_range'] : 'month';

		return array(
			'legend1'		=> $this->user->lang['SETTINGS'],
            'query_type'	=> array('lang' => 'QUERY_TYPE', 'validate' => 'string', 'type' => 'select', 'function' => 'build_select', 'params' => array($mode_options, $query_type), 'default' => 'recent', 'explain' => false),
            'date_range'	=> array('lang' => 'DATE_RANGE', 'validate' => 'string', 'type' => 'select', 'function' => 'build_select', 'params' => array($range_options, $date_range), 'default' => '', 'explain' => false),
			'max_members'	=> array('lang' => 'MAX_MEMBERS', 'validate' => 'int:0:20', 'type' => 'number:0:20', 'maxlength' => 2, 'explain' => false, 'default' => 5),
		);
	}

	public function display($bdata, $edit_mode = false)
	{
		$bdata['settings']['range'] = ($bdata['settings']['query_type'] != 'tenured') ? $bdata['settings']['date_range'] : '';
		$title = $this->mode_options[$bdata['settings']['query_type']] . '_' . $this->range_options[$bdata['settings']['date_range']];

		return array(
			'title'		=> $title,
			'content'	=> $this->members->get_list($bdata['settings']),
		);
	}
}