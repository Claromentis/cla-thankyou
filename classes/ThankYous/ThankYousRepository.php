<?php

namespace Claromentis\ThankYou\ThankYous;

use Analogue\ORM\Exceptions\MappingException;
use Claromentis\Core\Acl\PermOClass;
use Claromentis\Core\CDN\CDNSystemException;
use Claromentis\Core\DAL\Exceptions\TransactionException;
use Claromentis\Core\DAL\Interfaces\DbInterface;
use Claromentis\Core\DAL\QueryBuilder;
use Claromentis\Core\DAL\QueryFactory;
use Claromentis\Core\Repository\Exception\StorageException;
use Claromentis\People\Repository\UserRepository;
use Claromentis\ThankYou\Exception\ThankableException;
use Claromentis\ThankYou\Tags;
use Claromentis\ThankYou\Exception\UnsupportedOwnerClassException;
use Claromentis\ThankYou\Thankable;
use Date;
use DateTimeZone;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use User;

class ThankYousRepository
{
	const AGGREGATION_ID = 143;
	const THANKABLES = [PermOClass::INDIVIDUAL, PermOClass::GROUP];

	const TAG_TABLE = 'thankyou_tag';
	const THANK_YOU_TABLE = 'thankyou_item';
	const THANKED_USERS_TABLE = 'thankyou_user';
	const THANK_YOU_TAGS_TABLE = 'thankyou_tagged';
	const USER_TABLE = 'users';
	const GROUP_TABLE = 'groups';
	const THANKED_TABLE = 'thankyou_thanked';

	/**
	 * @var DbInterface
	 */
	private $db;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var Thankable\Factory
	 */
	private $thankable_factory;

	/**
	 * @var ThankYouFactory
	 */
	private $thank_you_factory;

	/**
	 * @var UserRepository
	 */
	private $user_repository;

	/**
	 * @var ThankYouUtility
	 */
	private $utility;

	/**
	 * @var QueryFactory
	 */
	private $query_factory;

	/**
	 * @var Tags\Api
	 */
	private $tags;

	/**
	 * ThankYousRepository constructor.
	 *
	 * @param ThankYouFactory   $thank_you_factory
	 * @param ThankYouUtility   $thank_you_utility
	 * @param DbInterface       $db_interface
	 * @param UserRepository    $user_repository
	 * @param LoggerInterface   $logger
	 * @param QueryFactory      $query_factory
	 * @param Tags\Api          $tag_api
	 * @param Thankable\Factory $thankable_factory
	 */
	public function __construct(
		ThankYouFactory $thank_you_factory,
		ThankYouUtility $thank_you_utility,
		DbInterface $db_interface,
		UserRepository $user_repository,
		LoggerInterface $logger,
		QueryFactory $query_factory,
		Tags\Api $tag_api,
		Thankable\Factory $thankable_factory
	) {
		$this->thank_you_factory = $thank_you_factory;
		$this->utility           = $thank_you_utility;
		$this->user_repository   = $user_repository;
		$this->db                = $db_interface;
		$this->logger            = $logger;
		$this->query_factory     = $query_factory;
		$this->tags              = $tag_api;
		$this->thankable_factory = $thankable_factory;
	}

	/**
	 * Given an array of Thank You IDs, returns an array of Thank Yous indexed by their IDs.
	 *
	 * @param int[] $ids
	 * @return ThankYou[]
	 * @throws MappingException
	 */
	public function GetThankYous(array $ids)
	{
		if (count($ids) === 0)
		{
			return [];
		}

		foreach ($ids as $id)
		{
			if (!is_int($id))
			{
				throw new InvalidArgumentException("Failed to Get Thank Yous, invalid ID given");
			}
		}

		$query_string = "SELECT * FROM " . self::THANK_YOU_TABLE;

		$query = $this->query_factory->GetQueryBuilder($query_string);

		$query->AddWhereAndClause("id IN in:int:ids", $ids);

		$result = $this->db->query($query->GetQuery());

		$rows     = [];
		$user_ids = [];
		while ($row = $result->fetchArray())
		{
			$id           = (int) $row['id'];
			$author_id    = (int) $row['author'];
			$date_created = (string) $row['date_created'];

			$rows[$id] = ['author_id' => $author_id, 'date_created' => $date_created, 'description' => $row['description']];

			$user_ids[$author_id] = true;
		}

		$user_ids = array_keys($user_ids);

		$users = $this->user_repository->find($user_ids);

		$thank_yous = [];
		foreach ($ids as $id)
		{
			if (!isset($rows[$id]))
			{
				continue;
			}

			$thank_you = $this->Create(
				($users->find($rows[$id]['author_id']) ?? $rows[$id]['author_id']),
				$rows[$id]['description'] ?? '',
				new Date($rows[$id]['date_created'], new DateTimeZone('UTC'))
			);
			$thank_you->SetId($id);

			$thank_yous[$id] = $thank_you;
		}

		return $thank_yous;
	}

