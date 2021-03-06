<?php

class BookController extends \BaseController {


	/**
	*
	*/
	public function __construct() {

		# Make sure BaseController construct gets called
		parent::__construct();

		$this->beforeFilter('auth', array('except' => ['getIndex','getDigest']));

	}


	/**
	* Used as an example for something you might want to set up a cron job for
	*/
	public function getDigest() {

		$books = Book::getBooksAddedInTheLast24Hours();

		$users = User::all();

		$recipients = Book::sendDigests($users,$books);

		$results = 'Book digest sent to: '.$recipients;

		Log::info($results);

		return $results;

	}


	/**
	* Process the searchform
	* @return View
	*/
	public function getSearch() {

		return View::make('book_search');

	}


	/**
	* Display all books
	* @return View
	*/
	public function getIndex() {

		# Format and Query are passed as Query Strings
		$format = Input::get('format', 'html');

		$query  = Input::get('query');

		$books = Book::search($query);

		# Decide on output method...
		# Default - HTML
		if($format == 'html') {
			return View::make('book_index')
				->with('books', $books)
				->with('query', $query);
		}
		# JSON
		elseif($format == 'json') {
			return Response::json($books);
		}
		# PDF (Coming soon)
		elseif($format == 'pdf') {
			return "This is the pdf (Coming soon).";
		}


	}


	/**
	* Show the "Add a book form"
	* @return View
	*/
	public function getCreate() {

		$authors = Author::getIdNamePair();

		$tags = Tag::getIdNamePair();

    	return View::make('book_add')
    		->with('authors',$authors)
    		->with('tags',$tags);

	}


	/**
	* Process the "Add a book form"
	* @return Redirect
	*/
	public function postCreate() {

		# Instantiate the book model
		$book = new Book();

		$book->fill(Input::except('tags'));

		# Note this save happens before we enter any tags (next step)
		$book->save();

		foreach(Input::get('tags') as $tag) {

			# This enters a new row in the book_tag table
			$book->tags()->save(Tag::find($tag));

		}

		return Redirect::action('BookController@getIndex')->with('flash_message','Your book has been added.');

	}


	/**
	* Show the "Edit a book form"
	* @return View
	*/
	public function getEdit($id) {

		try {

			# Get all the authors (used in the author drop down)
			$authors = Author::getIdNamePair();

			# Get this book and all of its associated tags
		    $book    = Book::with('tags')->findOrFail($id);

		    # Get all the tags (not just the ones associated with this book)
		    $tags    = Tag::getIdNamePair();
		}
		catch(exception $e) {
		    return Redirect::to('/book')->with('flash_message', 'Book not found');
		}

    	return View::make('book_edit')
    		->with('book', $book)
    		->with('authors', $authors)
    		->with('tags', $tags);

	}


	/**
	* Process the "Edit a book form"
	* @return Redirect
	*/
	public function postEdit() {

		try {
	        $book = Book::with('tags')->findOrFail(Input::get('id'));
	    }
	    catch(exception $e) {
	        return Redirect::to('/book')->with('flash_message', 'Book not found');
	    }

	    try {
		    # http://laravel.com/docs/4.2/eloquent#mass-assignment
		    $book->fill(Input::except('tags'));
		    $book->save();

		    # Update tags associated with this book
		    if(!isset($_POST['tags'])) $_POST['tags'] = array();
		    $book->updateTags($_POST['tags']);

		   	return Redirect::action('BookController@getIndex')->with('flash_message','Your changes have been saved.');

		}
		catch(exception $e) {
	        return Redirect::to('/book')->with('flash_message', 'Error saving changes.');
	    }

	}


	/**
	* Process book deletion
	*
	* @return Redirect
	*/
	public function postDelete() {

		try {
	        $book = Book::findOrFail(Input::get('id'));
	    }
	    catch(exception $e) {
	        return Redirect::to('/book/')->with('flash_message', 'Could not delete book - not found.');
	    }

	    Book::destroy(Input::get('id'));

	    return Redirect::to('/book/')->with('flash_message', 'Book deleted.');

	}


	/**
	* Process a book search
	* Called w/ Ajax
	*/
	public function postSearch() {

		if(Request::ajax()) {

			$query  = Input::get('query');

			# We're demoing two possible return formats: JSON or HTML
			$format = Input::get('format');

			# Do the actual query
	        $books  = Book::search($query);

	        # If the request is for JSON, just send the books back as JSON
	        if($format == 'json') {
		        return Response::json($books);
	        }
	        # Otherwise, loop through the results building the HTML View we'll return
	        elseif($format == 'html') {

		        $results = '';
				foreach($books as $book) {
					# Created a "stub" of a view called book_search_result.php; all it is is a stub of code to display a book
					# For each book, we'll add a new stub to the results
					$results .= View::make('book_search_result')->with('book', $book)->render();
				}

				# Return the HTML/View to JavaScript...
				return $results;
			}
		}
	}



}