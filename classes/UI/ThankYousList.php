<?php

namespace Claromentis\ThankYou\UI;

use Claromentis\Core\Application;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\Core\Templater\Plugin\TemplaterComponentTmpl;
use Claromentis\ThankYou\Api;
use DateClaTimeZone;

/**
 * Component displays list of recent thanks and allows submitting a new one.
 *
 **/
//TODO Fix spacing around Say Thank You button
class ThankYousList extends TemplaterComponentTmpl
{
	/**
	 * #Attributes
	 * * admin_mode:
	 *     * 1 = Editing and Deleting Thank Yous ignores permissions. Thank Yous are not filtered by Thanked Extranet Area ID.
	 * * create:
	 *     * 0 = Creating Thank Yous is disabled.
	 *     * 1 = Creating Thank Yous is enabled.
	 * * array = Creating ThankYous is locked to the Thankable array given (Created with \Claromentis\ThankYou\View\ThanksListView::ConvertThankableToArray).
	 * * delete:
	 *     * 0 = Deleting Thank Yous is disabled.
	 *     * 1 = Deleting Thank Yous is enabled (subject to permissions or admin_mode).
	 * * edit:
	 *     * 0 = Editing Thank Yous is disabled.
	 *     * 1 = Editing Thank Yous is enabled (subject to permissions or admin_mode).
	 * * thanked_images:
	 *     * 0 = Thanked will never display as an image.
	 *     * 1 = Thanked will display as an image if available.
	 * * links:
	 *     * 0 = Thanked will never provide a link.
	 *     * 1 = Thanked will provide a link if available.
	 * * limit:
	 *     * int = How many Thank Yous to display.
	 * * offset:
	 *     * int = Offset of Thank Yous.
	 * * user_id:
	 *     * int  = Only display Thank Yous associated with this User.
	 *
	 * @param array       $attributes
	 * @param Application $app
	 * @return string
	 */
	public function Show($attributes, Application $app): string
	{
		$api              = $app[Api::class];
		$lmsg             = $app[Lmsg::class];
		$security_context = $app[SecurityContext::class];

		$admin_mode = (bool) ($attributes['admin_mode'] ?? null);

		$extranet_area_id = $admin_mode ? null : (int) $security_context->GetExtranetAreaId();
		$time_zone        = DateClaTimeZone::GetCurrentTZ();

		$can_create       = (bool) ($attributes['create'] ?? null);
		$can_delete       = (bool) ($attributes['delete'] ?? null);
		$can_edit         = (bool) ($attributes['edit'] ?? null);
		$create_thankable = (isset($attributes['create']) && is_array($attributes['create'])) ? $attributes['create'] : null;
		$thanked_images   = (bool) ($attributes['thanked_images'] ?? null);
		$links            = (bool) ($attributes['links'] ?? null);
		$limit            = (int) ($attributes['limit'] ?? 20);
		$offset           = (int) ($attributes['offset'] ?? null);
		$user_id          = (isset($attributes['user_id'])) ? (int) $attributes['user_id'] : null;

		if (isset($user_id))
		{
			$thank_yous = $api->ThankYous()->GetUsersRecentThankYous($user_id, $limit, $offset, true);
		} else
		{
			$thank_yous = $api->ThankYous()->GetRecentThankYous($limit, $offset, true, $extranet_area_id);
		}

		$args            = [];
		$view_thank_yous = [];
		foreach ($thank_yous as $thank_you)
		{
			$view_thank_yous[] = [
				'thank_you.admin_mode'     => $admin_mode,
				'thank_you.delete'         => $can_delete,
				'thank_you.edit'           => $can_edit,
				'thank_you.links'         => $thanked_images,
				'thank_you.thanked_images' => $links,
				'thank_you.thank_you'      => $thank_you
			];
		}

		$args['thank_yous.datasrc'] = $view_thank_yous;

		if (count($args['thank_yous.datasrc']) === 0)
		{
			$args['no_thanks.body'] = $lmsg('thankyou.thanks_list.no_thanks');
		}

		if ($can_create)
		{
			$args['create.visible'] = 1;
			if (isset($create_thankable))
			{
				$args['preselected_thankable.json'] = $create_thankable;
			}
		} else
		{
			$args['create.visible'] = 0;
		}

		return $this->CallTemplater('thankyou/thank_yous_list.html', $args);
	}
}
