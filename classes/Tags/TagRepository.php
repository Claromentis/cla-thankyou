<?php

namespace Claromentis\ThankYou\Tags;

use Claromentis\Core\DAL\Interfaces\DbInterface;
use Claromentis\Core\DAL\Query;
use Claromentis\Core\DAL\QueryFactory;
use Claromentis\Core\DAL\ResultInterface;
use Claromentis\People\InvalidFieldIsNotSingle;
use Claromentis\People\UsersListProvider;
use Claromentis\ThankYou\Tags\Exceptions\TagCreatedByException;
use Claromentis\ThankYou\Tags\Exceptions\TagCreatedDateException;
use Claromentis\ThankYou\Tags\Exceptions\TagDuplicateNameException;
use Claromentis\ThankYou\Tags\Exceptions\TagInvalidNameException;
use Claromentis\ThankYou\Tags\Exceptions\TagModifiedByException;
use Claromentis\ThankYou\Tags\Exceptions\TagModifiedDateException;
use Date;
use DateTimeZone;
use InvalidArgumentException;
use LogicException;
use Psr\Log\LoggerInterface;
use User;

class TagRepository
{
	const TABLE_NAME = 'thankyou_tag';

	private $db;

	protected $log;

	private $query_factory;

	private $tag_factory;

	public function __construct(DbInterface $db, QueryFactory $query_factory, LoggerInterface $log, TagFactory $tag_factory)
	{
		$this->db            = $db;
		$this->log           = $log;
		$this->query_factory = $query_factory;
		$this->tag_factory   = $tag_factory;
	}

	/**
	 * @param int         $limit
	 * @param int         $offset
	 * @param string|null $name
	 * @param array|null  $orders
	 * @return Tag[]
	 */
	public function GetTags(int $limit, int $offset, ?string $name = null, ?array $orders = null): array
	{
		$query_string = "SELECT * FROM " . self::TABLE_NAME;
		if (isset($name))
		{
			$query_string .= " WHERE name LIKE \"" . $name . "%\"";
		}
		if (isset($orders) && count($orders) > 0)
		{
			$query_string .= " ORDER BY";
			foreach ($orders as $offset => $order)
			{
				$column    = $order['column'] ?? null;
				$direction = (isset($order['desc']) && $order['desc'] === true) ? 'DESC' : 'ASC';
				if (!isset($column) || !is_string($column))
				{
					throw new InvalidArgumentException("Failed to GetTags, one or more Orders does not have a column");
				}
				$query_string .= " " . $column . " " . $direction;
			}
		}

		$query = new Query($query_string);
		$query->setLimit($limit, $offset);
		$results = $this->db->query($query);

		return $this->GetTagsFromDbQuery($results);
	}

	/**
	 * @return int
	 */
	public function GetTotalTags(): int
	{
		return (int) $this->db->query_row("SELECT COUNT(1) FROM " . self::TABLE_NAME)[0];
	}

	/**
	 * @param Tag $tag
	 * @throws TagDuplicateNameException - If the Tag's Name is not unique to the Repository.
	 * @throws TagModifiedByException - If the Tag's Modified By has not been defined.
	 * @throws TagModifiedDateException - If the Tag's Modified Date has not been defined.
	 * @throws TagCreatedByException - If the Tag's Created By has not been defined.
	 * @throws TagCreatedDateException - If the Tag's Created Date has not been defined.
	 */
	public function Save(Tag $tag)
	{
		$name = $tag->GetName();

		$id = $tag->GetId();

		if (!$this->IsTagNameUnique($name, $id))
		{
			throw new TagDuplicateNameException("Failed to save Tag, Tag's Name is not unique");
		}

		$db_fields = ['str(255):name' => $name, 'int:active' => (int) $tag->GetActive()];

		$created_by = $tag->GetCreatedBy();
		if (isset($created_by))
		{
			$db_fields['int:created_by'] = $created_by->GetId();
		}

		$created_date = $tag->GetCreatedDate();
		if (isset($created_date))
		{
			$db_fields['int:created_date'] = $created_date->format('YmdHis');
		}

		$modified_by = $tag->GetModifiedBy();
		if (!isset($modified_by))
		{
			throw new TagModifiedByException("Failed to Save Tag, Modified By undefined");
		}
		$db_fields['int:modified_by'] = $modified_by->GetId();

		$modified_date = $tag->GetModifiedDate();
		if (!isset($modified_date))
		{
			throw new TagModifiedDateException("Failed to Save Tag, Modified Date undefined");
		}
		$db_fields['int:modified_date'] = $modified_date->format('YmdHis');

		$metadata  = null;
		$bg_colour = $tag->GetBackgroundColour();
		if (isset($bg_colour))
		{
			$metadata = json_encode(['bg_colour' => $bg_colour]);
		}

		$db_fields['clob:metadata'] = $metadata;

		if (!isset($id))
		{
			if (!isset($created_by))
			{
				throw new TagCreatedByException("Failed to Save new Tag, Created By undefined");
			}

			if (!isset($created_date))
			{
				throw new TagCreatedDateException("Failed to Save new Tag, Created Date undefined");
			}

			$query = $this->query_factory->GetQueryInsert(self::TABLE_NAME, $db_fields);
			$this->db->query($query);
			$tag->SetId($this->db->insertId());
		} else
		{
			$query = $this->query_factory->GetQueryUpdate(self::TABLE_NAME, "id=int:id", $db_fields);
			$query->Bind('id', $id);
			$this->db->query($query);
		}
	}

