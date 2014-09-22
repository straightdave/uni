uni
===

(for test collaterals, see uni-test branch)

## the purposes
* To build an SSO (Single Sign-On) service from scratch
* To learn or know some security risks and how to overcome them
* To learn PHP/Slim
* To accumulate experiences on such related sphere

## tech archi
* routing based on Slim Framework (refer to http://docs.slimframework.com)
* ORM: Eloquent (only use its models and data access approach; i hate its messy Migration)
* use Twig as view
* Bootstrap
* use encrypted cookies (Slim internal middleware)

## Derived outputs
* test automation
* auto deployment tool for Slim( or laravel? :-) )

## Status
this is in the progress and any help is much welcomed

## TODO
* all pages around Apps
* user info table and model

## how to install this
1. git clone to your local
2. restore mysql file : db.sql to database 'uni_dev' or other you like
3. set up apache site (this is a slim site: point doc root to /this/app/folder/public); you should notice that here using a .htaccess file in public folder to make url rewrite (refer to slim doc)
4. config your mysql db info in public/index.php

## about test framework
1. use PHPUnit as testing engine
2. use Httpful as php http client
(both above are installed from composer)
3. use Eloquent in test framework for tests based on models

## Contact
eyaswoo@163.com
