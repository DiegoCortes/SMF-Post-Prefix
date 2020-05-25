<?php

/**
 * @package SMF Post Prefix
 * @version 3.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2020, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

namespace PostPrefix;

if (!defined('SMF'))
	die('No direct access...');

class Manage
{
	private static $cats_columns = ['c.id_cat', 'c.name AS cat_name', 'c.cat_order'];
	private static $boards_columns = ['b.id_board', 'b.board_order', 'b.id_cat', 'b.name', 'b.child_level'];
	private static $groups_columns = ['group_name', 'id_group', 'min_posts', 'online_color'];
	private static $fields_data = [];
	private static $fields_type = [];

	public static function prefixes()
	{
		global $context, $sourcedir, $modSettings, $scripturl, $txt;

		require_once($sourcedir . '/Subs-List.php');
		$context['sub_template'] = 'show_list';
		$context['default_list'] = 'prefixlist';
		$context['page_title'] = $txt['PostPrefix_main'] . ' - '. $txt['PostPrefix_tab_prefixes'];
		$context[$context['admin_menu_name']]['tab_data']['title'] = $context['page_title'];

		// The entire list
		$listOptions = [
			'id' => 'prefixlist',
			'title' => $txt['PostPrefix_tab_prefixes'],
			'items_per_page' => 25,
			'base_href' => '?action=admin;area=postprefix;sa=prefixes',
			'default_sort_col' => 'modify',
			'get_items' => [
				'function' => __NAMESPACE__ . '\Helper::Get',
				'params' => ['postprefixes AS pp', Helper::$columns],
			],
			'get_count' => [
				'function' => __NAMESPACE__ . '\Helper::Count',
				'params' => ['postprefixes AS pp', Helper::$columns]
			],
			'no_items_label' => $txt['PostPrefix_no_prefixes'],
			'no_items_align' => 'center',
			'columns' => [
				'status' => [
					'header' => [
						'value' => $txt['PostPrefix_prefix_status'],
						'class' => 'centertext',
					],
					'data' => [
						'function' => function($row)
						{
							global $scripturl;

							return '<a href="'.$scripturl.'?action=admin;area=postprefix;sa=status;id='. $row['id'].'"><span class="main_icons warning_' . ($row['status'] == 1 ? 'watch' : 'mute') . '"></span></a>';
						},
						'style' => 'width: 2%',
						'class' => 'centertext',
					],
					'sort' => [
						'default' => 'status DESC',
						'reverse' => 'status',
					]
				],
				'item_name' => [
					'header' => [
						'value' => $txt['PostPrefix_prefix_name'],
					],
					'data' => [
						'function' => function($row)
						{
							 return PostPrefix::format($row);
						},
						'style' => 'width: 20%',
					],
					'sort' =>  [
						'default' => 'name DESC',
						'reverse' => 'name',
					],
				],
				'boards' => [
					'header' => [
						'value' => $txt['PostPrefix_prefix_boards'],
						'class' => 'centertext',
					],
					'data' => [
						'sprintf' => [
							'format' => '<a href="'. $scripturl. '?action=admin;area=postprefix;sa=boards;id=%1$d" onclick="return reqOverlayDiv(this.href, \'%2$s\', \'/icons/modify_inline.png\');">'. $txt['PostPrefix_select_visible_boards']. '</a>',
							'params' => [
								'id' => false,
								'name' => true,
							],
						],
						'class' => 'centertext',
						'style' => 'width: 4%',
					],
				],
				'groups' => [
					'header' => [
						'value' => $txt['PostPrefix_prefix_groups'],
						'class' => 'centertext',
					],
					'data' => [
						'sprintf' => [
							'format' => '<a href="'. $scripturl. '?action=admin;area=postprefix;sa=groups;id=%1$d" onclick="return reqOverlayDiv(this.href, \'%2$s\', \'icons/members.png\');">'. $txt['PostPrefix_select_visible_groups']. '</a>',
							'params' => [
								'id' => false,
								'name' => true,
							],
						],
						'class' => 'centertext',
						'style' => 'width: 4%',
					],
				],
				'modify' => [
					'header' => [
						'value' => $txt['PostPrefix_prefix_modify'],
						'class' => 'centertext',
					],
					'data' => [
						'sprintf' => [
							'format' => '<a href="'. $scripturl. '?action=admin;area=postprefix;sa=edit;id=%1$d">'. $txt['PostPrefix_prefix_modify']. '</a>',
							'params' => [
								'id' => false,
							],
						],
						'style' => 'width: 5%',
						'class' => 'centertext',
					],
					'sort' => [
						'default' => 'id DESC',
						'reverse' => 'id',
					]
				],
				'delete' => [
					'header' => [
						'value' => $txt['delete']. ' <input type="checkbox" onclick="invertAll(this, this.form, \'delete[]\');" class="input_check" />',
						'class' => 'centertext',
					],
					'data' => [
						'sprintf' => [
							'format' => '<input type="checkbox" name="delete[]" value="%1$d" class="check" />',
							'params' => [
								'id' => false,
							],
						],
						'class' => 'centertext',
						'style' => 'width: 3%',
					],
				],
			],
			'form' => [
				'href' => '?action=admin;area=postprefix;sa=delete',
				'hidden_fields' => [
					$context['session_var'] => $context['session_id'],
				],
				'include_sort' => true,
				'include_start' => true,
			],
			'additional_rows' => [
				'delete' => [
					'position' => 'below_table_data',
					'value' => '<input type="submit" size="18" value="'.$txt['delete']. '" class="button" onclick="return confirm(\''.$txt['PostPrefix_prefix_delete_sure'].'\');" />',
				],
				'updated' => [
					'position' => 'top_of_list',
					'value' => (!isset($_REQUEST['deleted']) ? (!isset($_REQUEST['added']) ? (!isset($_REQUEST['updated']) ? '' : '<div class="infobox">'. $txt['PostPrefix_prefix_updated']. '</div>') : '<div class="infobox">'. $txt['PostPrefix_prefix_added']. '</div>') : '<div class="infobox">'. $txt['PostPrefix_prefix_deleted']. '</div>'),
				],
			],
		];
		// Let's finishem
		createList($listOptions);
	}

	public static function set_prefix()
	{
		global $txt, $context;

		// Essential bits
		$context['sub_template'] = 'postprefix';
		$context[$context['admin_menu_name']]['current_subsection'] = 'add';
		$context[$context['admin_menu_name']]['tab_data'] = [
			'title' => $txt['PostPrefix_main'] . ' - '. $txt['PostPrefix_tab_prefixes_add'],
			'description' => $txt['PostPrefix_tab_prefixes_add_desc'],
		];
		$context['prefix']['boards'] = [];
		$context['prefix']['groups'] = [];

		// Edit, or Add?
		if ($_REQUEST['sa'] == 'edit')
		{
			// Page information
			$where_query = 'WHERE pp.id = "'. (int) (isset($_REQUEST['id']) ? $_REQUEST['id'] : 0) . '"';
			$context['prefix'] = Helper::Get('', '', '', 'postprefixes AS pp', Helper::$columns, $where_query, true);
			$context[$context['admin_menu_name']]['current_subsection'] = 'prefixes';
			$context[$context['admin_menu_name']]['tab_data'] = [
				'title' => $txt['PostPrefix_main'] . ' - '. $txt['PostPrefix_tab_prefixes_edit'],
				'description' => $txt['PostPrefix_tab_prefixes_edit_desc'],
			];
			$context['prefix']['boards'] = explode(',', $context['prefix']['boards']);
			$context['prefix']['groups'] = explode(',', $context['prefix']['groups']);
			
			addInlineJavascript('var postprefix_color = \''.$context['prefix']['color']. '\';', true);

			// We found a page
			if (empty($context['prefix']))
				fatal_error($txt['PostPrefix_error_unable_tofind'], false);
		}

		// Title
		$context['page_title'] = $txt['PostPrefix_main'] . ' - '. $context[$context['admin_menu_name']]['tab_data']['title'];

		// Colorpicker
		loadCSSFile('colpick.min.css', ['default_theme' => true]);
		loadJavascriptFile('colpick.min.js', ['default_theme' => true]);
		addInlineJavascript('
			$(document).ready(function (){
				$(\'#color\').colpick({
					layout:\'hex\',
					submit:0,
					colorScheme:\'light\',
					'. (!empty($context['prefix']['color']) ? 'color:\''. $context['prefix']['color']. '\',' : 'color:\'000000\',').'
					onChange:function(hsb,hex,rgb,el,bySetColor) {
						$(el).css(\'border-color\',\'#\'+hex);
						// Fill the text box just if the color was set using the picker, and not the colpickSetColor function.
						if(!bySetColor) $(el).val(hex);
					}
				}).keyup(function(){
					$(this).colpickSetColor(this.value);
				});
			});',
			true
		);

		// Groups
		loadLanguage('ManageBoards');
		$context['forum_groups'] = [
			-1 => [
				'id_group' => '-1',
				'group_name' => $txt['parent_guests_only'],
			],
			0 => [
				'id_group' => '0',
				'group_name' => $txt['parent_members_only'],
			]
		];
		$context['forum_groups'] += Helper::Get(0, 10000, 'min_posts, group_name', 'membergroups', self::$groups_columns, 'WHERE id_group != 3');

		// Boards
		$context['forum_categories'] = Helper::Nested('b.board_order', 'boards AS b', self::$cats_columns, self::$boards_columns, 'boards', '', 'LEFT JOIN {db_prefix}categories AS c ON (c.id_cat = b.id_cat)');
		// Now, let's sort the list of categories into the boards for templates that like that.
		foreach ($context['forum_categories'] as $category)
			// Include a list of boards per category for easy toggling.
			$context['forum_categories'][$category['id_cat']]['child_ids'] = array_keys($category['boards']);
	}

	public static function save()
	{
		global $smcFunc, $txt;

		// Data
		self::$fields_data = [
			'id' => (int) isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? $_REQUEST['id'] : 0,
			'name' => (string) isset($_REQUEST['name']) ? $smcFunc['htmlspecialchars']($_REQUEST['name'], ENT_QUOTES) : '',
			'status' => (int) isset($_REQUEST['status']) ? 1 : 0,
			'color' => (string) isset($_REQUEST['color']) ? (((strpos($_REQUEST['color'], '#') === false && !empty($_REQUEST['color'])) ? '#' : '') . $smcFunc['htmlspecialchars']($_REQUEST['color'], ENT_QUOTES)) : '',
			'bgcolor' => (int) isset($_REQUEST['bgcolor']) ? 1 : 0,
			'invert_color' => (int) isset($_REQUEST['invert']) ? 1 : 0,
			'groups' => (string) isset($_REQUEST['groups']) && !empty($_REQUEST['groups']) && is_array($_REQUEST['groups']) ? implode(',', $_REQUEST['groups']) : '',
			'boards' => (string) isset($_REQUEST['boardset']) && !empty($_REQUEST['boardset']) && is_array($_REQUEST['boardset']) ? implode(',', $_REQUEST['boardset']) : '',
			'icon_url' => (string) isset($_REQUEST['icon_url']) && isset($_REQUEST['icon']) ? $smcFunc['htmlspecialchars']($_REQUEST['icon_url'], ENT_QUOTES) : '',
		];

		// Validate info
		self::Validate(self::$fields_data);
		checkSession();
		$status = 'updated';

		if (empty(self::$fields_data['id']))
		{
			// Type
			foreach(self::$fields_data as $column => $type)
				self::$fields_type[$column] = str_replace('integer', 'int', gettype($type));

			// Insert
			Helper::Insert('postprefixes', self::$fields_data, self::$fields_type);
			$status = 'added';
		}
		else
		{
			self::$fields_type = '';
			
			// Remove those that don't require updating
			unset(self::$fields_data['page_type']);

			// Type
			foreach(self::$fields_data as $column => $type)
				self::$fields_type .= $column . ' = {'.str_replace('integer', 'int', gettype($type)).':'.$column.'}, ';

			// Update
			Helper::Update('postprefixes', self::$fields_data, self::$fields_type, 'WHERE id = ' . self::$fields_data['id']);
		}

		redirectexit('action=admin;area=postprefix;sa=prefixes;'.$status);
	}

	public static function validate($data)
	{
		global $txt;

		// Empty name
		if (empty($data['name']))
			fatal_error($txt['PostPrefix_error_noprefix'], false);

		// Doesn't exist
		if (!empty($data['id']) && empty(Helper::Find('postprefixes AS pp', 'pp.id', $data['id'])))
			fatal_error($txt['PostPrefix_error_unable_tofind'], false);
	}

	public static function delete()
	{
		global $context, $smcFunc, $txt;

		// Set all the page stuff
		$context['page_title'] = $txt['PostPrefix_main'] . ' - '. $txt['PostPrefix_tab_prefixes'];
		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => $context['page_title'],
			'description' => $txt['PostPrefix_tab_prefixes_desc'],
		);

		// If nothing was chosen to delete (shouldn't happen, but meh)
		if (!isset($_REQUEST['delete']))
			fatal_error($txt['PostPrefix_error_unable_tofind'], false);
				
		// Make sure all IDs are numeric
		foreach ($_REQUEST['delete'] as $key => $value)
			$_REQUEST['delete'][$key] = (int) $value;

		// Delete all the items
		Helper::Delete('postprefixes', 'id', $_REQUEST['delete']);
			
		// Send the user to the items list with a message
		redirectexit('action=admin;area=postprefix;sa=prefixes;deleted;');
	}

	public static function status()
	{
		global $smcFunc, $context, $modSettings, $txt;

		// Set all the page stuff
		$context['page_title'] = $txt['PostPrefix_main'] . ' - '. $txt['PostPrefix_tab_prefixes_edit'];
		$context[$context['admin_menu_name']]['tab_data'] = [
			'title' => $context['page_title'],
			'description' => $txt['PostPrefix_tab_prefixes_edit_desc'],
		];

		$id = (int) $_REQUEST['id'];
		$status = (int) (!isset($_REQUEST['status']) || empty($_REQUEST['status']) ? 0 : $_REQUEST['status']);

		// Verifiy
		if (!isset($id) || empty($id) || empty(Helper::Find('postprefixes AS pp', 'pp.id', $id)))
			fatal_error($txt['PostPrefix_error_unable_tofind'], false);

		// Get the prefix info
		$context['prefix'] = Helper::Get('', '', '', 'postprefixes AS pp', Helper::$columns, 'WHERE pp.id = "'. (int) (isset($_REQUEST['id']) ? $_REQUEST['id'] : 0) . '"', true);

		// Update the item information
		Helper::Update('postprefixes', ['id' => $id, 'status' => (!empty($context['prefix']['status']) ? 0 : 1)], 'id = {int:id}, status = {int:status},', 'WHERE id = ' . $id);
		
		// Send him to the items list
		redirectexit('action=admin;area=postprefix;sa=prefixes');
	}

	public static function show_define($type = 'boards')
	{
		global $smcFunc, $context, $txt;

		// Load the info
		$context[$context['admin_menu_name']]['current_subsection'] = 'prefixes';
		$context[$context['admin_menu_name']]['tab_data'] = [
			'title' => $txt['PostPrefix_main'] . ' - '. $txt['PostPrefix_prefix_' . $type],
			'description' => $txt['PostPrefix_prefix_' . $type. '_desc'],
		];
		$context['page_title'] = $context[$context['admin_menu_name']]['tab_data']['title'];
		$context['template_layers'][] = 'postprefix_show';
		$context['from_ajax'] = true;
		$context['sub_template'] = 'postprefix_show';
		$context['prefix']['show'] = $type;

		// Help language
		loadLanguage('Help');

		// Check if there's an id
		if (!isset($_REQUEST['id']) || empty($_REQUEST['id']) || empty(Helper::Find('postprefixes AS pp', Helper::$columns[0], $_REQUEST['id'])))
			fatal_error($txt['PostPrefix_error_unable_tofind'], false);

		// Obtain the prefix details
		$context['prefix']['details'] = Helper::Get('', '', '', 'postprefixes AS pp', Helper::$columns, 'WHERE pp.id = ' . $_REQUEST['id'], true);

		// Update title
		$context[$context['admin_menu_name']]['tab_data']['title'] .= ' - ' . $context['prefix']['details']['name'];
		$context['page_title'] = $context[$context['admin_menu_name']]['tab_data']['title'];
	}

	public static function groups()
	{
		global $context, $txt;

		// Groups type
		self::show_define('groups');

		// Get groups
		$context['prefix']['get_type'] = Helper::Get(0, 10000, 'min_posts, group_name', 'membergroups', self::$groups_columns, 'WHERE id_group != 3 AND FIND_IN_SET(id_group, \''. $context['prefix']['details']['groups'] .'\')');

		// Load extra language
		loadLanguage('ManageBoards');

		// :(
		$groups = [];

		// Guests
		if (in_array(-1, explode(',', $context['prefix']['details']['groups'])))
			$groups[-2] = [
				'id_group' => '-1',
				'cat_name' => $txt['parent_guests_only'],
			];
		// Regular Members
		if (in_array(0, explode(',', $context['prefix']['details']['groups'])))
			$groups[-1] = [
				'id_group' => '0',
				'cat_name' => $txt['parent_members_only'],
			];

		// Well this isn't great but meh
		foreach($context['prefix']['get_type'] as $group)
		{
			$groups[$group['id_group']] = $group;
			$groups[$group['id_group']]['cat_name'] = $group['group_name'];
		}

		// Re-assigns
		$context['prefix']['get_type'] = $groups;
	}

	public static function boards()
	{
		global $context, $smcFunc;

		// Boards type
		self::show_define();

		// Get groups
		$context['prefix']['get_type'] = Helper::Nested('b.board_order', 'boards AS b', self::$cats_columns, self::$boards_columns, 'boards', 'WHERE FIND_IN_SET(b.id_board, \''. $context['prefix']['details']['boards'] .'\')', 'LEFT JOIN {db_prefix}categories AS c ON (c.id_cat = b.id_cat)');
		// Now, let's sort the list of categories into the boards for templates that like that.
		foreach ($context['prefix']['get_type'] as $category)
			// Include a list of boards per category for easy toggling.
			$context['prefix']['get_type'][$category['id_cat']]['child_ids'] = array_keys($category['boards']);
	}
}