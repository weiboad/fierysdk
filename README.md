### RagnarSDK

### Introdution
[中文文档](./README_CN.md)
> * for the complex system online tracing.
> * support online debug.level log.exception and error collect.performance monitor and depend reloation picture
> * for the https://github.com/weiboad/fiery

### install
> * PHP5.3+ with bcmath
> * charset utf-8 project

#### Nginx

copy the nginx/fiery_fastcgi_pararms -> nginx/conf
and edit the vhost config
example：

```
    server{
        listen 80;
        charset utf-8;
        root /path/xxx/xxx/src/public;
        server_name xxx.com;
        
        location /{
            index index.php index.html index.htm;
            if (-f $request_filename) {
                break;
            }
            if (-d $request_filename) {
                break;
            }
            if ($request_filename !~ (\.css|images|index\.php.*) ) {
                rewrite ^/(.*)$ /index.php/$1 last;
                break;
            }
        }
    
        location ~ /index.php/ {
            fastcgi_index index.php;
            fastcgi_pass 127.0.0.1:9000;
            include fastcgi_params;
            include fiery_fastcgi_params; # here is the point
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            fastcgi_read_timeout 600;
        }
    
        location ~ \.php$ {
            fastcgi_index index.php;
            fastcgi_pass 127.0.0.1:9000;
            include fastcgi_params;
            include weiboad_fastcgi_params; # here is the point
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            fastcgi_read_timeout 600;
        }
    }
```

```
# reload the nginx config
nginx -s reload

```

#### Apache Env
```
<VirtualHost *:80>
    ServerAdmin webmaster@demo.com
    DocumentRoot "e:\wwwroot\demo"
    ServerName my.demo.com
    ErrorLog "logs/my.demo.com-error.log"
    CustomLog "logs/my.demo.com-access.log" common
    SetEnv RAGNAR_LOGPATH /data1/ragnar/  # here is the point
    SetEnv RAGNAR_IDC 0  # here is the point
    SetEnv RAGNAR_IP 192.168.1.123  # here is the point

    <Directory "e:\wwwroot\demo">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Ragnarsdk Introduce

Run command to get this composer
```
composer require weiboad/fierysdk

```
init on the bootstrap of php project


```
    require_once("vendor/autoload.php");
    
    
    use \Adinf\RagnarSDK\RagnarSDK as RagnarSDK;
    use \Adinf\RagnarSDK\RagnarConst as RagnarConst;
    
    //for the Temporary disable this sdk
    //RagnarSDK::disable();
    
    //default log level is info you can Temporary open the debug level by send header
    RagnarSDK::setLogLevel(RagnarConst::LOG_TYPE_INFO); 
    
    //this must run at latest
    //ragnar_projectname is you project name will use on log folder name
    RagnarSDK::init("ragnar_projectname");
     
    //extra info on the meta log .please don't set too much
    //RagnarSDK::setMeta(123, "", array("extrakey" => "extraval"));
    
    //Ragnar level log example
    // this is info log you can see this on tracing page on fiery 
    RagnarSDK::RecordLog(RagnarConst::LOG_TYPE_INFO, __FILE__, __LINE__, "module1_msg", "i wish i can fly!");
    // this is debug log 
    RagnarSDK::RecordLog(RagnarConst::LOG_TYPE_DEBUG, __FILE__, __LINE__, "module2_msg", "i wish i'm rich!");
    
    //customize performance dig point example will display on tracing page on fiery
    //dig start
    $digpooint = RagnarSDK::digLogStart(__FILE__,__LINE__,"ragnar_test");
    
        //run something.....
    //dig end
    RagnarSDK::digLogEnd($digpooint,array("happy"=>1));
    

```

### Ragnar level log
> * level log：set the log level to decide the customize log to dump
> * log search：all the log in the level will be show on the tracing page.
> * exception and error：will be show on the error statistic page 
> * performance：a easy way the record the function cost time and make an statistics on curl mysql api

#### log level
> * LOG_TYPE_TRACE trace log for the low level debug
> * LOG_TYPE_DEBUG Debug log
> * LOG_TYPE_NOTICE notice log on the system
> * LOG_TYPE_INFO  info for the tips the working status
> * LOG_TYPE_ERROR when the system error will record this level
> * LOG_TYPE_EMEGENCY emegency log that will send SMS or Email to admin
> * LOG_TYPE_EXCEPTION Exception log

> * LOG_TYPE_PERFORMENCE performance log all the dig point will use this


#### Curl dig point
curl dig point

```
    //curl must fill
    $digpooint = RagnarSDK::digLogStart(__FILE__, __LINE__, "curl");
    
    //curl init ....
    
    $nextrpcidheader = RagnarSDK::getCurlChildCallParam($digpooint);
    curl_setopt($this->ch, CURLOPT_HTTPHEADER, $nextrpcidheader);
    
    $result = //curl exec ...
    
    $ext = array("errorno" => $errno, "error" => curl_error($this->ch));
    $info = curl_getinfo($this->ch);
    
    //digCurlEnd($digPoint, $url, $method, $postParam, $getParam, $curlInfo, $errCode, $errMsg, $result)
    
    RagnarSDK::digLogEnd($digpooint, array(
                "url" => $info['url'], "method" => self::get_method(),
                "param" => array("post" => $this->post_fields, "get" => $this->query_fields),
                "info" => $info,
                "error" => $ext,
                "result" => $result,
    );

```

#### Mysql
Mysql dig point
```
    //this for record the exception when the error
    RagnarSDK::RecordLog(\Adinf\Ragnar\Ragnar::LOG_TYPE_EXCEPTION, __FILE__, __LINE__, "mysql", array("fun" => "query", "sql" => $sql, "error" => $ex->getMessage()));
    
    //start monitor the performance
    $digpooint = RagnarSDK::digLogStart(__FILE__, __LINE__, "mysql");
    
    //do some sql execute
    
    //for the mysql performance dig point end
    //RagnarSDK::digLogEnd($digpooint, array("sql" => $sql, "data" => "sql的参数", "op" => "select\delete\update\...", "fun" => "execute_sql"));
    RagnarSDK::digMysqlEnd($digPoint, $sql, "sql的参数", "select\delete\update\...", "execute_sql");
    //if is error
    if(error){
        RagnarSDK::RecordLog(RagnarSDK::LOG_TYPE_EXCEPTION, __FILE__, __LINE__, "mysql", array("fun" => "execute", "sql" => $sql, "error" => $error));
    }
```

### Temporary change
online change the log level by send header

```
    X-RAGNAR-TRACEID   traceid
    X-RAGNAR-RPCID     rpcid (spanid)
    X-RAGNAR-LOGLEVEL  log level
```
