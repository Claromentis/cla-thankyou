<?php
namespace Claromentis\ThankYou\UI;

use Claromentis\Core\Application;
use Claromentis\Core\Templater\Plugin\TemplaterComponentTmpl;
use Claromentis\ThankYou\ThanksItem;
use Claromentis\ThankYou\ThanksRepository;
use Claromentis\ThankYou\View\ThanksListView;

/**
 * Component displays list of recent thanks for a particular user and allows submitting a new one.
 *
 * <component class="\Claromentis\ThankYou\UI\Wall" allow_new="1" user_id="123" limit="10">
 *
 * @author Alexander Polyanskikh
 */
class Wall extends TemplaterComponentTmpl
{
	/**
	 * @var array
	 */
	protected $default_attributes = [
		'profile_images' => false
	];

	/**
	 * Show the thanks wall.
	 *
	 * @param array $attributes
	 * @param Application $app
	 * @return string
	 */
	public function Show($attributes, Application $app)
	{
		$attributes = array_merge($this->default_attributes, $attributes);
		$args = array();

		/**
		 * @var ThanksRepository $repository
		 */
		$repository = $app['thankyou.repository'];

		$user_id = isset($attributes['user_id']) ? (int) $attributes['user_id'] : 0;

		if (!$user_id)
			return "No user ID given";

		$limit = isset($attributes['limit']) ? (int) $attributes['limit'] : 10;

		/**
		 * @var ThanksItem[] $thanks
		 */
		$thanks = $repository->GetForUser($user_id, $limit);

		/**
		 * @var ThanksListView $view
		 */
		$view = $app['thankyou.thanks_list_view'];
		$args['items.datasrc'] = $view->Show($thanks, $attributes);

		if (isset($attributes['allow_new']) && !(bool)$attributes['allow_new'])
		{
			$args['allow_new.visible'] = 0;
		} else
		{
			$args = $view->ShowAddNew($user_id) + $args;
		}

		$args['no_thanks.body'] = lmsg('thankyou.component.no_thanks_user', \User::GetNameById($user_id));

		$template = 'thankyou/wall.html';
		return $this->CallTemplater($template, $args);
	}
}