	/**
	 * @param int        $limit
	 * @param int        $offset
	 * @param array|null $date_range
	 * @param int[]|null $thanked_user_ids
	 * @param int[]|null $tag_ids
	 * @param int[]|null $extranet_ids
	 * @param bool       $allow_no_thanked
	 * @return int[]
	 */
	public function GetRecentThankYousIds(?int $limit = null, ?int $offset = null, ?array $extranet_ids = null, bool $allow_no_thanked = true, ?array $date_range = null, ?array $thanked_user_ids = null, ?array $tag_ids = null)
	{
		$table = self::THANK_YOU_TABLE;

		$query = "
			SELECT $table.id
			FROM $table
			GROUP BY $table.id, $table.date_created
			ORDER BY $table.date_created DESC";

		$query = $this->query_factory->GetQueryBuilder($query);

		$query->setLimit($limit, $offset);

		if (isset($date_range))
		{
			$this->QueryAddCreatedBetweenFilter($query, $date_range);
		}

		if (isset($extranet_ids) || isset($thanked_user_ids))
		{
			$query->AddJoin(self::THANK_YOU_TABLE, self::THANKED_USERS_TABLE, self::THANKED_USERS_TABLE, self::THANK_YOU_TABLE . ".id = " . self::THANKED_USERS_TABLE . ".thanks_id");
		}

		if (isset($thanked_user_ids))
		{
			$this->QueryAddThankedUserFilter($query, $thanked_user_ids);
		}

		if (isset($tag_ids))
		{
			$this->QueryJoinThankYouToTagged($query);
			$this->QueryAddTagsFilter($query, $tag_ids);
		}

		if (isset($extranet_ids))
		{
			$query->AddJoin(self::THANKED_USERS_TABLE, self::USER_TABLE, self::USER_TABLE, self::THANKED_USERS_TABLE . ".user_id = " . self::USER_TABLE . ".id");
			$this->QueryAddExtranetFilter($query, $extranet_ids, $allow_no_thanked);
		}

		$result = $this->db->query($query->GetQuery());

		$thank_you_ids = [];
		while ($row = $result->fetchArray())
		{
			$thank_you_ids[] = (int) $row['id'];
		}

		return $thank_you_ids;
	}

	/**
	 * Given an array of Thank You IDs, returns an array of Thankeds, indexed by the Thank You's ID and then the Thanked's ID.
	 *
	 * @param int[] $ids
	 * @return array[Thankable]
	 * @throws MappingException
	 */
	public function GetThankYousThankedsByThankYouIds(array $ids)
	{
		if (count($ids) === 0)
		{
			return [];
		}

		$query_string = "SELECT id, item_id, object_type, object_id FROM " . self::THANKED_TABLE;

		$query = $this->query_factory->GetQueryBuilder($query_string);

		$query->AddWhereAndClause(self::THANKED_TABLE . ".item_id IN in:int:thank_you_ids", $ids);

		$results = $this->db->query($query->GetQuery());

		$thank_yous_thankeds = [];
		$thankeds            = [];
		while ($row = $results->fetchArray())
		{
			$id                    = (int) $row['id'];
			$thank_you_id          = (int) $row['item_id'];
			$owner_class_id        = (int) $row['object_type'];
			$owner_classes_item_id = (int) $row['object_id'];

			$thankeds[$id] = ['oclass' => $owner_class_id, 'id' => $owner_classes_item_id];

			$thank_yous_thankeds[$thank_you_id][$id] = true;
		}

		try
		{
			$thankeds = $this->CreateThankablesFromOClasses($thankeds);
		} catch (UnsupportedOwnerClassException $exception)
		{
			$this->logger->error("One or more Thanked in the Repository is invalid", [$exception]);

			return [];
		}

		foreach ($thank_yous_thankeds as $thank_you_id => $thank_you_thankeds)
		{
			foreach ($thank_you_thankeds as $id => $true)
			{
				$thank_yous_thankeds[$thank_you_id][$id] = $thankeds[$id];
			}
		}

		return $thank_yous_thankeds;
	}

	/**
	 * Given and array of Thank You IDs, returns an array of Thank You Users, indexed by the Thank Yous's ID.
	 *
	 * @param int[] $ids
	 * @return array[]
	 * @throws MappingException
	 */
	public function GetThankYousUsersByThankYouIds(array $ids)
	{
		if (count($ids) === 0)
		{
			return [];
		}

		$query_string = "SELECT * FROM " . self::THANKED_USERS_TABLE;
		$query        = $this->query_factory->GetQueryBuilder($query_string);

		$query->AddWhereAndClause(self::THANKED_USERS_TABLE . ".thanks_id IN in:int:ids", $ids);

		$results = $this->db->query($query->GetQuery());

		$user_ids = [];
		$rows     = [];
		while ($row = $results->fetchArray())
		{
			$thank_you_id = (int) $row['thanks_id'];
			$user_id      = (int) $row['user_id'];

			$rows[]             = ['thank_you_id' => $thank_you_id, 'user_id' => $user_id];
			$user_ids[$user_id] = true;
		}

		$user_ids = array_keys($user_ids);

		$users_entity_collection = $this->user_repository->find($user_ids);

		$thank_yous_users = [];
		foreach ($rows as $row)
		{
			$user = $users_entity_collection->find($row['user_id']);
			if (isset($user))
			{
				$thank_yous_users[$row['thank_you_id']][$row['user_id']] = $user;
			}
		}

		return $thank_yous_users;
	}

