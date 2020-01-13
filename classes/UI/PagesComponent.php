<?php
namespace Claromentis\ThankYou\UI;

use Claromentis\Core\Acl\PermOClass;
use Claromentis\Core\Application;
use Claromentis\Core\Component\ComponentInterface;
use Claromentis\Core\Component\MutatableOptionsInterface;
use Claromentis\Core\Component\OptionsInterface;
use Claromentis\Core\Component\TemplaterTrait;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\ThankYou\ThankYous;
use Claromentis\ThankYou\Configuration;
use ClaText;

/**
 * 'Thank you' component for Pages application. Shows list of latest "thanks" and optionally
 * a button to allow adding a new "thank you"
 */
//TODO fix redirecting away from Page when saying Thank You.
class PagesComponent implements ComponentInterface, MutatableOptionsInterface
{
	use TemplaterTrait;

	/**
	 * @var Configuration\Api
	 */
	private $config_api;

	/**
	 * @var Lmsg
	 */
	private $lmsg;

	/**
	 * PagesComponent constructor.
	 *
	 * @param Lmsg              $lmsg
	 * @param Configuration\Api $config_api
	 */
	public function __construct(Lmsg $lmsg, Configuration\Api $config_api)
	{
		$this->config_api = $config_api;
		$this->lmsg       = $lmsg;
	}

	/**
	 * Returns information about supported options for this component as array
	 *
	 * array(
	 *   'option_name' => ['type' => ...,
	 *                     'default' => ...,
	 *                     'title' => ...,
	 *                    ],
	 *   'other_option' => ...
	 * )
	 *
	 * @return array
	 */
	public function GetOptions()
	{
		return [
			'title'          => ['type' => 'string', 'title' => ($this->lmsg)('thankyou.component.options.custom_title'), 'default' => '', 'placeholder' => ($this->lmsg)('thankyou.component_heading')],
			'show_header'    => ['type' => 'bool', 'title' => ($this->lmsg)('thankyou.component.options.show_header'), 'default' => true, 'mutate_on_change' => true],
			'allow_new'      => ['type' => 'bool', 'default' => true, 'title' => ($this->lmsg)('thankyou.component.options.show_button')],
			'profile_images' => ['type' => 'bool', 'default' => false, 'title' => ($this->lmsg)('thankyou.component.options.profile_images')],
			'comments'       => ['type' => 'bool', 'default' => false, 'title' => ($this->lmsg)('common.show_comments')],
			'user_id'        => ['type' => 'int', 'title' => ($this->lmsg)('thankyou.component.options.user_id'), 'default' => null, 'input' => 'user_picker', 'width' => 'medium'],
			'group_ids'      => ['type' => 'array_int', 'title' => ($this->lmsg)('thankyou.common.filter_by_groups'), 'default' => [], 'input' => 'group_picker', 'width' => 'medium'],
			'limit'          => ['type' => 'int', 'title' => ($this->lmsg)('thankyou.component.options.num_items'), 'default' => 10, 'min' => 1, 'max' => 50]
		];
	}

	/**
	 * Render this component with the specified options
	 *
	 * @param string           $id_string
	 * @param OptionsInterface $options
	 * @param Application      $app
	 *
	 * @return string
	 */
	public function ShowBody($id_string, OptionsInterface $options, Application $app)
	{
		/**
		 * @var ThankYous\Api $api
		 */
		$api = $app[ThankYous\Api::class];

		/**
		 * @var SecurityContext $context
		 */
		$context          = $app[SecurityContext::class];
		$viewer_logged_in = !($context->GetUserId() === 0);

		$thank_user_id = $options->Get('user_id');
		$group_ids     = $options->Get('group_ids');

		$args = [];

		$args['ty_list.limit'] = $options->Get('limit');
		if (!is_int($args['ty_list.limit']))
		{
			$args['ty_list.limit'] = 20;
		}

		$args['ty_list.create']         = (bool) $options->Get('allow_new') && !(bool) $options->Get('show_header');
		$args['ty_list.thanked_images'] = (bool) $options->Get('profile_images');
		$args['ty_list.comments']       = (bool) $options->Get('comments') && $viewer_logged_in;

		$thanked_owner_classes = [];
		if (isset($thank_user_id) && $thank_user_id > 0)
		{
			$thanked_owner_classes[] = ['id' => (int) $thank_user_id, 'oclass' => PermOClass::INDIVIDUAL];
		}

		if (is_array($group_ids))
		{
			foreach ($group_ids as $group_id)
			{
				if ($group_id > 0)
				{
					$thanked_owner_classes[] = ['id' => (int) $group_id, 'oclass' => PermOClass::GROUP];
				}
			}
		}

		$user_ids = $api->GetOwnersUserIds($thanked_owner_classes);

		if (count($user_ids) > 0)
		{
			$args['ty_list.user_ids'] = $user_ids;
		}

		return $this->CallTemplater('thankyou/UI/pages_component.html', $args);
	}

