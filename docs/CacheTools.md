---
id: cache_tools
title: Cache Tools
---

Merlin has a cache for content.  The `cache` command provides a way to clear the cache for a given domain or a specific url as well as print out some cache statistics.

There are two main ways it can be used - with or without an existing configuration file.

###With an Existing Config File###

You can use an existing crawler or migrate config YAML file which will use the `domain` and any `cache_dir` setting found.

You can then use relative or absolute domains via the `--purge-url` flag.


**Statistics:**

```
./migrate cache -c config/my_config.yml --stats
```

**Purge Single URL:**

```
./migrate cache -c config/my_config.yml \
  --purge-url="/somepage.html"`
```

**Purge Multple URLs:**

```
./migrate cache -c config/my_config.yml \
  --purge-url="http://localhost/somepage.html" \
  --purge-url="http://localhost/anotherpage.html" \
  --purge-url="http://localhost/andanother.html"
```
 
**Purge Entire Domain:**

While you are prompted to confirm, you may like to take a backup of your cache before doing this if the cache for the domain is large!

```
./migrate cache -c config/my_config.yml \
  --purge-domain="http://localhost"

 Really purge entire cache for http://localhost? [y/n]:
 n

``` 
 
 
###Without an Existing Config File###
If you don't have or wish to use an existing config file, you can specify the parameters directly on the command line.

**NOTE:** When used without a config file, you must specify the absolute url to `--purge-url` or specify `--domain=http://yourdomain.com`.


**Statistics:**

```
./migrate cache --cache-dir="/tmp/merlin_cache" \
  --domain="http://localhost" --stats
```

**Purge Single URL:**

```
./migrate cache --cache-dir="/tmp/merlin_cache" \
  --purge-url="http://localhost/somepage.html"
```


**Purge Multple URLs:**

```
./migrate cache --cache-dir="/tmp/merlin_cache" \
  --purge-url="http://localhost/somepage.html" \
  --purge-url="http://localhost/anotherpage.html" \
  --purge-url="http://localhost/andanother.html"
```

**Purge Entire Domain:**

While you are prompted to confirm, you may like to take a backup of your cache before doing this if the cache for the domain is large!

```
./migrate cache --cache-dir="/tmp/merlin_cache" \
  --purge-domain="http://localhost"

 Really purge entire cache for http://localhost? [y/n]:
 n

```