	public function GetTagsTotalThankYouUses(?array $orders = null, ?int $limit = null, ?int $offset = null, ?array $extranet_ids = null, bool $allow_no_thanked = true, ?array $date_range = null, ?array $thanked_user_ids = null, ?array $tag_ids = null)
	{
		$order = "";
		if (isset($orders))
		{
			$order = $this->utility->BuildOrderString($orders);
		}

		$query_string = "SELECT COUNT(" . self::THANK_YOU_TAGS_TABLE . ".item_id) AS \"" . self::THANK_YOU_TAGS_TABLE . ".total_uses\"";
		$query_string .= ", " . self::TAG_TABLE . ".id AS \"" . self::TAG_TABLE . ".id\"";
		$query_string .= " FROM " . self::TAG_TABLE;
		$query_string .= $order;
		$query_string .= " GROUP BY " . self::TAG_TABLE . ".id";

		$query = $this->query_factory->GetQueryBuilder($query_string);
		$query->AddWhereAndClause(self::THANK_YOU_TAGS_TABLE . ".aggregation_id = " . self::AGGREGATION_ID);

		$query->AddJoin(self::TAG_TABLE, self::THANK_YOU_TAGS_TABLE, self::THANK_YOU_TAGS_TABLE, self::THANK_YOU_TAGS_TABLE . ".tag_id = " . self::TAG_TABLE . ".id");

		if (isset($thanked_user_ids) || isset($extranet_ids))
		{
			$query->AddJoin(self::THANK_YOU_TAGS_TABLE, self::THANKED_USERS_TABLE, self::THANKED_USERS_TABLE, self::THANK_YOU_TAGS_TABLE . ".item_id = " . self::THANKED_USERS_TABLE . ".thanks_id");
		}

		if (isset($thanked_user_ids))
		{
			$this->QueryAddThankedUserFilter($query, $thanked_user_ids);
		}

		if (isset($date_range))
		{
			$query->AddJoin(self::THANK_YOU_TAGS_TABLE, self::THANK_YOU_TABLE, self::THANK_YOU_TABLE, self::THANK_YOU_TAGS_TABLE . ".item_id = " . self::THANK_YOU_TABLE . ".id");
			$this->QueryAddCreatedBetweenFilter($query, $date_range);
		}

		if (isset($tag_ids))
		{
			$this->QueryAddTagsFilter($query, $tag_ids);
		}

		if (isset($extranet_ids))
		{
			$query->AddJoin(self::THANKED_USERS_TABLE, self::USER_TABLE, self::USER_TABLE, self::THANKED_USERS_TABLE . ".user_id = " . self::USER_TABLE . ".id");
			$this->QueryAddExtranetFilter($query, $extranet_ids, $allow_no_thanked);
		}

		$query->SetLimit($limit, $offset);

		$result = $this->db->query($query->GetQuery());

		$tags_total_thank_yous = [];
		while ($row = $result->fetchArray())
		{
			$tags_total_thank_yous[(int) $row[self::TAG_TABLE . ".id"]] = (int) $row[self::THANK_YOU_TAGS_TABLE . ".total_uses"];
		}

		if (isset($tag_ids))
		{
			foreach ($tag_ids as $tag_id)
			{
				if (!isset($tags_total_thank_yous[$tag_id]))
				{
					$tags_total_thank_yous[$tag_id] = 0;
				}
			}
		}

		return $tags_total_thank_yous;
	}

	/**
	 * Returns total number of thanks items in the database
	 *
	 * @param int[]|null $extranet_ids
	 * @param bool       $allow_no_thanked
	 * @param array|null $date_range
	 * @param int[]|null $thanked_user_ids
	 * @param int[]|null $tag_ids
	 * @return int
	 */
	public function GetTotalThankYousCount(?array $extranet_ids = null, bool $allow_no_thanked = true, ?array $date_range = null, ?array $thanked_user_ids = null, ?array $tag_ids = null): int
	{
		$query_string = "SELECT COUNT(DISTINCT " . self::THANK_YOU_TABLE . ".id) FROM " . self::THANK_YOU_TABLE;

		$query = $this->query_factory->GetQueryBuilder($query_string);

		if (isset($extranet_ids) || isset($thanked_user_ids))
		{
			$query->AddJoin(self::THANK_YOU_TABLE, self::THANKED_USERS_TABLE, self::THANKED_USERS_TABLE, self::THANK_YOU_TABLE . ".id = " . self::THANKED_USERS_TABLE . ".thanks_id");
		}

		if (isset($extranet_ids))
		{
			$query->AddJoin(self::THANKED_USERS_TABLE, self::USER_TABLE, self::USER_TABLE, self::THANKED_USERS_TABLE . ".user_id = " . self::USER_TABLE . ".id");
			$this->QueryAddExtranetFilter($query, $extranet_ids, $allow_no_thanked);
		}

		if (isset($date_range))
		{
			$this->QueryAddCreatedBetweenFilter($query, $date_range);
		}

		if (isset($thanked_user_ids))
		{
			$this->QueryAddThankedUserFilter($query, $thanked_user_ids);
		}

		if (isset($tag_ids))
		{
			$this->QueryJoinThankYouToTagged($query);
			$this->QueryAddTagsFilter($query, $tag_ids);
		}

		[$count] = $this->db->query_row($query->GetQuery());

		return $count;
	}

