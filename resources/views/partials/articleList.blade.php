
<h1>{{ !empty($srchTrm)?'SEARCH RESULTS for "'.$srchTrm.'"':'ALL ARTICLES' }}</h1>

@include('partials/listHeader', ['type' => 'articles'])


<div class="collatedGrid {{Auth::check()?'loggedIn':''}}">

@if(count($articles) === 0)
	<h2>No results</h2>
@else
	@php($i = 0)

	@foreach($articles as $article)
		
		@php($row = $i % 2 == 0?'row odd':'row')

		<div class="{{$row}} titleRow">
			<a href="{{ url('/readArticle/'.$article->id) }}">
				<i class="far fa-file"></i> {{$article->title}}
			</a>
		</div>
		<div class="{{$row}} cats">
			<div class="categoryList">
				<i class="fas fa-times-circle"></i>
				<ul>
					@foreach(explode(",", $article->categoryNames) as $catName)						 
						<li>{{$catName}}</li>
					@endforeach
				</ul>
			</div>
			{!! count(explode(",", $article->categoryNames)) > 1?"<a href='#' class='seeMore'>View <i class='far fa-eye'></i></a>":"" !!}
		</div>
		<div class="{{$row}} featured">
			{{ $article->featured == 1?"Yes":"No" }}
		</div>
		<div class="{{$row}}">
			{{$article->dateCreated}}
		</div>
		@if (Auth::check())
			<div class="{{$row}} actionItems">
				@include('partials/actionItems', ['items' => [['deleteArticle', 'Delete'], ['updateArticle', 'Update']], 'objId' => $article->id])
			</div>
		@endif
		@php($i++)
	@endforeach
@endif


</div>


@include('partials/javaScriptSort')

<script>
	$('.collatedGrid').on('click', '.seeMore', function(e){
		e.preventDefault();
		$(this).siblings('.categoryList').addClass('show');
	});

	$('.collatedGrid').on('click', '.fas.fa-times-circle', function(){
		$(this).closest('.categoryList').removeClass('show');
	});
</script>
