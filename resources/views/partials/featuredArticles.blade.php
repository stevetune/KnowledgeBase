<h1>FEATURED ARTICLES</h1>
<div>
<!--{{--<div class="featuredArticlesContainer slideshow-container">
    <i class="fas fa-angle-left cycle-prev"></i>

    <div class="cycle-slideshow" 
    	
    	data-cycle-prev=".cycle-prev" 
    	data-cycle-next=".cycle-next" 
    	data-cycle-slides="> .featuredArticle"

		data-cycle-fx=carousel
		data-cycle-timeout=0
		data-cycle-carousel-visible=5
    	data-cycle-carousel-fluid=true
    >
		@foreach($featuredArticles as $article)
			<a class="featuredArticle" href="http://localhost:8888/KnowledgeBase/public/readArticle/{{$article->id}}">
				<h2>{{$article->Title}}</h2>
				<iframe src="http://localhost:8888/KnowledgeBase/public/fullPageArticle/{{$article->id}}"></iframe>
			</a>
		@endforeach
	</div>
	<i class="fas fa-angle-right cycle-next"></i>
</div>


<script>
  	//$.fn.cycle.defaults.autoSelector = ".cycle-slideshow";
</script>--}}-->
</div>
<div class="featuredArticlesGrid">
	@if(count($featuredArticles) === 0)
		<h2>No results</h2>
	@else
		@foreach($featuredArticles as $article)
			<div class="featuredArticle">
				<iframe src="fullPageArticle/{{$article->id}}"></iframe>
				<h2>{{$article->title}}</h2>
				<a href="readArticle/{{$article->id}}" class="clickableLink"></a>
			</div>
		@endforeach
	@endif

</div>