	/**
	 * Returns an array of the total number of Thank Yous associated with a User, indexed by the User's ID.
	 *
	 * @param int[]      $user_ids
	 * @param array|null $date_range
	 * @param int[]|null $tag_ids
	 * @param int[]|null $extranet_ids
	 * @param int|null   $limit
	 * @param int|null   $offset
	 * @return int[]
	 */
	public function GetTotalUsersThankYous(?int $limit = null, ?int $offset = null, ?array $user_ids = null, ?array $date_range = null, ?array $tag_ids = null, ?array $extranet_ids = null): array
	{
		$query_string = "SELECT COUNT(" . self::THANKED_USERS_TABLE . ".thanks_id) AS \"" . self::THANKED_USERS_TABLE . ".total_thank_yous\"";
		$query_string .= ", " . self::USER_TABLE . ".id AS \"" . self::USER_TABLE . ".id\"";
		$query_string .= " FROM " . self::USER_TABLE;
		$query_string .= " ORDER BY " . self::USER_TABLE . ".firstname ASC";
		$query_string .= " GROUP BY " . self::USER_TABLE . ".id";

		$query = $this->query_factory->GetQueryBuilder($query_string);

		$query->AddJoin(self::USER_TABLE, self::THANKED_USERS_TABLE, self::THANKED_USERS_TABLE, self::THANKED_USERS_TABLE . ".user_id = " . self::USER_TABLE . ".id");

		if (isset($user_ids))
		{
			$this->QueryAddThankedUserFilter($query, $user_ids);
		}

		if (isset($date_range))
		{
			$query->AddJoin(self::THANKED_USERS_TABLE, self::THANK_YOU_TABLE, self::THANK_YOU_TABLE, self::THANKED_USERS_TABLE . ".thanks_id = " . self::THANK_YOU_TABLE . ".id");
			$this->QueryAddCreatedBetweenFilter($query, $date_range);
		}

		if (isset($tag_ids))
		{
			$this->QueryJoinThankedUsersToTagged($query);
			$this->QueryAddTagsFilter($query, $tag_ids);
		}

		if (isset($extranet_ids))
		{
			$this->QueryAddExtranetFilter($query, $extranet_ids);
		}

		$query->SetLimit($limit, $offset);

		$result = $this->db->query($query->GetQuery());

		$users_total_thank_yous = [];
		while ($row = $result->fetchArray())
		{
			$users_total_thank_yous[(int) $row[self::USER_TABLE . ".id"]] = (int) $row[self::THANKED_USERS_TABLE . ".total_thank_yous"];
		}

		if (isset($user_ids))
		{
			foreach ($user_ids as $user_id)
			{
				if (!isset($users_total_thank_yous[$user_id]))
				{
					$users_total_thank_yous[$user_id] = 0;
				}
			}
		}

		return $users_total_thank_yous;
	}

	/**
	 * @param int[]|null $user_ids
	 * @param array|null $date_range
	 * @param int[]|null $tag_ids
	 * @param int[]|null $extranet_ids
	 * @return int
	 */
	public function GetTotalUsers(?array $user_ids = null, ?array $date_range = null, ?array $tag_ids = null, ?array $extranet_ids = null): int
	{
		$query_string = "SELECT COUNT(DISTINCT " . self::USER_TABLE . ".id) FROM " . self::USER_TABLE;

		$query = $this->query_factory->GetQueryBuilder($query_string);

		$query->AddJoin(self::USER_TABLE, self::THANKED_USERS_TABLE, self::THANKED_USERS_TABLE, self::THANKED_USERS_TABLE . ".user_id = " . self::USER_TABLE . ".id");

		if (isset($user_ids))
		{
			$this->QueryAddThankedUserFilter($query, $user_ids);
		}

		if (isset($date_range))
		{
			$query->AddJoin(self::THANKED_USERS_TABLE, self::THANK_YOU_TABLE, self::THANK_YOU_TABLE, self::THANKED_USERS_TABLE . ".thanks_id = " . self::THANK_YOU_TABLE . ".id");
			$this->QueryAddCreatedBetweenFilter($query, $date_range);
		}

		if (isset($tag_ids))
		{
			$this->QueryJoinThankedUsersToTagged($query);
			$this->QueryAddTagsFilter($query, $tag_ids);
		}

		if (isset($extranet_ids))
		{
			$query->AddJoin(self::THANKED_USERS_TABLE, self::USER_TABLE, self::USER_TABLE, self::THANKED_USERS_TABLE . ".user_id = " . self::USER_TABLE . ".id");
			$this->QueryAddExtranetFilter($query, $extranet_ids);
		}

		[$count] = $this->db->query_row($query->GetQuery());

		return $count;
	}

