<?php

namespace Claromentis\ThankYou\Controller;

use Claromentis\Core\Application;
use Claromentis\Core\Http\RedirectResponse;
use Claromentis\Core\Http\RequestData;
use Claromentis\Core\Http\RequestDataTokenException;
use Claromentis\Core\Http\TemplaterCallResponse;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\Core\Widget\Sugre\SugreRepository;
use Claromentis\ThankYou\Api;
use Claromentis\ThankYou\Exception\ThankYouForbidden;
use Claromentis\ThankYou\Exception\ThankYouInvalidUsers;
use Claromentis\ThankYou\Exception\ThankYouNotFound;
use DateClaTimeZone;
use Psr\Http\Message\ServerRequestInterface;

class ThanksController
{
	private $api;

	private $lmsg;

	private $sugre_repository;

	public function __construct(Lmsg $lmsg, Api $api, SugreRepository $sugre_repository)
	{
		$this->api              = $api;
		$this->lmsg             = $lmsg;
		$this->sugre_repository = $sugre_repository;
	}

	public function Admin(ServerRequestInterface $server_request, Application $app)
	{
		$limit = 20;

		$query_params = $server_request->getQueryParams();
		$offset = (int) ($query_params['st'] ?? null);

		$thank_yous = $this->api->ThankYous()->GetRecentThankYous($limit, $offset, true);
		$args = $this->api->ThankYous()->GetThankYousListArgs($thank_yous, DateClaTimeZone::GetCurrentTZ(), false, false, true, true, true);

		$args['nav_messages.+class'] = 'active';

		require_once('paging.php');

		$args['paging.body_html'] = get_navigation($server_request->getUri()->getPath(), $this->api->ThankYous()->GetTotalThankYousCount(), $offset, '', $limit);

		return new TemplaterCallResponse('thankyou/admin/admin.html', $args, ($this->lmsg)('thankyou.app_name'));
	}

	/**
	 * Create a new Thank You, or update an existing Thank You.
	 *
	 * @param RequestData            $request_data
	 * @param ServerRequestInterface $request
	 * @param SecurityContext        $security_context
	 * @return RedirectResponse
	 * @throws
	 */
	public function CreateOrUpdate(RequestData $request_data, ServerRequestInterface $request, SecurityContext $security_context)
	{
		$request_data->CheckToken();
		$post = $request->getParsedBody();

		$redirect = $request->getServerParams()['HTTP_REFERER'];

		$id          = (int) ($post['thank_you_id'] ?? null);
		$thanked     = (array) ($post['thank_you_user'] ?? $post['thank_you_preselected'] ?? null);
		$description = (string) $post['thank_you_description'] ?? '';

		if (isset($thanked))
		{
			$thanked = $this->sugre_repository->DecodeOutput($thanked);
		}

		try
		{
			if ($id === 0)
			{
				$this->api->ThankYous()->CreateAndSave($security_context->GetUser(), $thanked, $description);
			} else
			{
				try
				{
					$this->api->ThankYous()->UpdateAndSave($security_context, $id, $thanked, $description);
				} catch (ThankYouNotFound $thank_you_not_found)
				{
					return RedirectResponse::httpRedirect($redirect, ($this->lmsg)('thankyou.error.thanks_not_found'), true);
				} catch (ThankYouForbidden $thank_you_forbidden)
				{
					return RedirectResponse::httpRedirect($redirect, ($this->lmsg)('thankyou.error.no_edit_permission'), true);
				}
			}
		} catch (ThankYouInvalidUsers $thank_you_invalid_users)
		{
			return RedirectResponse::httpRedirect($redirect, ($this->lmsg)('thankyou.error.invalid_users'), true);
		}

		return RedirectResponse::httpRedirect($redirect);
	}

	/**
	 * @param RequestData            $request_data
	 * @param SecurityContext        $security_context
	 * @param ServerRequestInterface $request
	 * @return RedirectResponse
	 * @throws RequestDataTokenException
	 */
	public function Delete(RequestData $request_data, SecurityContext $security_context, ServerRequestInterface $request)
	{
		$request_data->CheckToken();
		$post     = $request->getParsedBody();
		$id       = (int) ($post['thank_you_id'] ?? null);
		$redirect = $request->getServerParams()['HTTP_REFERER'];

		try
		{
			$this->api->ThankYous()->Delete($security_context, $id);
		} catch (ThankYouNotFound $thank_you_not_found)
		{
			return RedirectResponse::httpRedirect($redirect, ($this->lmsg)('thankyou.error.thanks_not_found'), true);
		} catch (ThankYouForbidden $thank_you_forbidden)
		{
			return RedirectResponse::httpRedirect($redirect, ($this->lmsg)('thankyou.error.no_edit_permission'), true);
		}

		return RedirectResponse::httpRedirect($redirect, ($this->lmsg)('thankyou.common.thanks_deleted'), false);
	}
}