Yii Corrector
=============================

Расширение, помогающее исправлять пользовательские опечатки и раскладку клавиатуры.
Может использоваться для исправления поисковых запросов
без использования других различных поисковых движков

УСТАНОВКА
------------

Предпочтительно для установки использовать [composer](http://getcomposer.org/download/).

Добавьте
```json
"alviden/yii2-corrector": "*"
```
в секцию "require" файла composer.json

или запустите 
```
php composer.phar require --prefer-dist alviden/yii2-corrector "*"
```

После установки следует применить миграцию для создания таблицы:
```
php yii migrate --migrationPath=@vendor/alviden/yii2-corrector/src/migrations
```

ИСПОЛЬЗОВАНИЕ
------------
После установки в БД появится таблица `searchhash`, в которой будут храниться
корректные слова для исправления.
Например, следующий код добавляет 3 корректных слова и вводит запрос с ошибкой.
На выходе мы получаем наиболее релевантный результат:
```
	$sh = new \alviden\corrector\models\Corrector();
	$sh->addWord('хлебцы');
	$sh->addWord('хлеб');
	$sh->addWord('клей');
	echo $sh->getCorrectWord('хлебы'); // хлеб
	echo $sh->getCorrectWord('хлебц'); // хлебцы
```
Также можно просмотреть, как выполняется поиск результата для конкретного слова:
```
	$sh = new \alviden\corrector\models\Corrector();
	echo $sh->findRes('хлебц');
	/*  Current word: хлебцы
		Current koef: 0.96111111111111
		***
		NULL
	*/
```