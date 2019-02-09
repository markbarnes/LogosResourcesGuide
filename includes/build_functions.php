<?php
	function add_author_to_resource ($resource_id, $name) {
		global $db;
		$author_id = get_author_id ($name);
		$db->query ('INSERT IGNORE INTO resources_authors (resource_id, author_id) VALUES (:resource_id, :author_id)', array (':resource_id' => $resource_id, ':author_id' => $author_id));
	}
	
	function add_publisher_to_resource ($resource_id, $publisher) {
		global $db;
		$publisher_id = get_publisher_id ($publisher);
		$db->query ('INSERT INTO resources_publishers (resource_id, publisher_id) VALUES (:resource_id, :publisher_id)', array (':resource_id' => $resource_id, ':publisher_id' => $publisher_id));
	}
	
	function add_subject_to_resource ($resource_id, $subject) {
		global $db;
		$subject_id = get_subject_id ($subject);
		$db->query ('INSERT IGNORE INTO resources_subjects (resource_id, subject_id) VALUES (:resource_id, :subject_id)', array (':resource_id' => $resource_id, ':subject_id' => $subject_id));
	}
	
	function add_language_to_resource ($resource_id, $language_id) {
		global $db;
		$db->query ('INSERT IGNORE INTO resources_languages (resource_id, language_id) VALUES (:resource_id, :language_id)', array (':resource_id' => $resource_id, ':language_id' => $language_id));
	}
	
	function add_platform_to_resource ($resource_id, $platform) {
		global $db;
		$db->query ('INSERT IGNORE INTO resources_platforms (resource_id, platform_id) VALUES (:resource_id, :platform)', array (':resource_id' => $resource_id, ':platform' => $platform));
	}
	
	function add_owner_to_resource ($resource_id, $owner) {
		global $db;
		$db->query ('INSERT IGNORE INTO resources_owners (resource_id, owner) VALUES (:resource_id, :owner)', array (':resource_id' => $resource_id, ':owner' => $owner));
	}
	
	function add_trait_to_resource ($resource_id, $trait) {
		global $db;
		$trait_id = get_trait_id ($trait);
		$db->query ('INSERT IGNORE INTO resources_traits (resource_id, trait_id) VALUES (:resource_id, :trait_id)', array (':resource_id' => $resource_id, ':trait_id' => $trait_id));
	}
	
?>