	/**
	 * Returns the number of tags which satisfy the filtering provided.
	 *
	 * @param int[]|null $extranet_ids
	 * @param bool       $allow_no_thanked
	 * @param array|null $date_range
	 * @param int[]|null $thanked_user_ids
	 * @param int[]|null $tag_ids
	 * @return int
	 */
	public function GetTotalTags(?array $extranet_ids = null, bool $allow_no_thanked = true, ?array $date_range = null, ?array $thanked_user_ids = null, ?array $tag_ids = null): int
	{
		$query_string = "SELECT COUNT(DISTINCT " . self::TAG_TABLE . ".id) FROM " . self::TAG_TABLE;

		$query = $this->query_factory->GetQueryBuilder($query_string);
		$query->AddWhereAndClause(self::THANK_YOU_TAGS_TABLE . ".aggregation_id = " . self::AGGREGATION_ID);

		$query->AddJoin(self::TAG_TABLE, self::THANK_YOU_TAGS_TABLE, self::THANK_YOU_TAGS_TABLE, self::THANK_YOU_TAGS_TABLE . ".tag_id = " . self::TAG_TABLE . ".id");

		if (isset($thanked_user_ids) || isset($extranet_ids))
		{
			$query->AddJoin(self::THANK_YOU_TAGS_TABLE, self::THANKED_USERS_TABLE, self::THANKED_USERS_TABLE, self::THANK_YOU_TAGS_TABLE . ".item_id = " . self::THANKED_USERS_TABLE . ".thanks_id");
		}

		if (isset($thanked_user_ids))
		{
			$this->QueryAddThankedUserFilter($query, $thanked_user_ids);
		}

		if (isset($date_range))
		{
			$query->AddJoin(self::THANK_YOU_TAGS_TABLE, self::THANK_YOU_TABLE, self::THANK_YOU_TABLE, self::THANK_YOU_TAGS_TABLE . ".item_id = " . self::THANK_YOU_TABLE . ".id");
			$this->QueryAddCreatedBetweenFilter($query, $date_range);
		}

		if (isset($tag_ids))
		{
			$this->QueryAddTagsFilter($query, $tag_ids);
		}

		if (isset($extranet_ids))
		{
			$query->AddJoin(self::THANKED_USERS_TABLE, self::USER_TABLE, self::USER_TABLE, self::THANKED_USERS_TABLE . ".user_id = " . self::USER_TABLE . ".id");
			$this->QueryAddExtranetFilter($query, $extranet_ids, $allow_no_thanked);
		}

		[$count] = $this->db->query_row($query->GetQuery());

		return $count;
	}

	/**
	 * Create a Thank You object.
	 *
	 * @param \Claromentis\People\Entity\User|int $author
	 * @param string                              $description
	 * @param Date|null                           $date_created
	 * @return ThankYou
	 */
	public function Create($author, string $description, ?Date $date_created = null)
	{
		return $this->thank_you_factory->Create($author, $description, $date_created);
	}

	/**
	 * Takes an array of arrays in the format ['oclass' => int, 'id' => int]
	 * Returns an array of Thanked Objects, retaining indexing.
	 *
	 * @param array $thankeds
	 * @return Thankable\Thankable[]
	 * @throws UnsupportedOwnerClassException - If one or more of the Owner Classes given is not supported.
	 * @throws MappingException
	 */
	public function CreateThankablesFromOClasses(array $thankeds): array
	{
		//TODO: Expand accepted objects to include all PERM_OCLASS_*
		$owner_classes_ids = [];
		foreach ($thankeds as $thanked)
		{
			if (!isset($thanked['oclass']))
			{
				throw new InvalidArgumentException("Failed to Get Permission Object Classes Names, Object Class not specified");
			}

			if (!in_array($thanked['oclass'], self::THANKABLES))
			{
				throw new UnsupportedOwnerClassException("Failed to Get Permission Object Classes Names, Object class is not supported");
			}

			if (!isset($thanked['id']) || !is_int($thanked['id']))
			{
				throw new InvalidArgumentException("Failed to Get Permission Object Classes Names, Object ID is not specified or is invalid");
			}

			if (!isset($owner_classes_ids[$thanked['oclass']]))
			{
				$owner_classes_ids[$thanked['oclass']] = [];
			}

			$owner_classes_ids[$thanked['oclass']][$thanked['id']] = true;
		}

		if (isset($owner_classes_ids[PermOClass::GROUP]))
		{
			$owner_classes_ids[PermOClass::GROUP] = $this->CreateThankablesFromGroupIds(array_keys($owner_classes_ids[PermOClass::GROUP]));
		}

		if (isset($owner_classes_ids[PermOClass::INDIVIDUAL]))
		{
			$owner_classes_ids[PermOClass::INDIVIDUAL] = $this->CreateThankablesFromUserIds(array_keys($owner_classes_ids[PermOClass::INDIVIDUAL]));
		}

		foreach ($thankeds as $offset => $thanked)
		{
			$thankeds[$offset] = $owner_classes_ids[$thanked['oclass']][$thanked['id']];
		}

		return $thankeds;
	}