	/**
	 * Render component header with the specified options.
	 * If null or empty string is returned, header is not displayed.
	 *
	 * @param string           $id_string
	 * @param OptionsInterface $options
	 * @param Application      $app
	 *
	 * @return string
	 */
	public function ShowHeader($id_string, OptionsInterface $options, Application $app)
	{
		if (!$options->Get('show_header'))
		{
			return '';
		}

		$args = [];

		if (!$options->Get('allow_new'))
		{
			$args = ['create_container.visible' => 0];
		}

		if ($options->Get('title') !== '')
		{
			$args['custom_title.body']     = cla_htmlsafe(ClaText::ProcessAvailableLocalisation((string) $options->Get('title')));
			$args['custom_title.visible']  = 1;
			$args['default_title.visible'] = 0;
		}

		$template = 'thankyou/UI/pages_component_header.html';

		return $this->CallTemplater($template, $args);
	}

	/**
	 * Define any minimum or maximum size constraints that this component has.
	 * Widths are measured in 12ths of the page as with Bootstrap.
	 * Heights are measured in multiples of the grid row height (around 47 pixels currently?)
	 *
	 * @return array should contain any combination of min_width, max_width, min_height and max_height.
	 */
	public function GetSizeConstraints()
	{
		return [
			'min_height' => 4,
		];
	}

	/**
	 * Returns CSS class name to be set on component tile when it's displayed.
	 * This class then can be used to change the display style.
	 *
	 * Recommended class name is 'tile-' followed by full component code.
	 *
	 * It also can be empty.
	 *
	 * @return string
	 */
	public function GetCssClass()
	{
		return 'tile-thank-you';
	}

	/**
	 * Returns associative array with description of this component to be displayed for users in the
	 * components list.
	 *
	 * Result array has these keys:
	 *   title       - Localized component title, up to 40 characters
	 *   description - A paragraph-size plain text description of the component, without linebreaks or HTML
	 *   image       - Image URL
	 *   application - One-word lowercase application CODE (same as folder name and admin panel code)
	 *
	 * Some values may be missing - reasonable defaults will be used. But it's strongly recommended to have
	 * at least title.
	 *
	 * @return array
	 */
	public function GetCoverInfo()
	{
		return [
			'title'       => ($this->lmsg)('thankyou.component.cover_info.title'),
			'description' => ($this->lmsg)('thankyou.component.cover_info.desc'),
			'application' => 'thankyou',
			'icon_class'  => 'glyphicons glyphicons-donate',
			'categories'  => ['people']
		];
	}

	/**
	 * Allows the component to change it's option definitions to reflect a change of values
	 *
	 * Input and output are the same array that GetOptions() returns, plus each items current value -
	 *
	 * [
	 *   'option_name' => ['type' => ...,
	 *                     'default' => ...,
	 *                     'title' => ...,
	 *                     'value' => ...,
	 *                    ],
	 *   'other_option' => ...
	 * ]
	 *
	 * @param array $options
	 *
	 * @return array
	 */
	public function MutateOptions($options)
	{
		if (empty($options['show_header']['value']))
		{
			unset($options['title']);
		}

		if (!$this->config_api->IsCommentsEnabled())
		{
			unset($options['comments']);
		}

		return $options;
	}
}
