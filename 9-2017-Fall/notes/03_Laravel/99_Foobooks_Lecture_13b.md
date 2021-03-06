# Week 13 Foobooks progress, Part B
# Many to Many in Foobooks
The following is a __rough outline__ of some of the modifications I'll make to Foobooks during Week 13.

__This should not be considered a stand-alone document; for full details please refer to the lecture video and the Foobooks code source.__


## Using Tags/Many to Many
We have everything set up for a tags feature&mdash; migrations, seeders, models&mdash; now let's look at how we'd implement tags.

We need a way to associate tags with books (either from the *Edit Book* or *Create Book* page)

For authors, this was done with a dropdown which worked because each book can have only *one* author.

Each book can have *many* tags, though, so a dropdown won't do. Instead, let's show all possible tags with checkboxes. Example of what we're aiming for:

<img src='http://making-the-internet.s3.amazonaws.com/laravel-foobooks-tag-checkboxes@2x.png' style='max-width:357px; width:100%' alt='Tags checkboxes'>

To accomplish this, we'll need to gather the following data:

1. All the possible tags
2. All the tags associated with the book we're looking at.

First, a `getForCheckboxes()` method in the Tag model:

```php
public static function getForCheckboxes()
{
    $tags = Tag::orderBy('name')->get();

    $tagsForCheckboxes = [];

    foreach ($tags as $tag) {
        $tagsForCheckboxes[$tag['id']] = $tag->name;
    }

    return $tagsForCheckboxes;
}
```

Then update `BookController@edit`:

```php
public function edit($id = null)
{
    # Get this book and eager load its tags
    $book = Book::with('tags')->find($id);

    if (!$book) {
        return redirect('/book')->with('alert', 'Book not found');
    }

    # Get authors
    $authorsForDropdown = Author::getForDropdown();

    # Get all the possible tags so we can include them with checkboxes in the view
    $tagsForCheckboxes = Tag::getForCheckboxes();

    # Create a simple array of just the tag names for tags associated with this book;
    # will be used in the view to decide which tags should be checked off
    $tagsForThisBook = [];
    foreach ($book->tags as $tag) {
        $tagsForThisBook[] = $tag->name;
    }
    # Results in an array like this: $tagsForThisBook => ['novel', 'fiction', 'classic'];

    return view('book.edit')
        ->with([
            'book' => $book,
            'authorsForDropdown' => $tagsForThisBook,
            'tagsForCheckbox' => $tagsForCheckboxes,
            'tagsForThisBook' => $tagsForThisBook,
        ]);
}
```

Use this array of tags to construct the checkboxes in the view:
```php
# /resources/views/book/edit.blade.php

# [...]

@foreach ($tagsForCheckboxes as $id => $name)
    <input
        type='checkbox'
        value='{{ $id }}'
        name='tags[]'
        {{ (isset($tagsForThisBook) and in_array($name, $tagsForThisBook)) ? 'CHECKED' : '' }}
    >
    {{ $name }} <br>
@endforeach

# [...]
```


In `BookController@update` where we save the updates, sync the tags from the request:
```php
public function update(Request $request, $id)
{
    # [...validation removed for brevity...]

    # Find and update book
    $book = Book::find($request->id);

    $book->tags()->sync($request->input('tags')); # <---

    $book->title = $request->title;
    $book->cover = $request->cover;
    $book->published = $request->published;
    $book->purchase_link = $request->purchase_link;
    $book->save();

    # [...finish removed for brevity..]
}
```


## Revisiting the book delete feature
Now that books are associated with tags, you'll get a *foreign key constraint* SQL error when trying to delete a book. To fix, you want to remove any tag associations before the book is deleted...

```php
public function delete(Request $request)
{
    $book = Book::find($id);

    if (!$book) {
        return redirect('/book')->with('alert', 'Book not found');
    }

    $book->tags()->detach();

    $book->delete();

    return redirect('/book')->with('alert', $book->title.' was removed.');
}
```




## Misc changes 
In addition to the above, you'll also see the following changes reflected in the Foobooks code base. These changes were not shown in the lecture videos because they utilize concepts we've already covered.

+ Moved the tag checkbox code to its own view (`tagsForCheckboxes`) so it can be used on both Edit and Add book pages.
+ Integrated tags feature into the Add book page.
+ Added some more tags to the seeds.



