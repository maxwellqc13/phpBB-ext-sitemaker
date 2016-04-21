<?php
/**
 *
 * @package sitemaker
 * @copyright (c) 2015 Daniel A. (blitze)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace blitze\sitemaker\tests\services\blocks;

use phpbb\request\request_interface;
use blitze\sitemaker\services\blocks\display;

require_once dirname(__FILE__) . '../../../../../../../includes/functions.php';
require_once dirname(__FILE__) . '/../fixtures/ext/foo/bar/foo_bar_controller.php';

class display_test extends \phpbb_database_test_case
{
	protected $template;

	/**
	 * Define the extension to be tested.
	 *
	 * @return string[]
	 */
	protected static function setup_extensions()
	{
		return array('blitze/sitemaker');
	}

	/**
	 * Load required fixtures.
	 *
	 * @return mixed
	 */
	public function getDataSet()
	{
		return $this->createXMLDataSet(dirname(__FILE__) . '/../fixtures/blocks.xml');
	}

	/**
	 * Create the blocks display service
	 *
	 * @param array $auth_map
	 * @param array $variable_map
	 * @param array $page_data
	 * @param mixed $config_text_data
	 * @param bool $show_admin_bar
	 * @param bool $show_blocks
	 * @return \blitze\sitemaker\services\blocks\display
	 */
	protected function get_service(array $auth_map, array $variable_map, array $page_data, $config_text_data, $show_admin_bar, $show_blocks)
	{
		global $db, $request, $phpbb_path_helper, $phpbb_dispatcher, $phpbb_root_path, $phpEx;

		$auth = $this->getMock('\phpbb\auth\auth');
		$auth->expects($this->any())
			->method('acl_get')
			->with($this->stringContains('_'), $this->anything())
			->will($this->returnValueMap($auth_map));

		$db = $this->new_dbal();

		$lang_loader = new \phpbb\language\language_file_loader($phpbb_root_path, $phpEx);
		$translator = new \phpbb\language\language($lang_loader);

		$user = new \phpbb\user($translator, '\phpbb\datetime');
		$user->data['user_style'] = 1;
		$user->page = $page_data;

		$config = new \phpbb\config\config(array(
			'default_style' => 1,
			'override_user_style' => false,
			'cookie_name' => 'test',
		));

		$config_text = new \phpbb\config\db_text($db, 'phpbb_config_text');

		$config_text->set('sm_layout_prefs', json_encode(array(
			1 => $config_text_data
		)));

		$request = $this->getMock('\phpbb\request\request_interface');
		$request->expects($this->any())
			->method('is_set')
			->will($this->returnCallback(function($var) use ($variable_map) {
				return (!empty($variable_map[0]) && $variable_map[0][0] === $var) ? true : false;
			}));
		$request->expects($this->any())
			->method('variable')
			->with($this->anything())
			->will($this->returnValueMap($variable_map));

		$this->db = $db = $this->new_dbal();
		$this->config_text = new \phpbb\config\db_text($this->db, 'phpbb_config_text');

		$tpl_data = array();
		$this->template = $this->getMockBuilder('\phpbb\template\template')
			->getMock();
		$this->template->expects($this->any())
			->method('assign_vars')
			->will($this->returnCallback(function($data) use (&$tpl_data) {
				$tpl_data = array_merge($tpl_data, $data);
			}));
		$this->template->expects($this->any())
			->method('assign_block_vars')
			->will($this->returnCallback(function($block, $data) use (&$tpl_data) {
				$tpl_data['blocks'][$block][] = $data;
			}));
		$this->template->expects($this->any())
			->method('assign_display')
			->will($this->returnCallback(function() use (&$tpl_data) {
				return $tpl_data;
			}));

		$blocks = $this->getMockBuilder('\blitze\sitemaker\services\blocks\blocks')
			->disableOriginalConstructor()
			->getMock();
		$blocks->expects($this->exactly((int) $show_blocks))
			->method('display');
		$blocks->expects($this->any())
			->method('get_route_info')
			->willReturn(array());

		$admin_bar = $this->getMockBuilder('\blitze\sitemaker\services\blocks\admin_bar')
			->disableOriginalConstructor()
			->getMock();
		$admin_bar->expects($this->exactly((int) $show_admin_bar))
			->method('show');

		$phpbb_container = new \phpbb_mock_container_builder();
		$phpbb_container->set('config_text', $config_text);
		$phpbb_container->set('blitze.sitemaker.blocks', $blocks);
		$phpbb_container->set('blitze.sitemaker.blocks.admin_bar', $admin_bar);
		$phpbb_container->set('foo.bar.controller', new \foo\bar\foo_bar_controller());

		$phpbb_dispatcher = new \phpbb_mock_event_dispatcher();

		$phpbb_path_helper =  new \phpbb\path_helper(
			new \phpbb\symfony_request(
				new \phpbb_mock_request()
			),
			new \phpbb\filesystem(),
			$request,
			$phpbb_root_path,
			$phpEx
		);

		return new display($auth, $config, $phpbb_container, $request, $this->template, $translator, $user);
	}

	/**
	 * Data set for test_show_admin_bar
	 *
	 * @return array
	 */
	public function show_blocks_test_data()
	{
		return array(
			array(
				array(
					array('a_sm_manage_blocks', 0, true),
				),
				array(),
				array(
					'page_dir' => 'adm',
					'page_name' => 'index.php',
					'query_string' => '',
				),
				'',
				false,
				false,
				array(),
			),
			array(
				array(
					array('a_sm_manage_blocks', 0, true),
				),
				array(),
				array(
					'page_dir' => '',
					'page_name' => 'ucp.php',
					'query_string' => 'i=177',
				),
				'',
				false,
				false,
				array(),
			),
			array(
				array(
					array('a_sm_manage_blocks', 0, false),
				),
				array(),
				array(
					'page_dir' => '',
					'page_name' => 'index.php',
					'query_string' => '',
				),
				array(
					'layout' => './../ext/blitze/sitemaker/styles/all/template/layouts/portal/'
				),
				false,
				true,
				array(
					'S_SITEMAKER' => true,
					'S_LAYOUT' => 'portal',
					'U_EDIT_MODE' => '',
				),
			),
			array(
				array(
					array('a_sm_manage_blocks', 0, true),
				),
				array(),
				array(
					'page_dir' => '',
					'page_name' => 'index.php',
					'query_string' => '',
				),
				array(
					'layout' => './../ext/blitze/sitemaker/styles/all/template/layouts/portal_alt/'
				),
				false,
				true,
				array(
					'S_SITEMAKER' => true,
					'S_LAYOUT' => 'portal_alt',
					'U_EDIT_MODE' => 'http://phpBB/?edit_mode=1',
				),
			),
			array(
				array(
					array('a_sm_manage_blocks', 0, true),
				),
				array(
					array('edit_mode', false, false, request_interface::REQUEST, 1),
					array('test_sm_edit_mode', false, false, request_interface::COOKIE, false),
				),
				array(
					'page_dir' => '',
					'page_name' => 'index.php',
					'query_string' => 'edit_mode=1',
				),
				array(
					'layout' => './../ext/blitze/sitemaker/styles/all/template/layouts/portal/'
				),
				true,
				true,
				array(
					'S_SITEMAKER' => true,
					'S_LAYOUT' => 'portal',
					'U_EDIT_MODE' => 'http://phpBB/?edit_mode=0',
				),
			),
			array(
				array(
					array('a_sm_manage_blocks', 0, true),
				),
				array(
					array('style', 0, false, request_interface::REQUEST, 2),
					array('test_sm_edit_mode', false, false, request_interface::COOKIE, true),
				),
				array(
					'page_dir' => '',
					'page_name' => 'index.php',
					'query_string' => '',
				),
				'',
				true,
				true,
				array(
					'S_SITEMAKER' => true,
					'S_LAYOUT' => 'default',
					'U_EDIT_MODE' => 'http://phpBB/?edit_mode=0',
				),
			),
		);
	}

	/**
	 * Test the show method
	 *
	 * @dataProvider show_blocks_test_data
	 * @param array $auth_map
	 * @param array $variable_map
	 * @param array $page_data
	 * @param mixed $config_text
	 * @param bool $show_admin_bar
	 * @param bool $show_blocks
	 * @param array $expected
	 */
	public function test_show_blocks(array $auth_map, array $variable_map, array $page_data, $config_text_data, $show_admin_bar, $show_blocks, array $expected)
	{
		$display = $this->get_service($auth_map, $variable_map, $page_data, $config_text_data, $show_admin_bar, $show_blocks);

		$display->show();

		$result = $this->template->assign_display('page');

		$this->assertSame($expected, $result);
	}
}
