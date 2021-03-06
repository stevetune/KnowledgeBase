<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Article;
use App\Category;
use App\Article_Category;
use App\Folder;
use App\Traits\RelateFolders;
use App\Traits\SortResults;

class ArticleController extends Controller
{

	use RelateFolders;
	use SortResults;

	protected $model;

 	public function createArticle(Request $request){
 		if(empty($_POST)){
	 		$categories = DB::table('Category')->where('deleted', '=', '0')->get();

	 		$folders = $this->getFolders();
	 		$this->__relateFolders($folders);

	 		return view('createArticle', ['categories' => $categories, 'folderTree' => $folders]);
	 	}else{

	 		$validatedData = $request->validate([
		        'title' => 'required|unique:Articles',
		        'content' => 'required',
		    ]);

			$articleId = DB::table('Article')->insertGetId([
				'Title' 			=> $_POST['title'], 
				'featured'			=> !empty($_POST['featured'])?$_POST['featured']:0,
	 			'folderId'			=> !empty($_POST['parentId'])?$_POST['parentId']:null,
				'Content' 			=> $_POST['content'], 
				'textOnlyContent' 	=> $_POST['textOnlyContent'],  
				'dateCreated'		=> date('Y-m-d: H:i:s'),
				'createdBy' 		=> Auth::user()->id
			]);

			if(isset($_POST['categoryIds'])){
				foreach($_POST['categoryIds'] as $categoryId){
					DB::table('Article_Category')->insert([
						'articleId' => $articleId, 
						'categoryId' => $categoryId			
					]);			
				}
			}

			return self::homePage();
		}
 	}   

 	public function sortArticles($param, $dir, $srchTrm = null){

	 	$articles = self::__getAllArticles($param, $dir, $srchTrm);

	 	return view('partials/articleList')->with(['articles' => $articles]);

 	}

 	public function __getAllArticles($param = null, $dir = null, $srchTrm = null){

 		$articles = DB::table('Article as a')
	         	->leftJoin('Article_Category as a_c', 'a.ID', '=', 'a_c.articleId')
	            ->leftJoin('Category as c', function($leftJoin){
	            	$leftJoin->on('c.ID', '=', 'a_c.categoryId');
	            	$leftJoin->where('c.deleted', 0);
	            })
	            ->select('a.*', 
	            			DB::raw('group_concat(c.Name) as categoryNames'), 
	            			DB::raw('group_concat(c.ID) as categoryIds')
	            	)
	            ->where('a.deleted', 0)
	            ->when($srchTrm !== null, function($query) use($srchTrm){
	            	$query->where(function($query2) use($srchTrm){
	            		$query2->where('a.Title', 'like', '%'.$srchTrm.'%');
                		$query2->orWhere('a.textOnlyContent', 'like', '%'.$srchTrm.'%');
	            	});
	            })
	            ->orderBy('dateCreated', 'DESC')
	           	->groupBy('a.ID')
	            ->get();

	    $articles = $this->SortResults($articles, $param, $dir);

	    /*foreach($articles as $key => $article){
	    	$catNamesArr = !empty($article->categoryNames)?explode(",", $article->categoryNames):[];
	    	//$catIdsArr = !empty($article->categoryIds)?explode(",", $article->categoryIds):[];

	    	$catArr = [];
	    	for($i=0; $i<count($catNamesArr); $i++){
	    		$catArr[] = $catNamesArr[$i];
	    	}

	    	$article->categories = $catArr;
	    	unset($articles[$key]->categoryNames);
	    	unset($articles[$key]->categoryIds);
	    }*/

	    return $articles;
 	}

 	public function homePage(){

 		$articles = self::__getAllArticles();
 	
	    $featuredArticles = DB::table('Article as a')
	         	->leftJoin('Article_Category as a_c', 'a.ID', '=', 'a_c.articleId')
	            ->leftJoin('Category as c', 'c.ID', '=', 'a_c.categoryId')
	            ->select('a.*', 
	            	DB::raw('group_concat(c.Name) as categoryNames'), 
	            	DB::raw('group_concat(c.ID) as categoryIds'))
	            ->groupBy('a.ID')
	            ->where('a.deleted', '=', false)
	            ->where('a.featured', '=', 1)
	            ->orderBy('dateCreated', 'DESC')
	            ->get();
	    $featuredArticles->sortBy('dateCreated');

		return view('homePage')->with(['articles' => $articles, 'featuredArticles' => $featuredArticles]);
 	}

 	public function searchArticles(){

 		$articles = self::__getAllArticles(null, null, $_GET['search']);

 		return view('homePage')->with(['articles' => $articles, 'srchTrm' => $_GET['search']]);
 	}

 
	public function readArticleTree($curFolderId = null){

		$folders = $this->getFolders();
	    $this->__relateFolders($folders, false);

		return view('readArticlesWrapper')->with(['folders' => $folders, 'curFolderId' => $curFolderId, 'type' => 'tree']);
 	} 	