	/**
	 * Create an array of Thankables from an array of Group IDs. The returned array is indexed by the Group's ID
	 *
	 * @param int[] $groups_ids
	 * @return Thankable\Thankable[]
	 */
	public function CreateThankablesFromGroupIds(array $groups_ids): array
	{
		$owner_class_id = PermOClass::GROUP;

		foreach ($groups_ids as $groups_id)
		{
			if (!is_int($groups_id))
			{
				throw new InvalidArgumentException("Failed to Create Thankables from Groups, invalid Group ID provided");
			}
		}

		$result = $this->db->query("SELECT groupid, groupname, ex_area_id FROM " . self::GROUP_TABLE . " WHERE groupid IN in:int:groups ORDER BY groupid", $groups_ids);

		$group_thankables = [];
		while ($group = $result->fetchArray())
		{
			$id                    = (int) $group['groupid'];
			$group_thankables[$id] = $this->thankable_factory->Create($group['groupname'], $id, $owner_class_id, (int) $group['ex_area_id']);
		}

		foreach ($groups_ids as $groups_id)
		{
			if (!isset($group_thankables[$groups_id]))
			{
				$group_thankables[$groups_id] = $this->thankable_factory->CreateUnknown($groups_id, $owner_class_id);
			}
		}

		return $group_thankables;
	}

	/**
	 * Creates Thankables from User IDs. If the User cannot be found, a substitute Thankable will be created.
	 * Returns array indexed by the IDs.
	 *
	 * @param int[] $user_ids
	 * @return Thankable\Thankable[]
	 * @throws MappingException
	 */
	public function CreateThankablesFromUserIds(array $user_ids)
	{
		$owner_class_id = PermOClass::INDIVIDUAL;

		$users_entity_collection = $this->user_repository->find($user_ids);

		$thankables = $this->CreateThankablesFromUsers($users_entity_collection->getDictionary());

		foreach ($user_ids as $user_id)
		{
			if (!isset($thankables[$user_id]))
			{
				$thankables[$user_id] = $this->thankable_factory->CreateUnknown($user_id, $owner_class_id);
			}
		}

		return $thankables;
	}

	/**
	 * Creates an array of Thankables from an array of Users. Retains indexes.
	 *
	 * @param \Claromentis\People\Entity\User[] $users
	 * @return Thankable\Thankable[]
	 */
	public function CreateThankablesFromUsers(array $users)
	{
		$owner_class_id = PermOClass::INDIVIDUAL;

		foreach ($users as $user_offset => $user)
		{
			if (!($user instanceof \Claromentis\People\Entity\User))
			{
				throw new InvalidArgumentException("Failed to Create Thankables From Users, invalid object passed");
			}

			try
			{
				//TODO: Replace with a non-static post People API update
				$user_image_url = User::GetPhotoUrl($user->id);
			} catch (CDNSystemException $cdn_system_exception)
			{
				$this->logger->error("Failed to Get User's Photo URL when Creating Thankable: " . $cdn_system_exception->getMessage());
				$user_image_url = null;
			}

			//TODO: Replace with a non-static post People API update
			$user_profile_url = User::GetProfileUrl($user->id, false);

			$users[$user_offset] = $this->thankable_factory->Create($user->getFullname(), $user->id, $owner_class_id, $user->extranet_id, $user_image_url, $user_profile_url);
		}

		return $users;
	}

	/**
	 * Saves a Thank You to the repository, including its Users and Thankeds, but excluding Tags.
	 * If the Thank You is new the ID its ID will also be set.
	 * If a Thanked is new its ID will also be set.
	 *
	 * @param ThankYou $thank_you
	 * @return int ID of saved Thank You
	 */
	public function Save(ThankYou $thank_you)
	{
		$id = $this->SaveThankYou($thank_you);

		$thank_you->SetId($id);

		$thankeds = $thank_you->GetThankables();
		if (isset($thankeds))
		{
			$this->DeleteThankYouThanked($id);

			foreach ($thankeds as $thanked)
			{
				try
				{
					$thanked_id = $this->SaveThanked($id, $thanked);
					$thanked->SetId($thanked_id);
				} catch (ThankableException $exception)
				{
					$this->logger->warning("Could not save a Thank You's Thanked, not enough data", [$exception]);
				}
			}
		}

		$thanked_users = $thank_you->GetUsers();
		if (isset($thanked_users))
		{
			$this->DeleteThankYouUsers($id);

			foreach ($thanked_users as $thanked_user)
			{
				$this->SaveUser($id, $thanked_user);
			}
		}

		return $id;
	}

