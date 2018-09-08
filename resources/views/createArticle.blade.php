@extends('layouts.formMaster', ['bodyId'=>'createArticle'])

@section('title', 'New User')

@section('main')

<div class="action-icons">
	<a id="goBack" href="<?php echo url('/readArticles') ?>" >
		<i class="fas fa-reply"></i>
		Back
	</a>
</div>
<form id="kbForm" method="post" action="<?php echo url('createArticle');?>">
	@include('partials/tinyMceForm')
</form>




@stop