 	public function articleList(){

 		$articles = self::__getAllArticles();

		return view('readArticlesWrapper')->with(['articles' => $articles, 'type' => 'list']);
 	}

 	public function readArticleGUI($curFolderId = null){
 	
		$results = self::__getArticleGUI($curFolderId);

		$pathArr = self::__getFolderPath($curFolderId);

		return view('readArticlesWrapper')->with(['results' => $results, 'curFolderId' => $curFolderId, 'pathArr' => $pathArr, 'type' => 'GUI']);
 	}

 	public function __getFolderPath($curFolderId){
 		
 		$pathArr = array();

 		while($curFolderId !== null){
	 		$folder = DB::table('Folder as f')
	 				->select('id', 'parentId', 'name')
	 				->where('id', '=', $curFolderId)
	 				->first();
	 		
	 		$curFolderId = $folder->parentId;

	 		$pathArr[] = ['name' => $folder->name, 'id' => $folder->id];
	 	}

	 	return array_reverse($pathArr);

 	}

 	public function __getArticleGUI($parentFolderId = null){

 		$articles = DB::table('Article as a')
	            ->select('a.Title', 'a.ID')
	            ->where('a.folderId', '=', $parentFolderId)
	            ->where('a.deleted', '=', 0)
	            ->orderBy('a.ID')
	           	->get();
	           	
	    $folders = DB::table('Folder as f')
	            ->where('f.parentId', '=', $parentFolderId)
	           	->orderBy('f.id', 'DESC')
	           	->get();

	    return ['articles' => $articles, 'folders' => $folders];
 	}

 	public function __getArticle($articleId){

 		$result = DB::table('Article as a')
	         	->leftJoin('Article_Category as a_c', 'a.ID', '=', 'a_c.articleId')
	            ->leftJoin('Category as c', 'a_c.categoryId', '=','c.ID')
				->leftJoin('Folder as f', 'f.id', '=', 'a.folderId')
	            ->select('a.*', 'f.name as parentFolder',
	            	DB::raw('group_concat(c.Name) as categoryNames'), 
	            	DB::raw('group_concat(c.ID) as categoryIds'))
	            ->where('a.ID', '=', $articleId)
	           	->groupBy('a.ID')
	            ->first();

	    return $result;
 	}

 	public function readArticle($articleId){

 		$article = self::__getArticle($articleId);

	    return view('readArticle')->with(['article' => $article]);
 	}

 	public function updateArticle(Request $request, $articleId){
 		if(empty($_POST)){

	 		$article = self::__getArticle($articleId);
	        $categories = DB::table('Category')->where('deleted', 0)->get();
	        
	        $folders = $this->getFolders();
	        $curFolder = $this->getFolderById($folders, $article->folderId);
	        $this->__relateFolders($folders);


			return view('updateArticle')->with(['article' => $article, 'categories' => $categories, 'folderTree' => $folders,'curFolder' => $curFolder]);

		}else{

			$validatedData = $request->validate([
		        'title' => 'required|unique:Articles,ID,$articleId',
		        'content' => 'required',
		    ]);

	 		DB::table('Article')->where('id', $articleId)->update([
	 			'Title' 			=> $_POST['title'], 
	 			'featured'			=> !empty($_POST['featured'])?$_POST['featured']:0,
	 			'folderId'			=> !empty($_POST['parentId'])?$_POST['parentId']:null,
	 			'Content' 			=> $_POST['content'], 
	 			'textOnlyContent' 	=> $_POST['textOnlyContent'], 
	 			'lastUpdated'		=> date('Y-m-d'),
	 			'lastUpdatedBy' 	=> Auth::user()->id
	 		]);

	 		Articles_Categories::where('articleId', $articleId)->delete();
	 		if(isset($_POST['categoryIds'])){
	 			foreach($_POST['categoryIds'] as $categoryId){
		 			Articles_Categories::insert(
		 				['articleId' => $articleId, 'categoryId' => $categoryId]
					);
		 		}
	 		}

	 		return self::homePage();
		}
 	}

 	public function deleteArticle($articleId){

 		DB::table('Article')->where('id', $articleId)->update(array(
 			'lastUpdatedBy' 	=> Auth::user()->id,
 			'deleted' => true 
 		));

		$articles = Article::all();
		
		return self::homePage();
 	}

 	public function fullPageArticle($articleId){
 		$article = DB::table('Article')->where('ID', $articleId)->first();

 		return view('fullPageArticle')->with(array('article' => $article));
 	}