	/**
	 * Given a Thank You ID, deletes the Thank You and any hard dependencies from the repository.
	 *
	 * @param int $id
	 * @throws StorageException - If the Thank You could not be deleted from the repository.
	 */
	public function Delete(int $id)
	{
		try
		{
			$this->db->DoTransaction(function () use ($id) {
				$this->DeleteThankYouUsers($id);
				$this->DeleteThankYouThanked($id);
				$this->DeleteThankYou($id);
			});
		} catch (TransactionException $exception)
		{
			throw new StorageException("Failed to Delete Thank You from Repository", null, $exception);
		}
	}

	/**
	 * Saves a Thank You. If an ID is provided, an existing record will be updated, if not a new entry will be created.
	 * Returns the ID of the saved Thank You.
	 *
	 * @param ThankYou $thank_you - The Thank You to be saved.
	 * @return int - The ID of the Thank You.
	 */
	private function SaveThankYou(ThankYou $thank_you): int
	{
		$id = $thank_you->GetId();

		$author_id = $thank_you->GetAuthor()->id;

		$date_created = clone $thank_you->GetDateCreated();
		$date_created->setTimezone(new DateTimeZone("UTC"));
		$date_created_string = $date_created->format('YmdHis');

		$description = $thank_you->GetDescription();

		$db_fields = [
			'int:author'       => $author_id,
			'int:date_created' => $date_created_string,
			'clob:description' => $description
		];

		if (isset($id))
		{
			$query = $this->query_factory->GetQueryUpdate(self::THANK_YOU_TABLE, "id=int:id", $db_fields);
			$query->Bind('id', $id);
			$this->db->query($query);
		} else
		{
			$query = $this->query_factory->GetQueryInsert(self::THANK_YOU_TABLE, $db_fields);
			$this->db->query($query);
			$id = $this->db->insertId();
		}

		return $id;
	}

	/**
	 * Saves a Thanked to the Repository. If an ID is provided, an existing record will be updated,
	 * if not a new entry will be created.
	 * Due to the hard dependency on the Thank You, it is recommended that a check has been done for the the Thank You
	 * with the given ID prior to calling this.
	 * Returns the ID of the saved Thanked.
	 *
	 * @param int                 $thank_you_id
	 * @param Thankable\Thankable $thankable
	 * @return int
	 * @throws ThankableException - If the Thankable does not have an Owner Class ID or Item ID.
	 */
	private function SaveThanked(int $thank_you_id, Thankable\Thankable $thankable): int
	{
		$id             = $thankable->GetId();
		$owner_class_id = $thankable->GetOwnerClass();
		$item_id        = $thankable->GetItemId();

		if (!isset($owner_class_id))
		{
			throw new ThankableException("Failed to Save Thanked, Thanked does not have an Owner Class ID set");
		}
		if (!isset($item_id))
		{
			throw new ThankableException("Failed to Save Thanked, Thanked does not have an Item ID set");
		}

		$db_fields = [
			'int:item_id'     => $thank_you_id,
			'int:object_type' => $owner_class_id,
			'int:object_id'   => $item_id
		];

		if (isset($id))
		{
			$query = $this->query_factory->GetQueryUpdate(self::THANKED_TABLE, "id=int:id", $db_fields);
			$query->Bind('id', $id);
			$this->db->query($query);
		} else
		{
			$query = $this->query_factory->GetQueryInsert(self::THANKED_TABLE, $db_fields);
			$this->db->query($query);
			$id = $this->db->insertId();
		}

		return $id;
	}

	/**
	 * Saves a Thank You's thanked User.
	 * Due to the hard dependency on the Thank You, it is recommended that a check has been done for the the Thank You
	 * with the given ID prior to calling this.
	 *
	 * @param int                             $thank_you_id
	 * @param \Claromentis\People\Entity\User $user
	 */
	private function SaveUser(int $thank_you_id, \Claromentis\People\Entity\User $user)
	{
		$db_fields = [
			'int:thanks_id' => $thank_you_id,
			'int:user_id'   => $user->id
		];

		$query = $this->query_factory->GetQueryInsert(self::THANKED_USERS_TABLE, $db_fields);
		$this->db->query($query);
	}

	/**
	 * Given a Thank You's repository ID, deletes the Thank You from the repository.
	 *
	 * @param int $thank_you_id
	 */
	private function DeleteThankYou(int $thank_you_id)
	{
		$query_string = "DELETE FROM " . self::THANK_YOU_TABLE . " WHERE id=int:thank_you_id";

		$this->db->query($query_string, $thank_you_id);
	}

