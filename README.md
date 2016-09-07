#Yii2 component - Config

##Description
Yii2 component which allow get application parameters from database or default configs, import and manage them from admin panel dynamically.
Allows to setup params directly at app config file.



##Install

Get from composer
```php
composer require demmonico/yii2-config
```
or add dependency at composer.json in "require" section
```php
"demmonico/yii2-config": "*"
```

Create DB table for config storage using migrate (copy file `demo/tbl_config.php`) or manually.



##Configure

###Simple setup
```php
return [
    //...
    'components' => [
        //...
        'config' => 'demmonico\config\Configurator',
    ],
];
```

###Flexible setup
There are several params of Configurator class which can be modified:
- class (`class`)
- name of config storage DB table (`tableName`)
- filename of custom default config params (`defaultConfigFile`)

```php
return [
    //...
    'components' => [
        //...
        'config' => [
            'class' => 'demmonico\config\Configurator',
            'tableName' => 'tbl_config',
            'defaultConfigFile' => '@common/data/default.php',
        ],
    ],
];
```

###Configure handler of missing config event
There are several params of Configurator class which can be modified:
- class (`class`)
- filename of missing config params storage (`fileStorage`)
- dirname of missing config params storage (`folderStorage`)

#####Important
Folder specified as `folderStorage` should be exist. Here `fileStorage` file will be created if some config params will be exist.
Recommended to create folder previously with `.gitkeep` file inside.

```php
return [
    //...
    'components' => [
        //...
        'config' => [
            //...
            'handler' => [
                'class' => 'testHandler',
                'config'=> [
                    'fileStorage' => 'missing_configs',
                    'folderStorage' => '@common/data/',
                ],
            ],
        ],
    ],
];
```



##Usage

Possibility of modifying system configs by web application admin is target of this extension so all usages will realize modify function.

Try to use complex name in dotted style as param key at format: `moduleName.paramName` or `moduleName.submoduleName.paramName`.
Do not use `appconfig` as `moduleName`. This is reserved.



###Frontend

####Get application config param

Get application config param from DB or `\Yii::$app->params` array.

Getter sequentially passes following steps. If it finds out value the pass breaks. 
Flow here:
- internal class variable (use if this param had been requested)
- cache (use default `\Yii::$app->cache`)
- DB table (use `tableName` table)
- custom param `defaultValue` (if second param `defaultValue` was set)
- custom default config params array (`defaultConfigFile`)
- array `\Yii::$app->params`
In the end, if no value will be found then Exception will be throwed.

```php
\Yii::$app->config->get('paramName');
```
or
```php
\Yii::$app->config->get('paramName', 'defaultValue');
```
If param value doesn't exists at DB table (or cached view) it will be added to missing config file by handler (`fileStorage`).
After that web application administrator can import missing values (and all defaults also) into DB table and modify them.

If some config param need more secure level of setup you can use local param file to avoid commit them at public repo.



####Get array of application config params by key mask

Get array of application config params by key mask from DB.

Getter sequentially passes following steps. If it finds out value the pass breaks. 
Flow here:
- internal class variable (use if this param had been requested)
- cache (use default `\Yii::$app->cache`)
- DB table (use `tableName` table)
In the end, if no value by key mask will be found then empty array will be appeared as a result.

```php
\Yii::$app->config->getLike('beginParamName');
```
For example, 
```php
\Yii::$app->config->getLike('someModule');
```
will return all params linked with `someModule`:
- `someModule.param1`
- `someModule.param2`
- `someModule.submodule.param1`
- ...



####Get application param

Get application param from DB or `\Yii::$app`.
```php
\Yii::$app->config->app('paramName');
```
For example, 
```php
\Yii::$app->config->app('name');
```
will return your application name storing at DB or if it absent directly from application config (`\Yii::$app->name`).



####Use Configurator for pre-configuring other components

#####Modify config file directly

Configurator can be used for pre-configuring other components at config file directly.
These components should contain and use `ConfigurableTrait` (example see at `demo`).

For example configuring sms component with `sms.senderNumber` param:
```php
// ...
'sms' => [
    'class' => 'demmonico\sms\Sender',
    'senderNumber' => [
        'component' => 'config',
        'sms.senderNumber' => 'AppName',
    ],
],
```

#####Bootstrap components

Either Configurator can pre-configure other component implementing bootstrap interface.

Add config component to app bootstrap section:
```php
return [
    // ...
    'bootstrap' => [..., 'config', ...],
    // ...
],
```
Then fill bootstrap component section:
```php
return [
    //...
    'components' => [
        //...
        'config' => [
            //...
            'bootstrap' => [
                'cloudStorage' => [
                    'key' => 'cloud_amazons3_key',
                    'secret' => 'cloud_amazons3_secret',
                    'bucket' => 'cloud_amazons3_bucket',
                    'cloudStorageBaseUrl' => 'cloud_amazons3_baseurl',
                ],
                'upload' => [
                    'externalStorageBaseUrl' => 'cloud_amazons3_baseurl',
                ],
            ],
        ],
    ],
];
```
and add `cloud_amazons3_bucket`, `cloud_amazons3_baseurl`, `cloud_amazons3_baseurl` to params array (or set default params file) 
either add `cloud_amazons3_key`, `cloud_amazons3_secret` to local params to protect them.





###Backend

In backend part should be used `ConfiguratorAdmin` class or inheritances. 
It use `ConfiguratorAdminTrait` which allow import missing config from `fileStorage` file or default config from `defaultConfigFile` file (if exists).

Administrator of the web application can:
- get list of configs, filter and sort them using your app admin grid schema (using standard `IndexAction` or etc.)
- modify config exists (using standard `UpdateAction` or etc.)
- import absent config (using `admin/ImportMissingAction`)
- import default config if `defaultConfigFile` file exists (using `admin/ImportDefaultAction`)
