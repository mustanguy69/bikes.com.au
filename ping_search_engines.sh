#!/bin/bash
echo -------------  Pinging the search engines with the sitemap.xml url, starting at $(date): -------------------

echo Pinging Google...
wget -O- http://www.google.com/webmasters/tools/ping?sitemap=https%3A%2F%2Fwww.bikes.com.au%2F%2Fsitemap%2Fsitemap.xml

echo Pinging Bing...
wget -O- http://www.bing.com/ping?siteMap=https%3A%2F%2Fwww.bikes.com.au%2F%2Fsitemap%2Fsitemap.xml

echo DONE!