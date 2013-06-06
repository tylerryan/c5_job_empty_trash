<?php
defined('C5_EXECUTE') or die("Access Denied.");

class EmptyTrash extends Job {

	public function getJobName() {
		return t('Empty Trash');
	}

	public function getJobDescription() {
		return t('Empty trash 10 pages at a time');
	}

	public function run() {

		$count = 0;

		$pl = new PageList;
		$pl->filterByParentID(Page::getByPath('/!trash')->getCollectionID());
		$pl->ignorePermissions(true);
		$pl->displayUnapprovedPages();
		$pl->includeSystemPages();
		$pl->includeInactivePages();

		$pages = $pl->get();
		$total = $pl->getTotal();

		while ($page = array_pop($pages)) {
			$page->delete();
			$count++;
		}

		$remaining = $total - $count;
		if ($remaining < 0) $remaining = 0;

		return t('%s pages emptied from trash. %s remain.', $count, $remaining);
	}

}
