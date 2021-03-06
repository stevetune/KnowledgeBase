<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use App\Folders;
use App\Traits\RelateFolders;
use App\Traits\SortResults;

class FolderController extends Controller
{

	use RelateFolders;
 	use SortResults;

	public function createFolder(){

 		DB::table('Folder')->insert([
 			'Name' 			=> $_POST['name'], 
 			'dateCreated' 	=> date('Y-m-d H:i:s'),
 			'parentId'		=> !empty($_POST['parentId'])?$_POST['parentId']:null,
 			'createdBy'		=> Auth::user()->id
 		]);

 		return self::readFolders();
 	}

 	public function readFolders($param = null, $dir = null){
 		if (Auth::user()){

 			$folders = $this->getFolders(false);

	        //deep clone $folders
 			$folderTree = clone $folders;
 			foreach($folderTree as $key => $value){
 				$folderTree[$key] = clone $value;
 			}
	        $this->__relateFolders($folderTree, false);
	        
	        $foldersScalar = $folders;
	        $this->__relateFolders($foldersScalar, true);
	 		$foldersScalar = $this->sortResults($foldersScalar, $param, $dir);
			//dd($foldersScalar);

 			return view('readFolders', ['foldersScalar' => $foldersScalar, 'folderTree' => $folderTree] );
 		}else{
 			return view('authLandingPage');
 		}
 	}

 	public function updateFolder($folderID){

 		DB::table('Folder')
	 		->where('id', $folderID)
	 		->update(array(
	 			'Name' 			=> $_POST['name'],
	 			'lastUpdated' 	=> date('Y-m-d H:i:s'),
	 			'lastUpdatedBy'	=> Auth::user()->id,
	 			'parentId'		=> !empty($_POST['parentId'])?$_POST['parentId']:null
 		));

 		return self::readFolders();
 	}

 	public function deleteFolder($folderId){

 		Folders::where('id', $folderId)->delete();

 		return self::readFolders();
 	}

 	
}