	/**
	 * @param int $id
	 */
	public function Delete(int $id)
	{
		$this->db->query("DELETE FROM " . self::TABLE_NAME . " WHERE id=int:id", $id);
	}

	public function IsTagNameUnique(string $name, ?int $id): bool
	{
		if (!isset($id))
		{
			return !(bool) $this->db->query_row("SELECT COUNT(1) FROM " . self::TABLE_NAME . " WHERE name=str:name", $name)[0];
		} else
		{
			return !(bool) $this->db->query_row("SELECT COUNT(1) FROM " . self::TABLE_NAME . " WHERE name=str:name AND id!=int:id", $name, $id)[0];
		}
	}

	/**
	 * @param int[] $ids
	 * @return Tag[]
	 */
	public function Load(array $ids): array
	{
		foreach ($ids as $id)
		{
			if (!is_int($id))
			{
				throw new InvalidArgumentException("Failed to Load Tags from Database, Tag IDs must be integers");
			}
		}

		$query   = "SELECT * FROM " . self::TABLE_NAME . " WHERE id IN in:int:ids";
		$results = $this->db->query($query, $ids);

		return $this->GetTagsFromDbQuery($results);
	}

	/**
	 * @param ResultInterface $results
	 * @return Tag[]
	 */
	private function GetTagsFromDbQuery(ResultInterface $results): array
	{
		$rows  = [];
		$users = [];
		while ($row = $results->fetchArray())
		{
			$rows[$row['id']] = $row;

			$users[(int) $row['created_by']]  = null;
			$users[(int) $row['modified_by']] = null;
		}

		$users = $this->GetUsers(array_keys($users));

		$tags = [];
		foreach ($rows as $id => $row)
		{
			if (!isset($row['name']) || !is_string($row['name']))
			{
				$this->log->error("Failed to Get Tags From Db Query, one or more Tags could not be constructed due to invalid database data");
				continue;
			}
			try
			{
				$tag = $this->tag_factory->Create($row['name'], $row['active']);
			} catch (TagInvalidNameException $exception)
			{
				$this->log->error("Failed to Get Tags From Db Query, one or more Tags could not be constructed due to invalid database data");
				continue;
			}
			$tag->SetId($id);
			$tag->SetCreatedBy($users[(int) $row['created_by']] ?? null);
			$tag->SetCreatedDate(new Date($row['created_date'], new DateTimeZone('UTC')));
			$tag->SetModifiedBy($users[(int) $row['modified_by']] ?? null);
			$tag->SetModifiedDate(new Date($row['created_date'], new DateTimeZone('UTC')));

			$metadata = json_decode($row['metadata'], true);
			if (isset($metadata['bg_colour']) && is_string($metadata['bg_colour']))
			{
				$tag->SetBackgroundColour($metadata['bg_colour']);
			}

			$tags[$id] = $tag;
		}

		return $tags;
	}

	/**
	 * Returns an array of Users indexed by their ID.
	 *
	 * @param array $user_ids
	 * @return User[]
	 */
	private function GetUsers(array $user_ids)
		//TODO: Get rid of this method when possible, this class should be able to use something else to mass build Users really.
	{
		$users_list_provider = new UsersListProvider();
		$users_list_provider->SetFilterProtectExtranets(false);
		$users_list_provider->SetFilterIds($user_ids);
		try
		{
			return $users_list_provider->GetListObjects();
		} catch (InvalidFieldIsNotSingle $invalid_field_is_not_single)
		{
			throw new LogicException("Unexpected InvalidFieldIsNotSingle Exception throw by UserListProvider, GetListObjects", null, $invalid_field_is_not_single);
		}
	}

	//TODO: Add method GetTagFromDbRow for direct calls. Currently only works as an extension of the ThankTagRepository.
}
