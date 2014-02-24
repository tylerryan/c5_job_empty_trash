<?php
defined('C5_EXECUTE') or die("Access Denied.");

class EmptyTrash extends QueueableJob {

	public $jSupportsQueue = true;

	
	public function getJobName() {
		return t('Empty Trash');
	}

	public function getJobDescription() {
		return t('Empty trash 10 pages at a time');
	}

	//public function run() {}
	/*
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
	*/
	
	public function start(Zend_Queue $q) {
		$pl = new PageList;
		$pl->filterByParentID(Page::getByPath('/!trash')->getCollectionID());
		$pl->ignorePermissions(true);
		$pl->displayUnapprovedPages();
		$pl->includeSystemPages();
		$pl->includeInactivePages();
		$pages = $pl->get();
		$total = $pl->getTotal();

		foreach($pages as $page) {
			$deletePages= array();
			$includeThisPage = true;
			if ($page->getCollectionPath() == TRASH_PAGE_PATH) {
				$includeThisPage = false;
			}
			$deletePages = $page->populateRecursivePages($deletePages, array('cID' => $page->getCollectionID()), $page->getCollectionParentID(), 0, $includeThisPage);
			
			// now, since this is deletion, we want to order the pages by level, which
			// should get us no funny business if the queue dies.
			usort($deletePages, array('Page', 'queueForDeletionSort'));
			foreach($deletePages as $d) {
				$q->send(Loader::helper('json')->encode(array('cID'=>$d['cID'])));
			}
		}
	}
	
	public function processQueueItem(Zend_Queue_Message $msg) {
		$args = Loader::helper('json')->decode($msg->body);
		
		$cID = $args->cID;
		if(is_numeric($cID) && $cID) {
			$page = Page::getByID($cID);
			if($page instanceof Page && $page->getCollectionID()) {
				$page->delete();
			}
		}
	}
	
	public function finish(Zend_Queue $q) {
		return t('The trash has been emptied.');
	}
	
}
