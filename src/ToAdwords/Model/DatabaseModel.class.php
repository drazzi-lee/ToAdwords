<?php

/**
 * DatabaseModel.class.php
 *
 * Defines class DatabaseModel, which is used to do all operations on database.
 *
 * @author Li Pengfei
 * @email drazzi.lee@gmail.com
 * @version 1.0
 */

/**
 * Database Model, A Model Packaging database operations. 
 *
 * Provides a simple layer to operate database for actions from Adwords Adapter.
 *
 * It should do:
 *	1. checking whether an object is exists.
 *	2. insert all kinds of object.
 *	3. checking whether the parent object is exists.
 *	4. update all kinds of object.
 *	5. update parent object id.
 *	6. update current object id and synchronous status. 
 */
class DatabaseModel{
	private $dbh = null;

	public function __construct{

	}

	public function insertOne(){

	}

	public function updateOne(){

	}

	public function getOne(){

	}

	public function isExists(Object $object){

	}

	public function isParentExists(Object $object){

	}

	public function updateParentId(){

	}

	public function updateIdAndSyncStatus(){

	}
}