	/**
	 * Given a Thank You's deletes all of a Thank You's Thanked.
	 *
	 * @param int $thank_you_id
	 */
	private function DeleteThankYouThanked(int $thank_you_id)
	{
		$query_string = "DELETE FROM " . self::THANKED_TABLE . " WHERE item_id=int:thank_you_id";

		$this->db->query($query_string, $thank_you_id);
	}

	/**
	 * Delete all of a Thank You's Users.
	 *
	 * @param int $thank_you_id
	 */
	private function DeleteThankYouUsers(int $thank_you_id)
	{
		$query_string = "DELETE FROM " . self::THANKED_USERS_TABLE . " WHERE thanks_id=int:thank_you_id";
		$this->db->query($query_string, $thank_you_id);
	}

	private function QueryJoinThankYouToTagged(QueryBuilder $query)
	{
		$query->AddJoin(self::THANK_YOU_TABLE, self::THANK_YOU_TAGS_TABLE, self::THANK_YOU_TAGS_TABLE, self::THANK_YOU_TABLE . ".id = " . self::THANK_YOU_TAGS_TABLE . ".item_id AND aggregation_id = " . self::AGGREGATION_ID);
	}

	private function QueryJoinThankedUsersToTagged(QueryBuilder $query)
	{
		$query->AddJoin(self::THANKED_USERS_TABLE, self::THANK_YOU_TAGS_TABLE, self::THANK_YOU_TAGS_TABLE, self::THANKED_USERS_TABLE . ".thanks_id = " . self::THANK_YOU_TAGS_TABLE . ".item_id AND aggregation_id = " . self::AGGREGATION_ID);
	}

	/**
	 * @param QueryBuilder $query
	 * @param int[]        $date_range
	 */
	private function QueryAddCreatedBetweenFilter(QueryBuilder $query, array $date_range)
	{
		$date_range = $this->utility->FormatDateRange($date_range);

		$lower_date = $date_range[0] ?? null;
		$upper_date = $date_range[1] ?? null;

		if (!is_int($lower_date))
		{
			throw new InvalidArgumentException("Failed to Add Created Between Filter to Query, Lower Date is not an integer");
		}

		if (!is_int($upper_date))
		{
			throw new InvalidArgumentException("Failed to Add Created Between Filter to Query, Upper Date is not an integer");
		}

		$query->AddWhereAndClause(self::THANK_YOU_TABLE . ".date_created BETWEEN " . $lower_date . " AND " . $upper_date);
	}

	private function QueryAddThankedUserFilter(QueryBuilder $query, array $thanked_user_ids)
	{
		if (count($thanked_user_ids) === 0)
		{
			return;
		}

		$invalid_user_ids = [];
		foreach ($thanked_user_ids as $user_id)
		{
			if (!is_int($user_id))
			{
				$invalid_user_ids[] = $user_id;
			}
		}

		if (!empty($invalid_user_ids))
		{
			throw new InvalidArgumentException("Failed to Add Thanked User Filter to Query, invalid user IDs for thanked user filter: " . implode(', ', $invalid_user_ids));
		}

		$query->AddWhereAndClause(self::THANKED_USERS_TABLE . ".user_id IN in:int:thanked_user_ids", $thanked_user_ids);
	}

	private function QueryAddTagsFilter(QueryBuilder $query, array $tag_ids)
	{
		if (count($tag_ids) === 0)
		{
			return;
		}

		$invalid_tag_ids = [];
		foreach ($tag_ids as $tag_id)
		{
			if (!is_int($tag_id))
			{
				$invalid_tag_ids[] = $tag_id;
			}
		}

		if (!empty($invalid_tag_ids))
		{
			throw new InvalidArgumentException("Failed to Add Tagged Filter to Query, invalid Tag IDs for Tag filter: " . implode(', ', $invalid_tag_ids));
		}

		$query->AddWhereAndClause(self::THANK_YOU_TAGS_TABLE . ".tag_id IN in:int:tag_ids", $tag_ids);
	}

	private function QueryAddExtranetFilter(QueryBuilder $query, array $extranet_ids, bool $allow_absence = false)
	{
		if (count($extranet_ids) === 0)
		{
			return;
		}

		$where             = "(" . self::USER_TABLE . ".ex_area_id IN (";
		$first_extranet_id = true;
		foreach ($extranet_ids as $extranet_id)
		{
			if (!is_int($extranet_id))
			{
				throw new InvalidArgumentException("Failed to Add Extranet Filter to Query, Extranet ID '" . (string) $extranet_id . "' is not an integer");
			}

			$where             .= $first_extranet_id ? $extranet_id : ", " . $extranet_id;
			$first_extranet_id = false;
		}
		$where .= ")";

		if ($allow_absence)
		{
			$where .= " OR " . self::USER_TABLE . ".id IS NULL";
		}

		$where .= ")";

		$query->AddWhereAndClause($where);
	}
}