 	public function importGDoc(){

 		if(Auth::check()){
	 		if(!empty($_POST) && !empty($_POST['fileId'])){

	 			$fileId = $_POST['fileId']; 
	 			$tmpFolder = "tmpZipFolder";
	 			$tmpZipFile = "zipFile.zip";
	 			$tmpFilePath = $tmpFolder."/".$tmpZipFile;

	 			//self::__deleteFolderContents($tmpFolder);

	 			if(!file_exists($tmpFolder)){
			  		mkdir($tmpFolder, 0755, true);
			  	}
			  	
	 			if(self::__curlDownloadZipFile($fileId, $tmpFilePath)){
	 			
	 				$zip = new \ZipArchive;
	 				$res = $zip->open($tmpFilePath);
		 			if ($res === TRUE) {

		 				$zip->extractTo($tmpFolder);
		 				$hasImages = $zip->numFiles > 1?true:false;
		 				$zip->close();

		 				//get article Title
		 				$articleTitle = self::__curlGetArticleTitle($fileId);

		 				$articleId = DB::table('Article')->insertGetId([
							'Title' => $articleTitle,
							'dateCreated'		=> date('Y-m-d: H:i:s'),
							'createdBy' 		=> Auth::user()->id
						]);

		 				try{
			 				//copy images to folder
			 				if($hasImages){
				 				$tmpImgPath = $tmpFolder."/images";
							  	$newImgPath = "public/photos/gdoc_imports/".$articleId."/images/";
							  	if(!file_exists($newImgPath)){
							  		mkdir($newImgPath, 0755, true);
							  	}
							    foreach(scandir($tmpImgPath) as $entry){
							    	if(strpos($entry, ".") > 1){
								    	copy($tmpImgPath."/".$entry, $newImgPath.'/'.$entry);
								    }
							    }
							}

							//load HTML into DOMDocument object
						    foreach(scandir($tmpFolder) as $file){
						    	if(!is_dir($file) && strpos($file, ".html")){
						    		$filename = $file;
						    	}
						    }
						    $html = file_get_contents($tmpFolder."/".$filename);
						    $doc = new \DOMDocument();
						    $doc->loadHTML($html);
						    //update img src attributes
						    foreach ($doc->getElementsByTagName('img') as $imgTag) {
						    	$arr = explode("/", $imgTag->getAttribute("src"));
							    $imgTag->setAttribute('src', $newImgPath."".end($arr));
							}
							$css = $doc->getElementsByTagName('head')[0]->textContent;
							//extract tinyMCE compatible html content
							$body = $doc->getElementsByTagName('body')[0];
							$textOnlyContent = $body->textContent;
							$regex = '#<\s*?body\b[^>]*>(.*?)</body\b[^>]*>#s';
							preg_match($regex, $doc->saveHTML(), $matches);
							$content = $matches[1];

							//dd([$textOnlyContent, $content]);

							DB::table('Article')->where('id', $articleId)
	            			->update([
	            				'content'			=> $content,
								'textOnlyContent'	=> $textOnlyContent,
								'styleContent'		=> $css
	            			]);
	            		}
	            		catch(\Exception $e){
	            			Articles::destroy($articleId);
	            			return $e->getMessage();
	            		}
	            		finally{
	            			self::__deleteFolderContents($tmpFolder);
	            		}
						
					    return self::homePage();

					} else {
						$zip->close();
					    echo 'upload failed, code: '.$res;
					}
					

	 			}else{
	 				die('didnt work');
	 			}

	 		}else{
	 			return view('uploadZipFile');
	 		}
	 	}else{
	 		return view("auth/login");
	 	}
 	}

 	public function __curlDownloadZipFile($fileId, $filePath){

		$url = "https://docs.google.com/feeds/download/documents/export/Export?id=".$fileId."&exportFormat=zip&key=".env('GDRIVE_API_KEY');

		$ch = curl_init($url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/zip'));
     	$raw_file_data = curl_exec($ch);

		if(curl_errno($ch)){
			echo 'error:' . curl_error($ch);
		}
     	curl_close($ch);

     	file_put_contents($filePath, $raw_file_data);
     	
     	return (filesize($filePath) > 0)? true : false;
 	}

 	public function __deleteFolderContents($folder){
 		$files = glob($folder."/*");
		foreach($files as $file){ 
			if(is_file($file)){
				unlink($file);
			}
		}
 	}

 	public function __curlGetArticleTitle($fileId){

 		$url = "https://www.googleapis.com/drive/v2/files/".$fileId."?key=".env('GDRIVE_API_KEY');
		$ch = curl_init($url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
     	$response = json_decode(curl_exec($ch));
     	
		if(curl_errno($ch)){
			echo 'error:' . curl_error($ch);
		}
     	curl_close($ch);

     	return $response->title;
 	}
}

