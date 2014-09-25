<?php

// Disallow direct access to this file for security reasons
if(!defined("MYBBLOG_LOADED"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure MYBBLOG_LOADED is defined.");
}

class Article extends MyBBlogClass
{
	static protected $table = "mybblog_articles";
	static protected $timestamps = true;
	private $comment_cache = array();
	private $tags_cache = array();

	public function validate($hard=true)
	{
		global $lang;

		if(empty($this->data['title']))
			$this->errors[] = $lang->mybblog_article_no_title;

		if(empty($this->data['content']))
		    $this->errors[] = $lang->mybblog_article_no_content;

		if(!empty($this->errors))
			return false;

		return true;
	}

	public function saveWithChilds()
	{
		// First: save us to get our ID
		if(!$this->save())
		    return false;

		// Next: comments
		foreach($this->comment_cache as $comment)
		{
			// Make sure the connection is correct
			$comment->data['aid'] = $this->data['id'];
			if(!$comment->save())
			    return false;
		}

		// Last: tags
		foreach($this->tags_cache as $tag)
		{
			// Make sure the connection is correct
			$tag->data['aid'] = $this->data['id'];
			if(!$tag->save())
			    return false;
		}

		// Still here? Lucky guy
		return true;
	}

	// Functions to interact with our comments
	public function hasComments()
	{
		return ($this->numberComments() > 0);
	}

	public function numberComments()
	{
		if(empty($this->comment_cache))
		    $this->getComments();

		return count($this->comment_cache);
	}

	public function getComments()
	{
		if(empty($this->comment_cache))
			$this->comment_cache = Comment::getByArticle($this->data['id']);

	    return $this->comment_cache;
	}

	public function createComment($data)
	{
		if(!is_array($data))
		    $data = array("content" => $data);

		$data['aid'] = $this->data['id'];
		$comment = Comment::create($data);
		$this->comment_cache[] = $comment; // Don't save the id! Would cause issues with multiple new tags
		return $comment;
	}

	// Functions to interact with our tags
	public function hasTags()
	{
		return ($this->numberTags() > 0);
	}

	public function numberTags()
	{
		if(empty($this->tags_cache))
		    $this->getTags();

		return count($this->tags_cache);
	}

	public function getTags()
	{
		if(empty($this->tags_cache))
			$this->tags_cache = Tag::getByArticle($this->data['id']);

	    return $this->tags_cache;
	}

	public function createTag($data)
	{
		if(!is_array($data))
		    $data = array("tag" => $data);

		$data['aid'] = $this->data['id'];
		$tag = Tag::create($data);
		$this->tags_cache[] = $tag; // Don't save the id! Would cause issues with multiple new tags
		return $tag;
	}
}