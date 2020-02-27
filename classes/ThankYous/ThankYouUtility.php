<?php

namespace Claromentis\ThankYou\ThankYous;

use Claromentis\Core\Acl\PermOClass;
use Claromentis\ThankYou\Exception\OwnerClassNameException;
use InvalidArgumentException;

class ThankYouUtility
{
	/**
	 * Given the an Owner Class' ID, returns it's Name.
	 *
	 * @param int $id
	 * @return string
	 * @throws OwnerClassNameException - If the Name of the oClass could not be determined.
	 */
	public function GetOwnerClassName(int $id): string
	{
		return $this->GetOwnerClassNames([$id])[$id];
	}

	/**
	 * Returns an array of Owner Class Names indexed by their IDs.
	 *
	 * @param int[] $ids
	 * @return string[]
	 * @throws OwnerClassNameException - If the Name of the oClass could not be determined.
	 */
	public function GetOwnerClassNames(array $ids): array
	{
		$names = [];
		foreach ($ids as $id)
		{
			if (!is_int($id))
			{
				throw new InvalidArgumentException("Failed to Get Thanked Object Type's Name From ID, non-integer value given");
			}
			$names[$id] = PermOClass::GetName($id);
			if (!is_string($names[$id]))
			{
				throw new OwnerClassNameException("Failed to Get Thanked Object Type's Name From ID, oClass did not return string");
			}
		}

		return $names;
	}

	/**
	 * @param array $orders
	 * @return string
	 */
	public function BuildOrderString(array $orders): string
	{
		if (count($orders) === 0)
		{
			return '';
		}

		$first = true;
		$query_string = " ORDER BY";
		foreach ($orders as $offset => $order)
		{
			$column    = $order['column'] ?? null;
			$direction = (isset($order['desc']) && $order['desc'] === true) ? 'DESC' : 'ASC';
			if (!isset($column) || !is_string($column))
			{
				throw new InvalidArgumentException("Failed to GetTags, one or more Orders does not have a column");
			} ;
			$query_string .= ($first ? '' : ',') . " " . $column . " " . $direction;
			$first = false;
		}

		return $query_string;
	}

	/**
	 * Create a Thank You's URL (not including the site's name)
	 *
	 * @param int $id
	 * @return string
	 */
	public function GetThankYouUrl(int $id)
	{
		return '/thankyou/thanks/' . $id;
	}
}
