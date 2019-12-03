<?php

namespace Claromentis\ThankYou\ThankYous;

use Claromentis\Core\Localization\Lmsg;
use Claromentis\ThankYou\Exception\ThankYouAuthor;
use Date;
use InvalidArgumentException;
use User;

class ThankYouFactory
{
	private $lmsg;

	public function __construct(Lmsg $lmsg)
	{
		$this->lmsg = $lmsg;
	}

	/**
	 * @param User|int  $author
	 * @param string    $description
	 * @param Date|null $date_created
	 * @return ThankYou
	 * @throws ThankYouAuthor - If the Author could not be loaded.
	 */
	public function Create($author, ?Date $date_created, string $description)
	{
		if (is_int($author))
		{
			$author = new User($author);
		}

		if (!($author instanceof User))
		{
			throw new InvalidArgumentException("Failed to Create Thank You, invalid Author");
		}

		if (!$author->IsLoaded() && !$author->Load())
		{
			$author->SetFirstname('');
			$author->SetSurname(($this->lmsg)('orgchart.common.deleted_user'));
		}

		if (!isset($date_created))
		{
			$date_created = new Date();
		}

		return new ThankYou($author, $date_created, $description);
	}
}
