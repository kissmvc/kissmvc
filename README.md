# KISS Framework
KISS Framework - Very simple semi MVC framework with fast learning curve created with Keep It Simple Stupid principle.

First simple example application here - KISS Blog - https://github.com/kissmvc/kissblog

## About
KISS framework is a simple, easy to use framework created with students for students, to speedup learning, simplify programming in PHP and encapsulate complexity of standard MVC for beginners. It is created with *Keep It Simple Stupid* principle in mind, so this is why KISS and Semi MVC (models is not required, but could be used).

## How it's works
KISS is semi MVC framework, where are controllers and models joined into one object for simplicity. So this controller is dedicated to prepare data for presenter (view) and then this data is rendered by presenter (view/theme). All application logic is based on two thing - page and action. Page define controller, which sits inside app folder with same name as page. Action defines function that will be used to prepare and fill data for presenter.

Example: *index.php?page=news&action=addnew* ... this URL mean you will load "news" controller and call "addnew" function. In detail, application will load news.php (controller) from "app" folder. This news.php file contains class named "NewsPage" with function "addnew". Here you can do queries do database, any other things and save it to *$page->data->xxx* container. This *$page->data->xxx* container is then used inside template to render data to user. (It's eg. like *$data['xxx']* in Laravel).

If you not define page, then "index" page (controller) is used. If you not define action, then the "show" action (function) is used. For SEO url, there is modified AltoRouter with default *"/controller/action"* route.

## How to use
KISS is ready to use PHP application framework, so you can copy source to your app directory and you are ready to build your application. What you need:

* edit config.php to setup database connection
* create "page" (controller) file inside app folder, default index.php
* create functions for all required actions inside controller class, do queries by powerful database class, fill $page->data->xxx container.
* create theme file inside "theme" folder with same name as "page", so create index.php for default view, news.php for news "page" etc. Render (echo) data from $page->data->xxx container inside your template. 

There is default Start Bootstrap starter template. You can change template inside controller eg: $page->tpl = 'news-edit' for use "news-edit.php" template.

## Default structure
- *app folder* - contains controller files with controller class named by filename+Page, e.g. class IndexPage {} for index.php controller file
- *system folder* - contains KISS framework files
- *theme folder* - contains theme files, simple template engine used where variables is displayed by echo
- *config.php* - in root folder, contains base definitions
- *index.php* - in root folder - app loader

## Classes
There is so much and so powerfull classes inside KISS framework, more documentation soon.

## Examples

**Retrieve data from GET, POST, FILES**

```php
$title = $page->from->get('title')->val();

//or escaped
$title = $page->from->get('title')->escaped();

//or directly from GET
$title = $page->from->request->get['title'];

//or format as datetime
$date = $page->from->post('date')->as_datetime();

//look inside input.php for more
```

**Query database and fill data container**

```php
$sql = 'SELECT * FROM news_table LIMIT 10';
$page->data->news = $page->db->query($sql);
```

**Easier select and fill data container**

```php
$page->data->news = $page->db->select('news_table', 5); //means SELECT * FROM news_table WHERE id = 5 - db class automaticaly use field with primary key eg: id
```

**Insert data**

```php
$sql = "INSERT INTO news_table (title, content) VALUES ('KISS Framework', 'This is content of this news')";
$page->db->query($sql);

//or
$last_id = $page->db->query($sql);

//or without using SQL code
$values = array('title' => 'KISS framework', 'content' => 'This is content of this news.');
$page->db->insert('news_table', $values);

//or populate array from POST/GET
$values = array (
    'title' => , $page->from->post('title')->val(),
    'content' => , $page->from->post('text')->val()
)
$page->db->insert('news_table', $values);
```

**Need escape?**

```php
//use 
$page->from->...->escaped()

//or
$title = $page->db->escape($page->from->post('title')->val());

//or
$title = $page->db->escape($_GET['title']);
```

**Update data**

```php
$sql = "UPDATE news_table SET title='KISS Framework', content='This is content of this news' WHERE id = 5";
$page->db->query($sql);

//or without using SQL code
$values = array('title' => 'KISS framework', 'content' => 'This is content of this news.');
$page->db->update('news_table', $values, 5);
```

**Delete data**

```php
$sql = "DELETE FROM news_table WHERE id = 5";
$page->db->query($sql);

//or without using SQL code
$page->db->delete('news_table', 5);
```

**Using rows from query**

```php
$sql = 'SELECT * FROM news_table LIMIT 10';
$page->data->news = $page->db->query($sql);

foreach ($page->data->news as $article) {
  echo $article->title;
}

//or any row directly by index
echo $page->data->news->rows[2]->title;

//same as
echo $page->data->news[2]->title;

//or first row
echo $page->data->news->row->title;
```

**Resize image**

```php
$image = new Image('public/image/photo.jpg');
$image->setBackground('#000000', 64)->resize(300, 300, Image::FULL)->save('public/cache/photo_300x300.png');

//or use sharpen effect before save
$image->setBackground('#000000', 64)->resize(300, 300, Image::FULL)->sharpen()->save('public/cache/photo_300x300.png');

//or make it brigher
$image->setBackground('#000000', 64)->resize(300, 300, Image::FULL)->brightness(10)->save('public/cache/photo_300x300.png');
```

**Send email**

```php
$mail = new Mail();
		
$mail->addFrom('tester@example.com')
->addAttachment('public/Test_1.zip')
->addTo('tester2@example.com')
->addTo('someone@somewhere.com')
->addSubject('Hello form KISS MVC')
->setPriority(Mail::EMAIL_HIGH)
->addBody('<div>Hello from KISS MVC Framework! Images is attached automaticaly<br><p><img src="public/cache/photo_300x300.jpg"></p></div>')
->send();
//or save as eml for testing without sending spam
->save('public/email.eml');
```

**Work with filesystem**

```php
$fs = new FileSystem();
echo $fs->fromFile('public/test/photo.jpg')->humanSize();
$fs->createDir('public/test/')->moveTo('public/images/');
$fs->fromPath('public/images/photo.jpg')->copyTo('public/images/photo_2.jpg');
$fs->getDirecory()->getFullTree() //use latest directory, so 'public/images/' and return multidimensional array with SplFile
```

**Session handling**

```php
$loged = $page->session->get('loged');
//or array style
$loged = $page->session['loged'];
//or object style
$loged = $page->session->loged;
//same for set
$page->session-set('loged', true);
//or
$page->session['loged'] = true;
//or
$page->session->loged = true;
```

**Need default value if key from GET, POST, Files, SESSION is not set?**

```php
$title = $page->from->get('title', 'my default title')->val();
//need to convert checkbox input value to true/false?
$enabled = $page->from->get('enabled', false)->as_bool();
//or
$enabled = (bool)$page->from->get('enabled', false)->val();
```

**Validation available**

```php
$validator = new Validator();
//validate email
if ($validator->validate('email', 'someone@somewhere.com')) {
	echo 'Valid email address';
}
//available validators
//boolean, bool, number, int, float, numeric, string, null, email, url, ip, date, datetime
```

**And The Real Magic - QueryHelper**

We have eg: contact form with inputs, we want save messages to database table. Our inputs is named "name", "email", "message". We use POST method.

```php
$query = new QueryHelper($page);

$query->into('contacts_table')
->fromPost() /* fill fields by values from POST, or ->fromGet() to use GET */
->addField('name', 'name') /* first parameter - table field, second parameter key from POST */
->addField('email', 'email')
->addField('msg', 'message')
->insert();
//or
->update($your_id);

//if input names is same as field names, omit second parameter
$query->into('contacts_table')
->fromPost() /* fill fields by values from POST */
->addField('name')
->addField('email')
->addField('msg')
->insert();

//or in one line, elegant
$query->into('contacts_table')->fromPost()->addField('name')->addField('email')->addField('msg')->insert();

//if you need to add exact value also, combine addField with addValue as you need
$query->into('contacts_table')
->fromPost() /* fill fields by values from POST */
->addField('name')
->addField('email')
->addField('msg')
->addValue('enabled', true)
->insert();

//another style
$query->into('contacts_table')
->fromPost() /* fill fields by values from POST */
->addField('name', $page->from->post('name')->val())
->addField('email', $page->from->post('email')->val())
->addField('msg', $page->from->post('message')->val())
->insert();

//validate before insert
$query->into('contacts_table')
->fromPost()
->addField('name')
->addField('email', null, null, 'email') //4. parameter - validator - email
->addField('msg');

if ($query->isValid()) {
  $query->insert();
} else {
  var_dump($query->validations());
}
```

## And it's not all
More samples, documentation and website soon.
