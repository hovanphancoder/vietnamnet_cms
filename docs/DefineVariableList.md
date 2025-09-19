LIST PATH DEFINE NAME:
- PATH_ROOT: Define path of root folder. (Not have / at end path, Example value: /www/wwwroot/domain.com)
- PATH_APP: Define path of application folder. (Have / at end path, Example value: /www/wwwroot/domain.com/application/)
- PATH_SYS: Define path of system folder. (Have / at end path, Example value: /www/wwwroot/domain.com/system/)
- PATH_WRITE: Define path of writeable folder. (Have / at end path, Example value: /www/wwwroot/domain.com/writeable/)
- PATH_PLUGINS: Define path of plugins folder. (Have / at end path, Example value: /www/wwwroot/domain.com/plugins/)
- PATH_THEMES: Define path of themes folder. (Have / at end path, Example value: /www/wwwroot/domain.com/themes/)

LIST APP DEFINE NAME:
- APP_VER: Define version of application. (Example value: 1.1.0)
- APP_THEME_NAME: Define name of theme. (Example value: cmsfullform)
- APP_THEME_PATH: Define path of theme. (Have / at end path, Example value: /www/wwwroot/domain.com/themes/cmsfullform/)
- APP_URI: Define uri of application. (Example value: [ 'uri'   => $path, 'split' => $segments, 'query' => $safeQuery ])
- APP_ROUTE: Define route of application. (Example value: [ 'controller' => $controllerClass, 'action' => $action, 'params' => $params ])   
- APP_POSTTYPES: Define list posttype of CMS. (Example value: ['pages','posts','products'] )
- APP_LANGUAGES: Define list language of CMS. (Example value: ['en'=>['name'=>'English (US)','flag'=>'us'],'id'=>['name'=>'Indonesia','flag'=>'id'],'jp'=>['name'=>'Japanese','flag'=>'jp']] )
- APP_LANG_DF: Define default language of CMS. (Example value: 'en')
- APP_LANG: Define current using language of CMS. (Example value: 'jp')
LIST APP DEBUG DEFINE NAME:
- APP_DEBUGBAR: Define debugbar of application enable or disable. (Example value: true)
- APP_START_TIME: Define start time of application. (Example value: microtime(true))
- APP_START_MEMORY: Define start memory of application. (Example value: memory_get_usage())
